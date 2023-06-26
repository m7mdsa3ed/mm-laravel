<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /** Run the migrations. */
    public function up(): void
    {
        Schema::create('budget_categories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('budget_id');
            $table->unsignedBigInteger('category_id');
            $table->timestamps();
        });

        Schema::table('budgets', function (Blueprint $table) {
            $table->dropColumn('category_id');
        });
    }

    /** Reverse the migrations. */
    public function down(): void
    {
        Schema::dropIfExists('budget_categories');

        Schema::table('budgets', function (Blueprint $table) {
            $table->unsignedBigInteger('category_id');
        });
    }
};
