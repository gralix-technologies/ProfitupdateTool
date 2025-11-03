<?php

namespace App\Services;

use App\Models\Product;
use App\Repositories\ProductRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class ProductService
{
    public function __construct(
        private ProductRepository $productRepository,
        private ProductFieldValidator $fieldValidator
    ) {}

    
    public function getAllProducts(): Collection
    {
        return $this->productRepository->all();
    }

    
    public function getPaginatedProducts(int $perPage = 15): LengthAwarePaginator
    {
        return $this->productRepository->paginate($perPage);
    }

    
    public function findProduct(int $id): ?Product
    {
        return $this->productRepository->find($id);
    }

    
    public function createProduct(array $data): Product
    {
        if ($this->productRepository->nameExists($data['name'])) {
            throw ValidationException::withMessages([
                'name' => ['A product with this name already exists.']
            ]);
        }

        if (!in_array($data['category'], Product::getCategories())) {
            throw ValidationException::withMessages([
                'category' => ['Invalid product category.']
            ]);
        }

        if (isset($data['field_definitions'])) {
            $this->validateFieldDefinitions($data['field_definitions']);
            
            // Ensure customer_id field is present and required
            $data['field_definitions'] = $this->fieldValidator->ensureCustomerIdField($data['field_definitions']);
        }
        
        // Auto-suggest portfolio_value_field if not set
        if (empty($data['portfolio_value_field']) && isset($data['field_definitions'])) {
            $data['portfolio_value_field'] = $this->fieldValidator->suggestPortfolioValueField($data['field_definitions']);
        }

        return $this->productRepository->create($data);
    }

    
    public function updateProduct(Product $product, array $data): Product
    {
        if (isset($data['name']) && $this->productRepository->nameExists($data['name'], $product->id)) {
            throw ValidationException::withMessages([
                'name' => ['A product with this name already exists.']
            ]);
        }

        if (isset($data['category']) && !in_array($data['category'], Product::getCategories())) {
            throw ValidationException::withMessages([
                'category' => ['Invalid product category.']
            ]);
        }

        if (isset($data['field_definitions'])) {
            $this->validateFieldDefinitions($data['field_definitions']);
            
            // Ensure customer_id field is present and required
            $data['field_definitions'] = $this->fieldValidator->ensureCustomerIdField($data['field_definitions']);
        }
        
        // Validate portfolio_value_field if being set
        if (isset($data['portfolio_value_field'])) {
            $product->portfolio_value_field = $data['portfolio_value_field'];
            if (!$this->fieldValidator->validatePortfolioValueField($product)) {
                throw ValidationException::withMessages([
                    'portfolio_value_field' => ['Portfolio value field must be a numeric field type.']
                ]);
            }
        }

        $this->productRepository->update($product, $data);
        
        return $product->fresh();
    }

    
    public function deleteProduct(Product $product): bool
    {
        if ($product->productData()->exists()) {
            throw ValidationException::withMessages([
                'product' => ['Cannot delete product with associated data.']
            ]);
        }

        if ($product->formulas()->exists()) {
            throw ValidationException::withMessages([
                'product' => ['Cannot delete product with associated formulas.']
            ]);
        }

        return $this->productRepository->delete($product);
    }

    
    public function defineFields(Product $product, array $fields): Product
    {
        $this->validateFieldDefinitions($fields);
        
        return $this->updateProduct($product, ['field_definitions' => $fields]);
    }

    
    public function validateProductSchema(array $schema): bool
    {
        $requiredFields = ['name', 'category'];
        foreach ($requiredFields as $field) {
            if (!isset($schema[$field]) || empty($schema[$field])) {
                throw ValidationException::withMessages([
                    $field => ["The {$field} field is required."]
                ]);
            }
        }

        if (!in_array($schema['category'], Product::getCategories())) {
            throw ValidationException::withMessages([
                'category' => ['Invalid product category.']
            ]);
        }

        if (isset($schema['field_definitions'])) {
            $this->validateFieldDefinitions($schema['field_definitions']);
        }

        return true;
    }

    
    public function getProductsByCategory(string $category): Collection
    {
        if (!in_array($category, Product::getCategories())) {
            throw ValidationException::withMessages([
                'category' => ['Invalid product category.']
            ]);
        }

        return $this->productRepository->getByCategory($category);
    }

    
    public function getActiveProducts(): Collection
    {
        return $this->productRepository->getActive();
    }

    
    public function searchProducts(string $term): LengthAwarePaginator
    {
        return $this->productRepository->search($term);
    }

    
    private function validateFieldDefinitions(array $fieldDefinitions): void
    {
        if (!is_array($fieldDefinitions)) {
            throw ValidationException::withMessages([
                'field_definitions' => ['Field definitions must be an array.']
            ]);
        }

        $fieldNames = [];
        $validTypes = Product::getFieldTypes();

        foreach ($fieldDefinitions as $index => $field) {
            if (!is_array($field)) {
                throw ValidationException::withMessages([
                    "field_definitions.{$index}" => ['Each field definition must be an array.']
                ]);
            }

            if (!isset($field['name']) || empty($field['name'])) {
                throw ValidationException::withMessages([
                    "field_definitions.{$index}.name" => ['Field name is required.']
                ]);
            }

            if (!isset($field['type']) || empty($field['type'])) {
                throw ValidationException::withMessages([
                    "field_definitions.{$index}.type" => ['Field type is required.']
                ]);
            }

            if (!in_array($field['type'], $validTypes)) {
                throw ValidationException::withMessages([
                    "field_definitions.{$index}.type" => ['Invalid field type. Must be one of: ' . implode(', ', $validTypes)]
                ]);
            }

            if (in_array($field['name'], $fieldNames)) {
                throw ValidationException::withMessages([
                    "field_definitions.{$index}.name" => ['Duplicate field name: ' . $field['name']]
                ]);
            }
            $fieldNames[] = $field['name'];

            if ($field['type'] === 'Lookup') {
                if (!isset($field['options']) || !is_array($field['options']) || empty($field['options'])) {
                    throw ValidationException::withMessages([
                        "field_definitions.{$index}.options" => ['Lookup fields must have options array.']
                    ]);
                }
            }
        }
    }
}


