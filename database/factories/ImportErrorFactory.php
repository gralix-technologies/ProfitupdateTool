<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;


class ImportErrorFactory extends Factory
{
    
    public function definition(): array
    {
        return [
            'import_session_id' => $this->faker->uuid(),
            'product_id' => \App\Models\Product::factory(),
            'row_number' => $this->faker->numberBetween(1, 1000),
            'error_type' => $this->faker->randomElement([
                \App\Models\ImportError::TYPE_VALIDATION,
                \App\Models\ImportError::TYPE_PROCESSING,
                \App\Models\ImportError::TYPE_SYSTEM
            ]),
            'error_message' => $this->faker->sentence(),
            'row_data' => [
                'customer_id' => $this->faker->regexify('CUST[0-9]{3}'),
                'amount' => $this->faker->randomFloat(2, 1000, 100000),
                'field1' => $this->faker->word(),
                'field2' => $this->faker->numberBetween(1, 100)
            ],
            'context' => [
                'field' => $this->faker->word(),
                'expected_type' => $this->faker->randomElement(['string', 'numeric', 'date']),
                'received_value' => $this->faker->word()
            ]
        ];
    }
}



