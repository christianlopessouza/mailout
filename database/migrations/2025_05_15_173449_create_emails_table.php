<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('emails', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('account_id');
            $table->string('from');
            $table->jsonb('to');
            $table->jsonb('cc')->nullable();
            $table->jsonb('bcc')->nullable();
            $table->text('subject');
            $table->longText('body');
            $table->jsonb('attachments')->nullable();

            $table->string('direction', 20);
            $table->boolean('read');
            $table->timestamp('read_at')->nullable();

            $table->uuid('folder_id');
            $table->uuid('thread_id');
            $table->string('origin', 20);

            $table->timestamp('processed_at');
            $table->timestamps();

            $table->index('account_id');
            $table->index('folder_id');
            $table->index('thread_id');
        });

        // DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');

        // DB::statement("
        //     CREATE INDEX emails_subject_trgm_idx
        //     ON emails USING GIN (subject gin_trgm_ops)
        // ");

        // DB::statement("
        //     CREATE INDEX emails_body_trgm_idx
        //     ON emails USING GIN (body gin_trgm_ops)
        // ");
    }

    public function down(): void
    {
        Schema::dropIfExists('emails');
    }
};
