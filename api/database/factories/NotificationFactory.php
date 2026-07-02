<?php

namespace Database\Factories;

use App\Enums\NotificationType;
use App\Models\Notification;
use App\Models\Request;
use App\Models\UserAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Notification>
 */
class NotificationFactory extends Factory
{
    protected $model = Notification::class;

    public function definition(): array
    {
        return [
            'recipient_user_account_id' => UserAccount::factory(),
            'request_id' => Request::factory(),
            'request_history_entry_id' => null,
            'notification_type' => NotificationType::RequestSubmitted,
            'body' => fake()->sentence(),
            'read_at' => null,
        ];
    }
}
