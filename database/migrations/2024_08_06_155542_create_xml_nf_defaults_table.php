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
        Schema::create('xml_nf_defaults', function (Blueprint $table) {
            $table->string('id',15)->primary();
            $table->string('cod',15)->unique();
            $table->string('campo',25)->nullable(false);
            $table->string('descricao',25)->nullable(false);
            $table->string('ele',5)->nullable(false);
            $table->string('pai',5)->nullable(false);
            $table->string('tipo',5)->nullable(false);
            $table->string('ocor',5)->nullable(false);
            $table->string('tam',5)->nullable(false);
            $table->string('obs')->nullable(false);
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('xml_nf_defaults');
    }
};
