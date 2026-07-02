<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Source: [04_data-model.md §2.1 messages].
// The participant rule (owner <-> responsible staff) is enforced as a semantic
// constraint at message creation (MessagePolicy@create); the table keeps the
// historical sender and recipient [BR-011].
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id')
                ->constrained('requests')
                ->restrictOnDelete();
            $table->foreignId('sender_user_account_id')
                ->constrained('user_accounts')
                ->restrictOnDelete();
            $table->foreignId('recipient_user_account_id')
                ->constrained('user_accounts')
                ->restrictOnDelete();
            $table->string('message_kind'); // general, missing_information_request, citizen_reply
            $table->text('body');
            $table->timestamp('sent_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
