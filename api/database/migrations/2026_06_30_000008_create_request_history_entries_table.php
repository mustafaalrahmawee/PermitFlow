<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Source: [04_data-model.md §2.1 request_history_entries, §1.2].
// Weak entity owned by requests; partial key sequence_number unique per request.
// summary is a frozen audit snapshot, written explicitly in use-case logic (§4).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('request_history_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id')
                ->constrained('requests')
                ->restrictOnDelete(); // [BR-017]
            $table->integer('sequence_number');
            $table->foreignId('actor_user_account_id')
                ->nullable()
                ->constrained('user_accounts')
                ->restrictOnDelete(); // [UC-05]
            $table->foreignId('decision_id')
                ->nullable()
                ->constrained('decisions')
                ->restrictOnDelete(); // [UC-09]
            $table->foreignId('message_id')
                ->nullable()
                ->constrained('messages')
                ->restrictOnDelete(); // [UC-07]
            $table->foreignId('document_id')
                ->nullable()
                ->constrained('documents')
                ->restrictOnDelete(); // [UC-04]
            $table->foreignId('previous_staff_user_account_id')
                ->nullable()
                ->constrained('user_accounts')
                ->restrictOnDelete(); // [UC-05]
            $table->foreignId('new_staff_user_account_id')
                ->nullable()
                ->constrained('user_accounts')
                ->restrictOnDelete(); // [UC-05]
            $table->string('event_type'); // status_changed, assignment_changed, ... [BR-017]
            $table->string('from_status')->nullable(); // [BR-004]
            $table->string('to_status')->nullable(); // [BR-004]
            $table->text('summary'); // [BR-017]
            $table->text('reason')->nullable(); // [UC-05]
            $table->timestamp('event_occurred_at');
            $table->timestamps();

            $table->unique(['request_id', 'sequence_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('request_history_entries');
    }
};
