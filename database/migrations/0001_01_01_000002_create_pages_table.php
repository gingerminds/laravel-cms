<?php

use Gingerminds\LaravelCms\State\Page\Status\Draft;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('code');
            $table->foreignId('site_id')->constrained('sites')->cascadeOnDelete();
            $table->string('status')->default(str_replace('\\', '\\\\', Draft::class));
            $table->foreignUuid('main_visual_id')->nullable()->constrained('files')->nullOnDelete();
            $table->foreignUuid('thumbnail_id')->nullable()->constrained('files')->nullOnDelete();
            $table->dateTime('published_at')->nullable();
            $table->dateTime('archived_at')->nullable();
            $table->timestamps();

            $table->unique(['code', 'site_id']);
        });

        Schema::create('page_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->constrained('pages')->cascadeOnDelete();
            $table->foreignId('language_id')->constrained('languages')->cascadeOnDelete();
            $table->string('title')->nullable();
            $table->string('slug')->nullable();
            $table->text('hook')->nullable();
            $table->json('content')->nullable();
            $table->foreignUuid('main_visual_id')->nullable()->constrained('files')->nullOnDelete();
            $table->foreignUuid('thumbnail_id')->nullable()->constrained('files')->nullOnDelete();
            $table->timestamps();

            $table->unique(['page_id', 'language_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('page_translations');
        Schema::dropIfExists('pages');
    }
};
