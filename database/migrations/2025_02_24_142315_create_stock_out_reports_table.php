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
        Schema::create('stock_out_reports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id'); 
            $table->unsignedBigInteger('product_id'); 
            $table->unsignedBigInteger('stock_report_id'); 
            $table->unsignedBigInteger('inventory_id');
            $table->string('sku_code');
            $table->string('total_qty')->nullable();
            $table->string('qty')->nullable();
            $table->string('batch')->nullable();
            $table->integer('previous_qty')->nullable();
            $table->integer('current_qty')->nullable();

            $table->string('remarks')->nullable();
            $table->string('product_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_out_reports');
    }
};
