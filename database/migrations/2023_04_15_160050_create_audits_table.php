<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /** Run the migrations. */
    public function up(): void
    {
        Schema::create('audits', function (Blueprint $table) {
            $table->id();
            $table->string('action')->index();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('table_name')->index();
            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->ipAddress('ip')->nullable();
            $table->longText('url')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
        });
    }

    /** Reverse the migrations. */
    public function down(): void
    {
        Schema::dropIfExists('audits');
    }
};
