<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            ALTER TABLE emails
            ALTER COLUMN attachments
            TYPE boolean
            USING (
                CASE
                    WHEN attachments::jsonb = '[]' THEN false
                    WHEN attachments IS NULL THEN false
                    ELSE true
                END
            )
        ");
    }

    public function down(): void
    {
        DB::statement("
            ALTER TABLE emails
            ALTER COLUMN attachments
            TYPE jsonb
            USING to_jsonb(attachments)
        ");
    }
};
