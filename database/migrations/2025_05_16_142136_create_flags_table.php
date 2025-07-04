<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flags', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->uuid('account_id')->nullable();
            $table->uuid('client_id')->nullable();
            $table->timestamps();

            $table->index('account_id');
            $table->index('client_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flags');
    }
};
