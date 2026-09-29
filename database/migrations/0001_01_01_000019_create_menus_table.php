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
        Schema::create('menus', function (Blueprint $table) {
            $table->id();
            $table->string('code');
            $table->foreignId('site_id')->constrained('sites')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('menu_items', function (Blueprint $table) {
            $table->id();
            $table->string('code');
            $table->foreignId('site_id')->constrained('sites')->cascadeOnDelete();
            $table->boolean('is_active')->default(false);
            $table->integer('position')->default(0);
            $table->boolean('is_target_blank')->default(false);
            $table->boolean('is_no_referrer')->default(false);
            $table->boolean('is_no_opener')->default(false);
            $table->boolean('is_no_follow')->default(false);
            $table->foreignId('menu_id')->constrained('menus')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('menu_items')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('menu_item_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_item_id')->constrained('menu_items')->cascadeOnDelete();
            $table->foreignId('language_id')->constrained('languages')->cascadeOnDelete();
            $table->string('name');
            $table->string('url');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['menu_item_id', 'language_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('menu_item_translations');
        Schema::dropIfExists('menu_items');
        Schema::dropIfExists('menus');
    }
};
