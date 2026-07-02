<?php

namespace Database\Factories;

use App\Enums\DocumentKind;
use App\Models\Document;
use App\Models\Request;
use App\Models\UserAccount;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Document>
 */
class DocumentFactory extends Factory
{
    protected $model = Document::class;

    public function definition(): array
    {
        return [
            'request_id' => Request::factory(),
            'uploaded_by_user_account_id' => UserAccount::factory(),
            'decision_id' => null,
            'kind' => DocumentKind::Supporting,
            'file_reference' => 'documents/'.Str::uuid()->toString().'.pdf',
            'original_filename' => fake()->word().'.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => fake()->numberBetween(1024, 5_000_000),
            'uploaded_at' => now(),
            'description' => fake()->optional()->sentence(),
        ];
    }

    public function decision(): static
    {
        return $this->state(fn () => ['kind' => DocumentKind::Decision]);
    }
}
