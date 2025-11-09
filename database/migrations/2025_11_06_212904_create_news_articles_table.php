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
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->string('external_id');
            $table->foreignId('source_id')->constrained('article_sources')->onDelete('cascade');
            $table->foreignId('category_id')->nullable()->constrained()->onDelete('set null');
            $table->string('source_category_original')->nullable();
            $table->string('title', 500);
            $table->string('slug', 550);
            $table->text('description')->nullable();
            $table->text('content')->nullable();
            $table->text('url');
            $table->text('image_url')->nullable();
            $table->string('author')->nullable();
            $table->string('article_source')->nullable();
            $table->jsonb('keywords')->nullable();

            $table->timestamp('published_at');
            $table->timestamp('fetched_at')->useCurrent();

            $table->jsonb('raw_data')->nullable();
            $table->jsonb('metadata')->nullable();

            $table->timestamps();

            $table->unique(['external_id', 'source_id']);
            $table->index('published_at');
            $table->index(['source_id', 'published_at']);
        });

        DB::statement('ALTER TABLE articles ADD COLUMN search_vector tsvector');

        // 2. THEN: Create the GIN index
        DB::statement('CREATE INDEX articles_search_vector_idx ON articles USING GIN (search_vector)');

        // 3. Create the update function (removed 'category' since you don't have that column)
        DB::statement("
            CREATE OR REPLACE FUNCTION articles_search_vector_update() RETURNS trigger AS $$
            BEGIN
                NEW.search_vector :=
                    setweight(to_tsvector('english', COALESCE(NEW.title, '')), 'A') ||
                    setweight(to_tsvector('english', COALESCE(NEW.description, '')), 'B') ||
                    setweight(to_tsvector('english', COALESCE(NEW.content, '')), 'B') ||
                    setweight(to_tsvector('english', COALESCE(NEW.author, '')), 'C');
                RETURN NEW;
            END
            $$ LANGUAGE plpgsql;
        ");

        // 4. Create the trigger
        DB::statement("
            CREATE TRIGGER articles_search_vector_trigger
            BEFORE INSERT OR UPDATE ON articles
            FOR EACH ROW EXECUTE FUNCTION articles_search_vector_update();
        ");
    }

    public function down(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS articles_search_vector_trigger ON articles');
        DB::statement('DROP FUNCTION IF EXISTS articles_search_vector_update()');
        DB::statement('DROP INDEX IF EXISTS articles_search_vector_idx');

        Schema::dropIfExists('articles');
    }
};
