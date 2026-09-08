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
        Schema::table('pages', function (Blueprint $table) {
            $table->boolean('is_hidden_from_search')->default(false)->after('archived_at');
        });

        Schema::table('page_categories', function (Blueprint $table) {
            $table->boolean('is_hidden_from_search')->default(false)->after('is_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropColumn('is_hidden_from_search');
        });

        Schema::table('page_categories', function (Blueprint $table) {
            $table->dropColumn('is_hidden_from_search');
        });
    }
};
