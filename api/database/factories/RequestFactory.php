<?php

namespace Database\Factories;

use App\Enums\RequestStatus;
use App\Models\Request;
use App\Models\RequestCategory;
use App\Models\UserAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Request>
 */
class RequestFactory extends Factory
{
    protected $model = Request::class;

    public function definition(): array
    {
        return [
            'owner_user_account_id' => UserAccount::factory()->citizen(),
            'request_category_id' => RequestCategory::factory(),
            'responsible_staff_user_account_id' => null,
            'title' => fake()->sentence(4),
            'request_details' => [
                'summary' => fake()->paragraph(),
                'address' => fake()->address(),
            ],
            'status' => RequestStatus::Draft,
            'submitted_at' => null,
        ];
    }
}
