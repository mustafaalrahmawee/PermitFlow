<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Source: [04_data-model.md §2.1 user_accounts].
// role/account_state stored as varchar slugs (string-backed enum casts, .claude/skills/foundation §4).
// password + remember_token are implementation-only columns for local login (.claude/skills/foundation §5.10).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('display_name');
            $table->string('email')->unique();
            $table->string('role'); // allowed: citizen, staff_member, administrator [BR-001]
            $table->string('account_state'); // allowed: active, inactive [UC-01]
            $table->string('password'); // implementation-only (.claude/skills/foundation §5.10)
            $table->string('remember_token')->nullable(); // implementation-only (.claude/skills/foundation §5.10)
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_accounts');
    }
};
