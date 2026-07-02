<?php

namespace Database\Factories;

use App\Enums\HistoryEventType;
use App\Models\Request;
use App\Models\RequestHistoryEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RequestHistoryEntry>
 */
class RequestHistoryEntryFactory extends Factory
{
    protected $model = RequestHistoryEntry::class;

    public function definition(): array
    {
        return [
            'request_id' => Request::factory(),
            'sequence_number' => 1,
            'actor_user_account_id' => null,
            'decision_id' => null,
            'message_id' => null,
            'document_id' => null,
            'previous_staff_user_account_id' => null,
            'new_staff_user_account_id' => null,
            'event_type' => HistoryEventType::StatusChanged,
            'from_status' => null,
            'to_status' => null,
            'summary' => fake()->sentence(),
            'reason' => null,
            'event_occurred_at' => now(),
        ];
    }
}
