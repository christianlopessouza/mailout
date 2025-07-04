<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('email_search_tokens', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->uuid('email_id');
            $table->index('email_id');

            $table->text('value');

            $table->string('type');
            $table->index('type');

            $table->text('vector_value')->nullable();

        });

        DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');

        DB::statement('
            ALTER TABLE email_search_tokens
            ALTER COLUMN vector_value
            TYPE tsvector
            USING vector_value::tsvector');

        DB::statement("
            CREATE INDEX tokens_body_trgm
            ON email_search_tokens
            USING GIN (value gin_trgm_ops)
            WHERE type = 'body'
        ");

        DB::statement("
            CREATE INDEX tokens_body_vector_idx
            ON email_search_tokens
            USING GIN (vector_value)
            WHERE type = 'body'
        ");

        DB::statement("
            CREATE OR REPLACE FUNCTION update_vector_value()
            RETURNS trigger AS $$
            BEGIN
            IF NEW.type = 'body' OR NEW.type = 'subject' THEN
                NEW.vector_value := to_tsvector('portuguese', NEW.value);
            ELSE
                NEW.vector_value := NULL;
            END IF;

            RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;
        ");

        DB::statement("
            CREATE TRIGGER trigger_vector_value
            BEFORE INSERT OR UPDATE ON email_search_tokens
            FOR EACH ROW
            EXECUTE FUNCTION update_vector_value();
        ");
    }



    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_search_tokens');
    }
};
