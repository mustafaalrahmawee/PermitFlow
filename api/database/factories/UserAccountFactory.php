<?php

namespace Database\Factories;

use App\Enums\AccountState;
use App\Enums\Role;
use App\Models\UserAccount;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<UserAccount>
 */
class UserAccountFactory extends Factory
{
    protected $model = UserAccount::class;

    public function definition(): array
    {
        return [
            'display_name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'role' => Role::Citizen,
            'account_state' => AccountState::Active,
            'password' => Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    public function administrator(): static
    {
        return $this->state(fn () => ['role' => Role::Administrator]);
    }

    public function staff(): static
    {
        return $this->state(fn () => ['role' => Role::StaffMember]);
    }

    public function citizen(): static
    {
        return $this->state(fn () => ['role' => Role::Citizen]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['account_state' => AccountState::Inactive]);
    }
}
