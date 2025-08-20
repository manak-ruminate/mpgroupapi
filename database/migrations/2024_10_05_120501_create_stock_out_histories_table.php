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
    Schema::create('stock_out_histories', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('user_id'); 
        $table->unsignedBigInteger('product_id'); 
        $table->unsignedBigInteger('inventory_id'); 
        $table->string('sku_code');
        $table->string('total_qty');
        $table->string('qty');
        $table->string('batch');
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_out_histories');
    }
};
