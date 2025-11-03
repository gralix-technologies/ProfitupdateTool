<?php

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;


class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    
    public function definition(): array
    {
        $industries = ['Manufacturing', 'Technology', 'Healthcare', 'Finance', 'Retail', 'Services', 'Construction', 'Education'];
        $branches = ['MAIN', 'NORTH', 'SOUTH', 'EAST', 'WEST', 'CENTRAL'];
        $riskLevels = ['Low', 'Medium', 'High'];

        $totalLoans = $this->faker->randomFloat(2, 0, 2000000);
        $totalDeposits = $this->faker->randomFloat(2, 0, 500000);
        $nplExposure = $totalLoans > 0 ? $this->faker->randomFloat(2, 0, $totalLoans * 0.1) : 0;

        $interestEarned = $totalLoans * 0.08; // 8% average
        $interestPaid = $totalDeposits * 0.02; // 2% average
        $costs = $this->faker->randomFloat(2, 1000, 10000);
        $profitability = $interestEarned - $interestPaid - $costs;

        return [
            'customer_id' => 'CUST' . str_pad($this->faker->unique()->numberBetween(1, 99999), 5, '0', STR_PAD_LEFT),
            'name' => $this->faker->company(),
            'email' => $this->faker->unique()->companyEmail(),
            'phone' => $this->faker->phoneNumber(),
            'demographics' => [
                'industry' => $this->faker->randomElement($industries),
                'employees' => $this->faker->numberBetween(10, 1000),
                'annual_revenue' => $this->faker->numberBetween(500000, 50000000),
                'established_year' => $this->faker->numberBetween(1990, 2020),
            ],
            'branch_code' => $this->faker->randomElement($branches),
            'total_loans_outstanding' => $totalLoans,
            'total_deposits' => $totalDeposits,
            'npl_exposure' => $nplExposure,
            'profitability' => $profitability,
            'risk_level' => $this->faker->randomElement($riskLevels),
            'is_active' => $this->faker->boolean(95), // 95% chance of being active
        ];
    }

    
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    
    public function highRisk(): static
    {
        return $this->state(function (array $attributes) {
            $totalLoans = $this->faker->randomFloat(2, 100000, 1000000);
            $nplExposure = $totalLoans * $this->faker->randomFloat(2, 0.1, 0.3); // 10-30% NPL
            
            return [
                'total_loans_outstanding' => $totalLoans,
                'npl_exposure' => $nplExposure,
                'risk_level' => 'High',
                'profitability' => $this->faker->randomFloat(2, -50000, 10000), // Often unprofitable
            ];
        });
    }

    
    public function lowRisk(): static
    {
        return $this->state(function (array $attributes) {
            $totalLoans = $this->faker->randomFloat(2, 50000, 2000000);
            $nplExposure = $totalLoans * $this->faker->randomFloat(2, 0, 0.02); // 0-2% NPL
            
            return [
                'total_loans_outstanding' => $totalLoans,
                'npl_exposure' => $nplExposure,
                'risk_level' => 'Low',
                'profitability' => $this->faker->randomFloat(2, 10000, 100000), // Generally profitable
            ];
        });
    }

    
    public function fromBranch(string $branchCode): static
    {
        return $this->state(fn (array $attributes) => [
            'branch_code' => $branchCode,
        ]);
    }

    
    public function inIndustry(string $industry): static
    {
        return $this->state(fn (array $attributes) => [
            'demographics' => array_merge(
                $attributes['demographics'] ?? [],
                ['industry' => $industry]
            ),
        ]);
    }
}


