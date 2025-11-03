<?php

namespace Database\Factories;

use App\Models\Dashboard;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;


class DashboardFactory extends Factory
{
    protected $model = Dashboard::class;

    
    public function definition(): array
    {
        $dashboardTypes = [
            'Executive Overview',
            'Customer Analytics',
            'Product Performance',
            'Risk Analysis',
            'Branch Performance',
            'Profitability Dashboard'
        ];

        return [
            'name' => $this->faker->randomElement($dashboardTypes) . ' - ' . $this->faker->word(),
            'user_id' => User::factory(),
            'layout' => [
                'columns' => $this->faker->numberBetween(8, 16),
                'rows' => $this->faker->numberBetween(6, 12),
                'gap' => $this->faker->numberBetween(8, 24),
            ],
            'filters' => [
                'date_range' => [
                    'start' => $this->faker->optional()->dateTimeBetween('-1 year', 'now')?->format('Y-m-d'),
                    'end' => $this->faker->optional()->dateTimeBetween('now', '+1 month')?->format('Y-m-d'),
                ],
                'branch' => $this->faker->optional()->randomElement(['MAIN', 'NORTH', 'SOUTH', 'EAST', 'WEST']),
                'currency' => $this->faker->randomElement(['USD', 'EUR', 'GBP']),
                'demographic' => $this->faker->optional()->randomElement(['Manufacturing', 'Technology', 'Healthcare', 'Finance']),
                'product_type' => $this->faker->optional()->randomElement(['Loan', 'Account', 'Deposit', 'Other']),
            ],
            'description' => $this->faker->optional()->sentence(),
            'is_public' => $this->faker->boolean(30), // 30% chance of being public
            'is_active' => $this->faker->boolean(95), // 95% chance of being active
        ];
    }

    
    public function public(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_public' => true,
        ]);
    }

    
    public function private(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_public' => false,
        ]);
    }

    
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    
    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
        ]);
    }

    
    public function executive(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Executive Overview',
            'layout' => [
                'columns' => 12,
                'rows' => 8,
                'gap' => 16,
            ],
            'filters' => [
                'date_range' => ['start' => null, 'end' => null],
                'branch' => null,
                'currency' => 'USD',
                'demographic' => null,
                'product_type' => null,
            ],
            'is_public' => true,
            'description' => 'High-level executive overview of portfolio performance',
        ]);
    }

    
    public function customerAnalytics(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Customer Analytics',
            'layout' => [
                'columns' => 12,
                'rows' => 10,
                'gap' => 16,
            ],
            'filters' => [
                'date_range' => ['start' => null, 'end' => null],
                'branch' => null,
                'currency' => 'USD',
                'demographic' => null,
                'product_type' => null,
            ],
            'is_public' => false,
            'description' => 'Detailed customer profitability and risk analysis',
        ]);
    }

    
    public function withMinimalFilters(): static
    {
        return $this->state(fn (array $attributes) => [
            'filters' => [
                'date_range' => ['start' => null, 'end' => null],
                'branch' => null,
                'currency' => null,
                'demographic' => null,
                'product_type' => null,
            ],
        ]);
    }
}


