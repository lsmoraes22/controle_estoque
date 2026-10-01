<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('xml_nf_body', function (Blueprint $table) {
            $table->unique(['header', 'nItem']);
            $table->string('nLote', 20)->nullable()->comment('Número do Lote do produto')->change();
            $table->string('qLote', 12)->nullable()->comment('Quantidade de produto no Lote')->change();
            $table->string('dFab', 10)->nullable()->comment('Data de fabricação/ Produção')->change();
            $table->string('dVal', 10)->nullable()->comment('Data de validade')->change();
            $table->string('cAgreg', 20)->nullable()->comment('Código de Agregação')->change();
        });
    }

    public function down(): void
    {
        Schema::table('xml_nf_body', function (Blueprint $table) {
            // Preserve the index needed by xml_nf_body.header's foreign key when the composite key is removed.
            $table->index('header');
            $table->dropUnique(['header', 'nItem']);
            $table->string('nLote', 20)->nullable(false)->comment('Número do Lote do produto')->change();
            $table->string('qLote', 12)->nullable(false)->comment('Quantidade de produto no Lote')->change();
            $table->string('dFab', 10)->nullable(false)->comment('Data de fabricação/ Produção')->change();
            $table->string('dVal', 10)->nullable(false)->comment('Data de validade')->change();
            $table->string('cAgreg', 20)->nullable(false)->comment('Código de Agregação')->change();
        });
    }
};
