<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('user_currency_rates', function (Blueprint $table) {
            $table->longText('script')
                ->after('rate')
                ->nullable();

            $table->decimal('rate')
                ->nullable()
                ->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_currency_rates', function (Blueprint $table) {
            $table->dropColumn('script');

            $table->decimal('rate')
                ->nullable(false)
                ->change();
        });
    }
};
