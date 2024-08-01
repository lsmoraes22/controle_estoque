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
        Schema::create('structures', function (Blueprint $table) {
            $table->string('warehouse')->comment('Warehouse');
            $table->foreign('warehouse')->references('warehouse')->on('warehouses');
            $table->smallInteger('hall')->comment('hall');
            $table->smallInteger('position')->comment('position');
            $table->tinyInteger('level')->comment('level');
            $table->primary(['warehouse', 'hall', 'position', 'level']);
            $table->boolean('filled')->comment('if alveolus is filled')->default(false);
            $table->boolean('immobilized')->comment('if alveolus is immobilized')->default(false);
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
        Schema::dropIfExists('structures');
    }
};
