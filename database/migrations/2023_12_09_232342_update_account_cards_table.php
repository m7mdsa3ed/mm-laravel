<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('account_cards', function (Blueprint $table) {
            $table->dropColumn([
                'card_number',
                'cvv',
            ]);

            $table->string('last_4', 4)->after('type');
            $table->string('encrypted_payload', 1024)->after('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('account_cards', function (Blueprint $table) {
            $table->dropColumn('encrypted_payload');
            $table->dropColumn('last_4');
            $table->string('card_number', 16);
            $table->string('cvv', 3);
        });
    }
};
