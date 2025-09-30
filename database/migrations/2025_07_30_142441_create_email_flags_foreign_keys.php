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
        Schema::table('email_flags', function (Blueprint $table) {
            $table->foreign('flag_id')
                ->references('id')
                ->on('flags')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('email_flags', function (Blueprint $table) {
            $table->dropForeign(['flag_id']);
        });
    }
};
