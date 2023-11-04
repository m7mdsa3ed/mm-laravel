<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /** Run the migrations. */
    public function up(): void
    {
        $this->down();

        Schema::create('account_cards', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('account_id');
            $table->string('name', 21);
            $table->string('card_number', 16);
            $table->string('brand', 32);
            $table->string('type', 20);
            $table->string('cvv', 3);
            $table->unsignedInteger('expiration_month');
            $table->unsignedInteger('expiration_year');
            $table->timestamps();
        });
    }

    /** Reverse the migrations. */
    public function down(): void
    {
        Schema::dropIfExists('account_cards');
    }
};
