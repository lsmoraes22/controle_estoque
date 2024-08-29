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
        Schema::create('journals', function (Blueprint $table) {
            $table->id();
            $table->enum('action',['INS','UPD','DEL'])->comment('action sql')->nullable(false);
            $table->string('code',3)->comment('movement code')->nullable(false);
            $table->enum('moreless',['+','-'])->comment('movement direction')->nullable(false);
            $table->bigInteger('user_id')->comment('user movement')->nullable(false);
            $table->bigInteger('stock_id')->comment('stock id movement')->nullable(false);
            $table->string('product_id',20)->comment('product movemented')->nullable(false);
            $table->date('fabrication')->comment('fabrication movemented')->nullable(true);
            $table->date('validity')->comment('validity movemented')->nullable(true);
            $table->string('batch', 30)->comment('batch movemented')->nullable(true);
            $table->string('warehouse')->comment('warehouse movemented')->nullable(false);
            $table->smallInteger('hall')->comment('hall movemented')->nullable(false);
            $table->smallInteger('position')->comment('position movemented')->nullable(false);
            $table->tinyInteger('level')->comment('level movemented')->nullable(false);
            $table->boolean('immobilized')->comment('immobilized true or false')->nullable(false);
            $table->string('immob_code', 3)->comment('Immobilization reason code')->nullable(true);
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('journals');
    }
};
