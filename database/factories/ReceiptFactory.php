<?php

namespace Database\Factories;

use App\Enums\ReceiptSource;
use App\Enums\ReceiptStatus;
use App\Models\Receipt;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Receipt>
 */
class ReceiptFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'status' => ReceiptStatus::PENDING,
            'source' => ReceiptSource::MANUAL,
            'sum' => fake()->randomFloat(2, 10, 1000),
            'dt' => fake()->dateTimeBetween('-30 days', 'now')->format('Y-m-d H:i:s'),
            'fn' => fake()->numerify('################'),
            'fd' => fake()->numerify('#####'),
            'fp' => fake()->numerify('##########'),
        ];
    }
}
