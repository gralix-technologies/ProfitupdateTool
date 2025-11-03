<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;


class ProductFactory extends Factory
{
    protected $model = Product::class;

    
    public function definition(): array
    {
        $categories = ['Loan', 'Account', 'Deposit', 'Transaction', 'Other'];
        $category = $this->faker->randomElement($categories);
        
        $fieldDefinitions = $this->getFieldDefinitionsForCategory($category);

        return [
            'name' => $this->faker->unique()->words(2, true) . ' ' . $category,
            'category' => $category,
            'description' => $this->faker->sentence(),
            'field_definitions' => $fieldDefinitions,
            'is_active' => $this->faker->boolean(90), // 90% chance of being active
        ];
    }

    
    private function getFieldDefinitionsForCategory(string $category): array
    {
        switch ($category) {
            case 'Loan':
                return [
                    ['name' => 'loan_amount', 'type' => 'Numeric', 'required' => true],
                    ['name' => 'interest_rate', 'type' => 'Numeric', 'required' => true],
                    ['name' => 'term_months', 'type' => 'Numeric', 'required' => true],
                    ['name' => 'collateral_type', 'type' => 'Text', 'required' => false],
                    ['name' => 'approval_date', 'type' => 'Date', 'required' => true],
                ];
            case 'Account':
                return [
                    ['name' => 'balance', 'type' => 'Numeric', 'required' => true],
                    ['name' => 'interest_rate', 'type' => 'Numeric', 'required' => true],
                    ['name' => 'account_type', 'type' => 'Lookup', 'options' => ['Standard', 'Premium', 'VIP'], 'required' => true],
                    ['name' => 'minimum_balance', 'type' => 'Numeric', 'required' => true],
                ];
            case 'Deposit':
                return [
                    ['name' => 'principal_amount', 'type' => 'Numeric', 'required' => true],
                    ['name' => 'interest_rate', 'type' => 'Numeric', 'required' => true],
                    ['name' => 'term_months', 'type' => 'Numeric', 'required' => true],
                    ['name' => 'maturity_date', 'type' => 'Date', 'required' => true],
                    ['name' => 'auto_renewal', 'type' => 'Lookup', 'options' => ['Yes', 'No'], 'required' => true],
                ];
            case 'Transaction':
                return [
                    ['name' => 'transaction_amount', 'type' => 'Numeric', 'required' => true],
                    ['name' => 'transaction_type', 'type' => 'Lookup', 'options' => ['Debit', 'Credit'], 'required' => true],
                    ['name' => 'transaction_date', 'type' => 'Date', 'required' => true],
                    ['name' => 'description', 'type' => 'Text', 'required' => false],
                ];
            default: // Other
                return [
                    ['name' => 'amount', 'type' => 'Numeric', 'required' => true],
                    ['name' => 'description', 'type' => 'Text', 'required' => false],
                    ['name' => 'effective_date', 'type' => 'Date', 'required' => true],
                ];
        }
    }

    
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    
    public function loan(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => 'Loan',
            'name' => $this->faker->unique()->words(2, true) . ' Loan',
            'field_definitions' => $this->getFieldDefinitionsForCategory('Loan'),
        ]);
    }

    
    public function account(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => 'Account',
            'name' => $this->faker->unique()->words(2, true) . ' Account',
            'field_definitions' => $this->getFieldDefinitionsForCategory('Account'),
        ]);
    }
}


