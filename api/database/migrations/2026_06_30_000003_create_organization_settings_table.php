<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Source: [04_data-model.md §2.1 organization_settings].
// settings_payload is whole-block JSON (§3.1). Every FK restricts on delete (§4).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organization_settings', function (Blueprint $table) {
            $table->id();
            $table->string('singleton_key')->unique(); // fixed v1 value: single_organization
            $table->string('organization_name');
            $table->json('settings_payload');
            $table->foreignId('updated_by_user_account_id')
                ->nullable()
                ->constrained('user_accounts')
                ->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_settings');
    }
};
