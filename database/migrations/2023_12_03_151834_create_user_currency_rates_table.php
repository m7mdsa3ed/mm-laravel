<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /** Run the migrations. */
    public function up(): void
    {
        Schema::create('user_currency_rates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('currency_rate_id');
            $table->decimal('rate', 20, 10);
            $table->timestamps();

            $table->unique(['user_id', 'currency_rate_id']);
        });
    }

    /** Reverse the migrations. */
    public function down(): void
    {
        Schema::dropIfExists('user_currency_rates');
    }
};
