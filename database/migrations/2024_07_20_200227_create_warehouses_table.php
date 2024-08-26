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
        Schema::create('warehouses', function (Blueprint $table) {
            $table->string('warehouse',1)->primary();
            $table->string('description',50)->nullable(true)->comment('Allowed multiple ids in alveolus');
            $table->boolean('multiple')->default(false)->comment('Allowed multiple ids in alveolus');
            $table->smallInteger('weight_max_alveolus')->nullable(true);
            $table->enum('type',['P','V'])->nullable(true)->comment('P: physical V:virtual');
            $table->boolean('enabled')->default(true);
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warehouses');
    }
};
