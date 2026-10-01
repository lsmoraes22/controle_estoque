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
            $table->unsignedBigInteger('supplier_id')->nullable(true);
            $table->date('fabrication')->nullable(true);
            $table->date('validity')->nullable(true);
            $table->string('batch', 30)->nullable(true);
            $table->boolean('immobilized')->nullable(false)->comment('immobilized true or false');
            $table->string('immob_code', 3)->nullable(true)->comment('Immobilization reason code');
            $table->enum('status', ['to store', 'stored', 'reapro', 'homogeneo', 'picking']); 
            $table->integer('quantity')->nullable(false);
            $table->string('warehouse')->nullable(false);
            $table->smallInteger('hall')->comment('hall')->nullable(false);
            $table->smallInteger('position')->comment('position')->nullable(false);
            $table->tinyInteger('level')->comment('level')->nullable(false);
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->timestamp('created_at')->useCurrent();

            // Chaves estrangeiras
            $table->foreign('reception_id')->references('row')->on('reception_body');
            $table->foreign('product_id')->references('id')->on('products');
            $table->foreign('supplier_id')->references('id')->on('suppliers');
            $table->foreign('immob_code')->references('immob_code')->on('immobilizations');
            $table->foreign(['warehouse', 'hall', 'position', 'level'])
                ->references(['warehouse', 'hall', 'position', 'level'])->on('structures');
            
        });

        Schema::table('reception_body', function (Blueprint $table) {
            $table->foreign('stock_id')->references('id')->on('stock')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reception_body', function (Blueprint $table) {
            $table->dropForeign(['stock_id']);
        });

        Schema::dropIfExists('stock');
    }
};
