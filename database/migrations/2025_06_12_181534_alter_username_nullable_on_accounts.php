<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            // Se a coluna não existir, vai criá-la nullable.
            // Se ela existir e você quiser alterá-la, use change()
            if (!Schema::hasColumn('accounts', 'username')) {
                $table->string('username')->nullable();
            } else {
                $table->string('username')->nullable()->change();
            }
        });
    }

    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            // volta para NOT NULL se precisar
            $table->string('username')->nullable(false)->change();
        });
    }
};
