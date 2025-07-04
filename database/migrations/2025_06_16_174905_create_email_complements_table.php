<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('email_complements', function (Blueprint $table) {
            $table->uuid('email_id');  // Relacionamento com a tabela emails
            $table->jsonb('complement_data');  // Dados complementares em formato JSONB
            $table->timestamps();  // created_at, updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_complements');
    }
};
