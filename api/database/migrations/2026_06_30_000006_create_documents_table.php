<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Source: [04_data-model.md §2.1 documents].
// file_reference holds the S3/MinIO object key, not the bytes (§5.8).
// decision_id is unique on non-null (one decision document per decision); NULLs
// stay distinct under a unique index on Postgres/MySQL.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id')
                ->constrained('requests')
                ->restrictOnDelete();
            $table->foreignId('uploaded_by_user_account_id')
                ->constrained('user_accounts')
                ->restrictOnDelete(); // [UC-04]
            $table->foreignId('decision_id')
                ->nullable()
                ->unique()
                ->constrained('decisions')
                ->restrictOnDelete(); // [UC-09]
            $table->string('kind'); // allowed: supporting, decision [BR-006]
            $table->string('file_reference');
            $table->string('original_filename');
            $table->string('mime_type');
            $table->bigInteger('size_bytes');
            $table->timestamp('uploaded_at');
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
