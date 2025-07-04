<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_queue', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('from');
            $table->jsonb('to');
            $table->jsonb('cc')->nullable();
            $table->jsonb('bcc')->nullable();
            $table->text('subject');
            $table->longText('body');
            $table->jsonb('attachments')->nullable();

            $table->string('status', 20);
            $table->timestamps();

            $table->string('batch_id');
            $table->string('external_id')->nullable();
            $table->uuid('email_id')->nullable();
            $table->uuid('flag_id')->nullable();

            $table->index('status');
            $table->index('batch_id');
            $table->index('email_id');
            $table->index('flag_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_queue');
    }
};
