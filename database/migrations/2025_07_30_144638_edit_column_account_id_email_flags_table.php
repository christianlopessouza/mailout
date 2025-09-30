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
        Schema::table('flags', function (Blueprint $table) {
            $table->renameColumn('account_id', 'email_id');

            $table->foreign('email_id')
                ->references('id')
                ->on('emails')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('flags', function (Blueprint $table) {
            $table->dropForeign(['email_id']);
            $table->renameColumn('email_id', 'account_id');
        });
    }
};
