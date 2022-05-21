<?php

use App\Models\Transaction;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->unsignedTinyInteger('action')->after('type');
            $table->unsignedTinyInteger('action_type')->after('action');
        });

        DB::statement('UPDATE `transactions` SET `action` = `transactions`.`type`, action_type = ( IF(`is_public` = 1, ( IF(`type` = 1, 1, 2) ), 3))');

        DB::statement('ALTER TABLE `transactions` DROP COLUMN `type`');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn(['action', 'action_type']);
        });
    }
};
