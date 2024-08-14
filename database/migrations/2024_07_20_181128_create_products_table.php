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
            $table->string('id', 20)->primary(); 
            $table->string('description')->comment('Description of the product');
            $table->decimal('sale_price', 8, 2)->comment('Sale price of the product')->nullable(true); 
            $table->decimal('purchase_price', 8, 2)->comment('Purchase price of the product')->nullable(true); 
            $table->unsignedBigInteger('category_id')->comment('Category ID of the product');
            $table->foreign('category_id')->references('id')->on('categories');
            $table->string('unit', 4)->comment('Type unit of the product');
            $table->integer('unit_per_box')->comment('Unit per box of the product');
            $table->smallInteger('box_ballast')->comment('Box per product pallet ballast');
            $table->smallInteger('ballast_per_layer')->comment('Ballast per product pallet layer');
            $table->decimal('box_weight', 5, 3)->comment('Box weight')->nullable(true); // Ajuste na precisão para peso
            $table->smallInteger('shelflife')->comment('Days between manufacturing and expiration date');
            $table->unsignedBigInteger('supplier_default_id')->comment('Supplier default ID');
            $table->foreign('supplier_default_id')->references('id')->on('suppliers');
            $table->string('abc_curve', 1)->comment('ABC curve')->nullable(true);
            $table->boolean('enabled')->default(true)->comment('Enable');
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->timestamp('created_at')->useCurrent();
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
