<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /** Run the migrations. */
    public function up(): void
    {
        $this->down();

        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('account_id');
            $table->string('name');
            $table->decimal('amount', 8, 2);
            $table->unsignedTinyInteger('interval_unit');
            $table->unsignedBigInteger('interval_count');
            $table->boolean('auto_renewal')->default(false);
            $table->boolean('can_cancel')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamp('expires_at');
            $table->timestamp('started_at');
            $table->timestamps();
        });
    }

    /** Reverse the migrations. */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
