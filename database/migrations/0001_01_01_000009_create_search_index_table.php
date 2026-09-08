<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('search_index', function (Blueprint $table) {
            $table->id();
            // No FK to a source table: `type` + `record_id` can point at a
            // package model (pages) or an app-only model (news, events,
            // products, ...) the package has no knowledge of.
            $table->string('type');
            $table->unsignedBigInteger('record_id');
            $table->foreignId('site_id')->nullable()->constrained('sites')->nullOnDelete();
            // One row per translation the record actually has (see
            // PubliclySearchableInterface::getPublicSearchTranslations()) —
            // title/url/excerpt aren't language-agnostic in a multi-language
            // CMS, so a single row per record would silently pick whichever
            // translation happened to be "current" at reindex time.
            $table->foreignId('language_id')->constrained('languages')->cascadeOnDelete();
            $table->string('title');
            $table->string('url');
            $table->text('excerpt')->nullable();
            $table->text('searchable_text');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->unique(['type', 'record_id', 'language_id']);
            $table->index(['site_id', 'language_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('search_index');
    }
};
