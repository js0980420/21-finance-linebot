<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('sales_line_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('sales_code')->unique();
            $table->string('sales_name');
            $table->string('line_user_id')->unique();
            $table->boolean('is_manager')->default(false);
            $table->json('responsible_regions');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('line_user_id');
            $table->index(['is_active', 'sales_code']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('sales_line_accounts');
    }
};