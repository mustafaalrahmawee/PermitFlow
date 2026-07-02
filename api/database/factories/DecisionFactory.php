<?php

namespace Database\Factories;

use App\Enums\DecisionOutcome;
use App\Models\Decision;
use App\Models\Request;
use App\Models\UserAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Decision>
 */
class DecisionFactory extends Factory
{
    protected $model = Decision::class;

    public function definition(): array
    {
        return [
            'request_id' => Request::factory(),
            'decided_by_user_account_id' => UserAccount::factory()->staff(),
            'outcome' => DecisionOutcome::Approved,
            'decision_text' => fake()->paragraph(),
            'decided_at' => now(),
        ];
    }

    public function rejected(): static
    {
        return $this->state(fn () => ['outcome' => DecisionOutcome::Rejected]);
    }
}
