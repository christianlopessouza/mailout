<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('emails', function (Blueprint $table) {
            $table->boolean('read_temp')->default(false);
        });
        DB::table('emails')
            ->whereNull('read')
            ->update(['read_temp' => false]);
        DB::table('emails')
            ->where('read', '=', '1')
            ->update(['read_temp' => true]);
        Schema::table('emails', function (Blueprint $table) {
            $table->dropColumn('read');
            $table->renameColumn('read_temp', 'read');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('emails', function (Blueprint $table) {
            $table->string('read_temp')->nullable();
        });
        DB::table('emails')
            ->where('read', '=', true)
            ->update(['read_temp' => '1']);
        DB::table('emails')
            ->where('read', '=', false)
            ->update(['read_temp' => null]);
        Schema::table('emails', function (Blueprint $table) {
            $table->dropColumn('read');
            $table->renameColumn('read_temp', 'read');
        });
    }
};
