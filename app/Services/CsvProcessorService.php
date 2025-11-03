<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

class CsvProcessorService
{
    protected DataValidationService $dataValidationService;
    protected DataStorageService $dataStorageService;

    public function __construct(
        DataValidationService $dataValidationService,
        DataStorageService $dataStorageService
    ) {
        $this->dataValidationService = $dataValidationService;
        $this->dataStorageService = $dataStorageService;
    }

    
    public function processFile(UploadedFile $file, Product $product, string $mode = 'append'): ImportResult
    {
        try {

            $this->validateFile($file);


            $csvData = $this->parseCsvFile($file);
            
            if (empty($csvData)) {
                return new ImportResult(false, 'CSV file is empty', [], []);
            }


            $headers = array_shift($csvData);
            $headerValidation = $this->dataValidationService->validateHeaders($headers, $product);
            
            if (!$headerValidation->isValid()) {
                return new ImportResult(false, 'Header validation failed', [], $headerValidation->getErrors());
            }


            $processingResult = $this->processRows($csvData, $headers, $product);

            return new ImportResult(
                true, // Always successful if processing completed without exceptions
                $processingResult->getMessage(),
                $processingResult->getValidRows(),
                $processingResult->getErrors()
            );

        } catch (\Exception $e) {
            Log::error('CSV processing failed', [
                'file' => $file->getClientOriginalName(),
                'product_id' => $product->id,
                'error' => $e->getMessage()
            ]);

            return new ImportResult(false, 'File processing failed: ' . $e->getMessage(), [], []);
        }
    }

    
    public function processAndStoreFile(UploadedFile $file, Product $product, string $mode = 'append'): DataStorageResult
    {

        $importResult = $this->processFile($file, $product, $mode);
        
        if (!$importResult->isSuccess()) {
            return new DataStorageResult(
                false,
                $importResult->getMessage(),
                0,
                $importResult->getErrorCount(),
                ''
            );
        }


        return $this->dataStorageService->storeData(
            $importResult->getValidRows(),
            $importResult->getErrors(),
            $product,
            $mode
        );
    }


    
    public function processRows(array $rows, array $headers, Product $product): ProcessingResult
    {
        $validRows = [];
        $errors = [];
        $rowNumber = 2; // Start from row 2 (after header)

        foreach ($rows as $row) {
            try {

                if (empty(array_filter($row))) {
                    $rowNumber++;
                    continue;
                }


                if (count($row) !== count($headers)) {
                    $errors[] = "Row {$rowNumber}: Column count mismatch. Expected " . count($headers) . " columns, got " . count($row);
                    $rowNumber++;
                    continue;
                }


                $rowData = array_combine($headers, $row);
                

                $rowValidation = $this->dataValidationService->validateRow($rowData, $product);
                
                if ($rowValidation->isValid()) {
                    $validRows[] = $rowData;
                } else {
                    foreach ($rowValidation->getErrors() as $error) {
                        $errors[] = "Row {$rowNumber}: {$error}";
                    }
                }

            } catch (\Exception $e) {
                $errors[] = "Row {$rowNumber}: Processing error - {$e->getMessage()}";
            }

            $rowNumber++;
        }

        $message = count($validRows) > 0 
            ? "Processed " . count($validRows) . " valid rows with " . count($errors) . " errors"
            : "No valid rows found";

        return new ProcessingResult($validRows, $errors, $message);
    }

    
    protected function validateFile(UploadedFile $file): void
    {

        if (!in_array(strtolower($file->getClientOriginalExtension()), ['csv', 'txt'])) {
            throw new \InvalidArgumentException('File must be a CSV file');
        }


        $maxSize = 200 * 1024 * 1024; // 200MB in bytes
        if ($file->getSize() > $maxSize) {
            throw new \InvalidArgumentException('File size exceeds 200MB limit');
        }


        if (!$file->isValid()) {
            throw new \InvalidArgumentException('Invalid file upload');
        }
    }

    
    protected function parseCsvFile(UploadedFile $file): array
    {
        $csvData = [];
        $handle = fopen($file->getPathname(), 'r');

        if ($handle === false) {
            throw new \RuntimeException('Unable to open CSV file');
        }

        try {
            while (($row = fgetcsv($handle)) !== false) {
                $csvData[] = $row;
            }
        } finally {
            fclose($handle);
        }

        return $csvData;
    }
}



