<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Http\Request;

class PaginationService
{
    const DEFAULT_PER_PAGE = 50;
    const MAX_PER_PAGE = 1000;
    const LARGE_DATASET_THRESHOLD = 10000;

    
    public function paginateQuery(
        Builder $query,
        int $perPage = self::DEFAULT_PER_PAGE,
        ?int $page = null,
        array $options = []
    ): LengthAwarePaginator {
        $perPage = min($perPage, self::MAX_PER_PAGE);
        $page = $page ?: Paginator::resolveCurrentPage();
        
        if ($this->shouldUseCursorPagination($query)) {
            return $this->cursorPaginate($query, $perPage, $page, $options);
        }
        
        return $this->optimizedPaginate($query, $perPage, $page, $options);
    }

    
    public function paginateCollection(
        Collection $collection,
        int $perPage = self::DEFAULT_PER_PAGE,
        ?int $page = null,
        array $options = []
    ): LengthAwarePaginator {
        $page = $page ?: Paginator::resolveCurrentPage();
        $perPage = min($perPage, self::MAX_PER_PAGE);
        
        $offset = ($page - 1) * $perPage;
        $items = $collection->slice($offset, $perPage)->values();
        
        return new LengthAwarePaginator(
            $items,
            $collection->count(),
            $perPage,
            $page,
            array_merge($options, [
                'path' => request()->url(),
                'pageName' => 'page'
            ])
        );
    }

    
    public function paginateDashboardData(
        array $data,
        int $perPage = self::DEFAULT_PER_PAGE,
        ?int $page = null,
        string $cacheKey = null
    ): array {
        $page = $page ?: 1;
        $perPage = min($perPage, self::MAX_PER_PAGE);
        
        $total = count($data);
        $offset = ($page - 1) * $perPage;
        $items = array_slice($data, $offset, $perPage);
        
        return [
            'data' => $items,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => ceil($total / $perPage),
                'from' => $offset + 1,
                'to' => min($offset + $perPage, $total),
                'has_more' => ($offset + $perPage) < $total
            ]
        ];
    }

    
    public function infiniteScrollPaginate(
        Builder $query,
        int $perPage = self::DEFAULT_PER_PAGE,
        ?string $cursor = null,
        string $cursorColumn = 'id'
    ): array {
        $perPage = min($perPage, self::MAX_PER_PAGE);
        
        if ($cursor) {
            $query->where($cursorColumn, '>', $cursor);
        }
        
        $items = $query->orderBy($cursorColumn)
                      ->limit($perPage + 1)
                      ->get();
        
        $hasMore = $items->count() > $perPage;
        if ($hasMore) {
            $items->pop();
        }
        
        $nextCursor = $hasMore && $items->isNotEmpty() 
            ? $items->last()->{$cursorColumn} 
            : null;
        
        return [
            'data' => $items,
            'next_cursor' => $nextCursor,
            'has_more' => $hasMore
        ];
    }

    
    public function paginateWithFilters(
        Builder $query,
        Request $request,
        array $searchableColumns = [],
        array $filterableColumns = []
    ): LengthAwarePaginator {
        if ($request->has('search') && !empty($searchableColumns)) {
            $searchTerm = $request->get('search');
            $query->where(function ($q) use ($searchableColumns, $searchTerm) {
                foreach ($searchableColumns as $column) {
                    $q->orWhere($column, 'LIKE', "%{$searchTerm}%");
                }
            });
        }
        
        foreach ($filterableColumns as $column) {
            if ($request->has($column) && $request->get($column) !== null) {
                $value = $request->get($column);
                if (is_array($value)) {
                    $query->whereIn($column, $value);
                } else {
                    $query->where($column, $value);
                }
            }
        }
        
        if ($request->has('sort_by')) {
            $sortBy = $request->get('sort_by');
            $sortDirection = $request->get('sort_direction', 'asc');
            
            if (in_array($sortDirection, ['asc', 'desc'])) {
                $query->orderBy($sortBy, $sortDirection);
            }
        }
        
        $perPage = min(
            $request->get('per_page', self::DEFAULT_PER_PAGE),
            self::MAX_PER_PAGE
        );
        
        return $this->paginateQuery($query, $perPage);
    }

    
    public function getPaginationMeta(LengthAwarePaginator $paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'last_page' => $paginator->lastPage(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
            'has_more_pages' => $paginator->hasMorePages(),
            'links' => [
                'first' => $paginator->url(1),
                'last' => $paginator->url($paginator->lastPage()),
                'prev' => $paginator->previousPageUrl(),
                'next' => $paginator->nextPageUrl()
            ]
        ];
    }

    
    public function processInChunks(
        Builder $query,
        callable $callback,
        int $chunkSize = 1000
    ): array {
        $processed = 0;
        $errors = [];
        
        try {
            $query->chunk($chunkSize, function ($items) use ($callback, &$processed, &$errors) {
                try {
                    $callback($items);
                    $processed += $items->count();
                } catch (\Exception $e) {
                    $errors[] = [
                        'chunk_start' => $processed,
                        'error' => $e->getMessage()
                    ];
                }
            });
        } catch (\Exception $e) {
            $errors[] = [
                'general_error' => $e->getMessage()
            ];
        }
        
        return [
            'processed' => $processed,
            'errors' => $errors
        ];
    }

    
    private function shouldUseCursorPagination(Builder $query): bool
    {
        try {
            $count = $query->getQuery()->getCountForPagination();
            return $count > self::LARGE_DATASET_THRESHOLD;
        } catch (\Exception $e) {
            return true;
        }
    }

    
    private function cursorPaginate(
        Builder $query,
        int $perPage,
        int $page,
        array $options
    ): LengthAwarePaginator {
        
        $offset = ($page - 1) * $perPage;
        
        $items = $query->offset($offset)
                      ->limit($perPage)
                      ->get();
        
        $total = $this->getEstimatedCount($query);
        
        return new LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $page,
            array_merge($options, [
                'path' => request()->url(),
                'pageName' => 'page'
            ])
        );
    }

    
    private function optimizedPaginate(
        Builder $query,
        int $perPage,
        int $page,
        array $options
    ): LengthAwarePaginator {
        $countQuery = clone $query;
        
        $countQuery->getQuery()->orders = null;
        $countQuery->getQuery()->limit = null;
        $countQuery->getQuery()->offset = null;
        
        $total = $countQuery->count();
        
        $offset = ($page - 1) * $perPage;
        $items = $query->offset($offset)->limit($perPage)->get();
        
        return new LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $page,
            array_merge($options, [
                'path' => request()->url(),
                'pageName' => 'page'
            ])
        );
    }

    
    private function getEstimatedCount(Builder $query): int
    {
        try {
            $table = $query->getModel()->getTable();
            $result = \DB::select("
                SELECT table_rows 
                FROM information_schema.tables 
                WHERE table_schema = DATABASE() 
                AND table_name = ?
            ", [$table]);
            
            return $result[0]->table_rows ?? 0;
        } catch (\Exception $e) {
            return $query->count();
        }
    }
}


