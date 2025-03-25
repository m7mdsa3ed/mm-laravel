<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('api_responses', function (Blueprint $table) {
            $table->id();
            $table->string('url')->unique();
            $table->json('response');
            $table->string('method');
            $table->integer('status_code');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('api_responses');
    }
};
