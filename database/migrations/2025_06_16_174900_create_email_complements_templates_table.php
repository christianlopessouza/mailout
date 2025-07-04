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
        Schema::create('email_complements_template', function (Blueprint $table) {
            $table->uuid('client_id'); // Relacionamento com a tabela clients
            $table->uuid('template_id'); // ID do template de e-mail
            $table->jsonb('template_data'); // Dados do template em formato JSONB
            $table->timestamps(); // created_at, updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_complements_template');
    }
};
