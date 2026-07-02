<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Source: [04_data-model.md §2.1 decisions].
// Generated before documents because documents.decision_id references it.
// request_id is unique: one decision per request (the single recorded outcome).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('decisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id')
                ->unique()
                ->constrained('requests')
                ->restrictOnDelete(); // [UC-09]
            $table->foreignId('decided_by_user_account_id')
                ->constrained('user_accounts')
                ->restrictOnDelete(); // [BR-007]
            $table->string('outcome'); // allowed: approved, rejected [BR-008]
            $table->text('decision_text')->nullable();
            $table->timestamp('decided_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('decisions');
    }
};
