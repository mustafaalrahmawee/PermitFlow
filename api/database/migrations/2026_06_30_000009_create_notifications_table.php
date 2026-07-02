<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Source: [04_data-model.md §2.1 notifications].
// In-portal only: no external-delivery columns in v1.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recipient_user_account_id')
                ->constrained('user_accounts')
                ->restrictOnDelete();
            $table->foreignId('request_id')
                ->constrained('requests')
                ->restrictOnDelete();
            $table->foreignId('request_history_entry_id')
                ->nullable()
                ->constrained('request_history_entries')
                ->restrictOnDelete(); // [BR-017]
            $table->string('notification_type'); // request_submitted, assigned, ...
            $table->text('body');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
