<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('mortgage_form_submissions', function (Blueprint $table) {
            $table->id();
            $table->string('customer_name');
            $table->string('phone', 20);
            $table->string('line_id')->nullable();
            $table->string('region');
            $table->text('source_url')->nullable();
            $table->string('utm_source')->nullable();
            $table->string('utm_medium')->nullable();
            $table->string('utm_campaign')->nullable();
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->text('referrer_url')->nullable();
            $table->enum('status', ['pending', 'contacted', 'qualified', 'duplicate', 'invalid'])
                  ->default('pending');
            $table->string('assigned_to')->nullable();
            $table->timestamp('submitted_at');
            $table->timestamps();

            $table->index(['phone', 'submitted_at']);
            $table->index(['assigned_to', 'status']);
            $table->index(['status', 'submitted_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('mortgage_form_submissions');
    }
};