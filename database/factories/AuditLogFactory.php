<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;


class AuditLogFactory extends Factory
{
    
    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'user_email' => $this->faker->email(),
            'action' => $this->faker->randomElement(['created', 'updated', 'deleted', 'auth.login', 'auth.logout']),
            'model' => $this->faker->randomElement(['App\Models\Product', 'App\Models\Customer', 'App\Models\Dashboard']),
            'model_id' => $this->faker->numberBetween(1, 100),
            'old_values' => $this->faker->randomElement([null, ['name' => 'Old Name', 'status' => 'inactive']]),
            'new_values' => ['name' => $this->faker->words(2, true), 'status' => 'active'],
            'ip_address' => $this->faker->ipv4(),
            'user_agent' => $this->faker->userAgent(),
            'url' => $this->faker->url(),
            'method' => $this->faker->randomElement(['GET', 'POST', 'PUT', 'DELETE']),
        ];
    }
}



