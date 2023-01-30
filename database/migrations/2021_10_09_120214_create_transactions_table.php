<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();

            $table->tinyInteger('type');
            $table->unsignedDecimal('amount');
            $table->text('description')->nullable();
            $table->json('details')->nullable();

            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('account_id')->nullable();
            $table->unsignedBigInteger('category_id')->nullable();

            $table->foreign('user_id')->on('users')->references('id')->onDelete('set null');
            $table->foreign('account_id')->on('accounts')->references('id')->onDelete('set null');
            $table->foreign('category_id')->on('categories')->references('id')->onDelete('set null');

            $table->tinyInteger('is_public')->default(1);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('transactions');
    }
};
