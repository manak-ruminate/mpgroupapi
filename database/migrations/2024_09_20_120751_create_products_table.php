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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('sku')->nullable();
            $table->string('size')->nullable();
            $table->string('qty')->nullable();
            $table->string('batch')->nullable();
            $table->string('color')->nullable();
            $table->string('image')->nullable();
            $table->string('brand')->nullable();
            $table->string('category_id')->nullable();
            $table->boolean('is_active')->default(true);
             $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
