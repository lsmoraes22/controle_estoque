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
        Schema::create('stock', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('reception_id');
            $table->string('product_id',20);
            $table->unsignedBigInteger('supplier_id');
            $table->date('fabrication');
            $table->date('validity');
            $table->string('batch', 30)->nullable();
            $table->boolean('immobilized')->comment('immobilized true or false');
            $table->string('immob_code', 3)->comment('Immobilization reason code');
            $table->enum('status', ['to store', 'stored', 'reapro', 'homogeneo', 'picking']); 
            $table->integer('quantity');
            $table->string('warehouse');
            $table->smallInteger('hall')->comment('hall');
            $table->smallInteger('position')->comment('position');
            $table->tinyInteger('level')->comment('level');
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->timestamp('created_at')->useCurrent();

            // Chaves estrangeiras
            $table->foreign('reception_id')->references('row')->on('reception_body');
            $table->foreign('product_id')->references('id')->on('products');
            $table->foreign('supplier_id')->references('id')->on('suppliers');
            $table->foreign('immob_code')->references('immob_code')->on('immobilizations');
            $table->foreign('warehouse')->references('warehouse')->on('structures');
            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stocks');
    }
};
