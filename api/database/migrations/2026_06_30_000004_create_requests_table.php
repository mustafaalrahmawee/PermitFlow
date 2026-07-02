<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Source: [04_data-model.md §2.1 requests].
// request_details is whole-block JSON (§3.1). status stored as varchar slug.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_user_account_id')
                ->constrained('user_accounts')
                ->restrictOnDelete(); // [BR-003]
            $table->foreignId('request_category_id')
                ->constrained('request_categories')
                ->restrictOnDelete(); // [BR-002]
            $table->foreignId('responsible_staff_user_account_id')
                ->nullable()
                ->constrained('user_accounts')
                ->restrictOnDelete(); // [BR-009]
            $table->string('title');
            $table->json('request_details');
            $table->string('status'); // allowed: draft..decided [BR-004]
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('requests');
    }
};
