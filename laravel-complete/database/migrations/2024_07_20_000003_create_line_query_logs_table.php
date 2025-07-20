<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('line_query_logs', function (Blueprint $table) {
            $table->id();
            $table->string('line_user_id');
            $table->string('query_type');
            $table->json('query_params')->nullable();
            $table->timestamps();

            $table->index(['line_user_id', 'created_at']);
            $table->index(['query_type', 'created_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('line_query_logs');
    }
};