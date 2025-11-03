<?php

namespace Database\Factories;

use App\Models\Formula;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;


class FormulaFactory extends Factory
{
    protected $model = Formula::class;

    
    public function definition(): array
    {
        $formulaTypes = [
            'calculation' => [
                'names' => ['Monthly Payment', 'Interest Calculation', 'Principal Amount', 'Total Cost'],
                'expressions' => ['amount * rate / 100', 'principal * (rate / 12)', 'balance + interest', 'amount + fees'],
                'return_type' => 'numeric'
            ],
            'comparison' => [
                'names' => ['Risk Assessment', 'Eligibility Check', 'Status Validation', 'Threshold Check'],
                'expressions' => ['IF(amount > 10000, "High", "Low")', 'balance > minimum_balance', 'status == "active"', 'score >= threshold'],
                'return_type' => 'text'
            ],
            'aggregation' => [
                'names' => ['Total Portfolio', 'Average Balance', 'Customer Count', 'Sum of Loans'],
                'expressions' => ['SUM(amount)', 'AVG(balance)', 'COUNT(customer_id)', 'SUM(loan_amount)'],
                'return_type' => 'numeric'
            ]
        ];

        $type = $this->faker->randomElement(array_keys($formulaTypes));
        $typeData = $formulaTypes[$type];

        return [
            'name' => $this->faker->randomElement($typeData['names']) . ' ' . $this->faker->numberBetween(1, 100),
            'expression' => $this->faker->randomElement($typeData['expressions']),
            'product_id' => $this->faker->boolean(70) ? Product::factory() : null, // 70% chance of being product-specific
            'parameters' => [
                'precision' => $this->faker->numberBetween(0, 4),
                'currency' => $this->faker->randomElement(['USD', 'EUR', 'GBP']),
                'format' => $this->faker->randomElement(['number', 'currency', 'percentage']),
                'description' => $this->faker->sentence()
            ],
            'description' => $this->faker->sentence(),
            'return_type' => $typeData['return_type'],
            'is_active' => $this->faker->boolean(90), // 90% chance of being active
            'created_by' => User::factory(),
        ];
    }

    
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    
    public function global(): static
    {
        return $this->state(fn (array $attributes) => [
            'product_id' => null,
        ]);
    }

    
    public function forProduct(Product $product): static
    {
        return $this->state(fn (array $attributes) => [
            'product_id' => $product->id,
        ]);
    }

    
    public function createdBy(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'created_by' => $user->id,
        ]);
    }

    
    public function numeric(): static
    {
        return $this->state(fn (array $attributes) => [
            'return_type' => 'numeric',
            'expression' => $this->faker->randomElement([
                'amount * rate / 100',
                'principal + interest',
                'balance * (1 + rate)',
                'SUM(amount)',
                'AVG(balance)'
            ]),
        ]);
    }

    
    public function text(): static
    {
        return $this->state(fn (array $attributes) => [
            'return_type' => 'text',
            'expression' => $this->faker->randomElement([
                'IF(amount > 10000, "High", "Low")',
                'CASE WHEN risk_score > 80 THEN "High Risk" ELSE "Low Risk" END',
                'status',
                'CONCAT(first_name, " ", last_name)'
            ]),
        ]);
    }

    
    public function boolean(): static
    {
        return $this->state(fn (array $attributes) => [
            'return_type' => 'boolean',
            'expression' => $this->faker->randomElement([
                'balance > minimum_balance',
                'status == "active"',
                'amount >= threshold',
                'is_eligible == true'
            ]),
        ]);
    }
}


