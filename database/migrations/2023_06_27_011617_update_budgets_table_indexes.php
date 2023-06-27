<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /** Run the migrations. */
    public function up(): void
    {
        Schema::table('budgets', function (Blueprint $table) {
            $table->index('user_id');

            $table->index('category_id');
        });

        Schema::table('budget_categories', function (Blueprint $table) {
            $table->index(['budget_id', 'category_id']);
        });
    }

    /** Reverse the migrations. */
    public function down(): void
    {
        Schema::table('budgets', function (Blueprint $table) {
            $table->dropIndex(['user_id']);

            $table->dropIndex(['category_id']);
        });

        Schema::table('budget_categories', function (Blueprint $table) {
            $table->dropIndex(['budget_id', 'category_id']);
        });
    }
};
