<?php

namespace Database\Factories;

use App\Enums\MessageKind;
use App\Models\Message;
use App\Models\Request;
use App\Models\UserAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Message>
 */
class MessageFactory extends Factory
{
    protected $model = Message::class;

    public function definition(): array
    {
        return [
            'request_id' => Request::factory(),
            'sender_user_account_id' => UserAccount::factory(),
            'recipient_user_account_id' => UserAccount::factory(),
            'message_kind' => MessageKind::General,
            'body' => fake()->paragraph(),
            'sent_at' => now(),
        ];
    }

    public function missingInformationRequest(): static
    {
        return $this->state(fn () => ['message_kind' => MessageKind::MissingInformationRequest]);
    }

    public function citizenReply(): static
    {
        return $this->state(fn () => ['message_kind' => MessageKind::CitizenReply]);
    }
}
