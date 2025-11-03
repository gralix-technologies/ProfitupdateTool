<?php

namespace App\Http\Requests;

use App\Services\InputSanitizationService;

class FileUploadRequest extends SecureFormRequest
{
    
    public function authorize(): bool
    {
        return auth()->check();
    }

    
    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'mimes:csv,txt',
                'max:204800', // 200MB in KB
            ],
            'product_id' => [
                'required',
                'integer',
                'exists:products,id'
            ],
            'mode' => [
                'sometimes',
                'string',
                'in:append,overwrite'
            ]
        ];
    }

    
    public function messages(): array
    {
        return [
            'file.required' => 'Please select a file to upload.',
            'file.file' => 'The uploaded file is not valid.',
            'file.mimes' => 'The file must be a CSV file.',
            'file.max' => 'The file size must not exceed 200MB.',
            'product_id.required' => 'Please select a product.',
            'product_id.exists' => 'The selected product does not exist.',
            'mode.in' => 'Import mode must be either "append" or "overwrite".'
        ];
    }

    
    public function attributes(): array
    {
        return [
            'product_id' => 'product',
            'mode' => 'import mode'
        ];
    }

    
    public function withValidator($validator): void
    {
        parent::withValidator($validator);
        
        $validator->after(function ($validator) {
            if ($this->hasFile('file')) {
                $file = $this->file('file');
                
                if ($file->getSize() === 0) {
                    $validator->errors()->add('file', 'The uploaded file is empty.');
                }

                if (!$file->isValid()) {
                    $validator->errors()->add('file', 'The uploaded file is corrupted or invalid.');
                }

                $sanitizer = app(InputSanitizationService::class);
                $originalName = $file->getClientOriginalName();
                $sanitizedName = $sanitizer->sanitizeFileName($originalName);
                
                if ($originalName !== $sanitizedName) {
                    $validator->errors()->add('file', 'The filename contains invalid characters.');
                }

                if ($file->isValid()) {
                    $handle = @fopen($file->getPathname(), 'r');
                    if ($handle === false) {
                        $validator->errors()->add('file', 'Unable to read the uploaded file.');
                    } else {
                        $firstLine = @fgets($handle);
                        if ($firstLine === false) {
                            $validator->errors()->add('file', 'The uploaded file appears to be empty or corrupted.');
                        }
                        fclose($handle);
                    }
                }
            }
        });
    }

    
    protected function getFieldTypes(): array
    {
        return [
            'product_id' => 'number',
            'mode' => 'string',
        ];
    }
}


