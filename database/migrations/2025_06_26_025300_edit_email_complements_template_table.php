<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('email_complements_template', function (Blueprint $table) {
            $table->renameColumn('template_data', 'template');
            $table->renameColumn('template_id', 'id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('email_complements_template', function (Blueprint $table) {
            $table->renameColumn('template', 'template_data');
            $table->renameColumn('id', 'template_id');
        });
    }
};
