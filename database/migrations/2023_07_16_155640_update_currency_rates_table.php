<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /** Run the migrations. */
    public function up(): void
    {
        Schema::table('currency_rates', function (Blueprint $table) {
            $table->unique(['from_currency_id', 'to_currency_id']);
        });
    }

    /** Reverse the migrations. */
    public function down(): void
    {
        Schema::table('currency_rates', function (Blueprint $table) {
            $table->dropUnique(['from_currency_id', 'to_currency_id']);
        });
    }
};
