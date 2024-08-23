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
        Schema::create('reception_body', function (Blueprint $table) {
            $table->id('row');
            $table->unsignedBigInteger('header')->comment('number nf');
            $table->foreign('header')->references('id')->on('reception_headers')->onDelete('cascade');
            $table->string('product_id');  // Alterado para string
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->date('fabrication')->nullable(true);
            $table->date('validity')->nullable(true);
            $table->string('batch', 30)->nullable(true);
            $table->integer('quantity')->comment('physical quantity');
            $table->integer('theoretical')->comment('theoretical quantity')->nullable(true);
            $table->unsignedBigInteger('user_id')->comment('reception user_id')->nullable(true);
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reception_body');
    }
};
