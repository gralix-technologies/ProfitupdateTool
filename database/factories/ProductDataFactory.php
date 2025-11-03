<?php

namespace Database\Factories;

use App\Models\ProductData;
use App\Models\Product;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;


class ProductDataFactory extends Factory
{
    protected $model = ProductData::class;

    
    public function definition(): array
    {
        $product = Product::factory()->create();
        $customer = Customer::factory()->create();
        
        $amount = $this->faker->randomFloat(2, 1000, 1000000);
        $data = $this->generateDataForProduct($product, $amount);

        return [
            'product_id' => $product->id,
            'customer_id' => $customer->customer_id,
            'data' => $data,
            'amount' => $amount,
            'effective_date' => $this->faker->dateTimeBetween('-2 years', 'now'),
            'status' => $this->faker->randomElement(['active', 'inactive', 'closed', 'npl']),
        ];
    }

    
    private function generateDataForProduct(Product $product, float $amount): array
    {
        $data = [];
        $fieldDefinitions = $product->field_definitions ?? [];

        foreach ($fieldDefinitions as $field) {
            $fieldName = $field['name'];
            $fieldType = $field['type'];

            switch ($fieldType) {
                case 'Numeric':
                    if (str_contains($fieldName, 'amount') || str_contains($fieldName, 'balance') || str_contains($fieldName, 'principal')) {
                        $data[$fieldName] = $amount;
                    } elseif (str_contains($fieldName, 'rate')) {
                        $data[$fieldName] = $this->faker->randomFloat(2, 1, 25);
                    } elseif (str_contains($fieldName, 'term') || str_contains($fieldName, 'months')) {
                        $data[$fieldName] = $this->faker->numberBetween(6, 120);
                    } elseif (str_contains($fieldName, 'limit')) {
                        $data[$fieldName] = $this->faker->numberBetween(1000, 100000);
                    } elseif (str_contains($fieldName, 'fee')) {
                        $data[$fieldName] = $this->faker->numberBetween(10, 500);
                    } else {
                        $data[$fieldName] = $this->faker->randomFloat(2, 0, 10000);
                    }
                    break;

                case 'Text':
                    if (str_contains($fieldName, 'type') || str_contains($fieldName, 'collateral')) {
                        $data[$fieldName] = $this->faker->randomElement(['Real Estate', 'Vehicle', 'Equipment', 'Securities']);
                    } elseif (str_contains($fieldName, 'description')) {
                        $data[$fieldName] = $this->faker->sentence();
                    } elseif (str_contains($fieldName, 'purpose')) {
                        $data[$fieldName] = $this->faker->randomElement(['Business Expansion', 'Working Capital', 'Equipment Purchase', 'Real Estate']);
                    } else {
                        $data[$fieldName] = $this->faker->words(2, true);
                    }
                    break;

                case 'Date':
                    if (str_contains($fieldName, 'maturity')) {
                        $data[$fieldName] = $this->faker->dateTimeBetween('now', '+5 years')->format('Y-m-d');
                    } elseif (str_contains($fieldName, 'approval')) {
                        $data[$fieldName] = $this->faker->dateTimeBetween('-1 year', 'now')->format('Y-m-d');
                    } else {
                        $data[$fieldName] = $this->faker->dateTimeBetween('-1 year', '+1 year')->format('Y-m-d');
                    }
                    break;

                case 'Lookup':
                    $options = $field['options'] ?? ['Option A', 'Option B', 'Option C'];
                    $data[$fieldName] = $this->faker->randomElement($options);
                    break;

                default:
                    $data[$fieldName] = $this->faker->word();
                    break;
            }
        }

        return $data;
    }

    
    public function forProduct(Product $product): static
    {
        return $this->state(function (array $attributes) use ($product) {
            $amount = $this->faker->randomFloat(2, 1000, 1000000);
            
            return [
                'product_id' => $product->id,
                'data' => $this->generateDataForProduct($product, $amount),
                'amount' => $amount,
            ];
        });
    }

    
    public function forCustomer(Customer $customer): static
    {
        return $this->state(fn (array $attributes) => [
            'customer_id' => $customer->customer_id,
        ]);
    }

    
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }

    
    public function npl(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'npl',
        ]);
    }

    
    public function closed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'closed',
            'effective_date' => $this->faker->dateTimeBetween('-2 years', '-1 month'),
        ]);
    }

    
    public function withAmount(float $amount): static
    {
        return $this->state(fn (array $attributes) => [
            'amount' => $amount,
        ]);
    }
}


