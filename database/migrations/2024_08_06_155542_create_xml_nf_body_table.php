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
        Schema::create('xml_nf_body', function (Blueprint $table) {
            $table->id(); 
            //dados do produto
            $table->unsignedBigInteger('header')->nullable(false)->comment('numero do cabecalho');
            $table->foreign('header')->references('id')->on('xml_nf_header')->onDelete('cascade');
            $table->string('nItem', 3)->nullable(true)->comment('Número do item (1-990)');
            $table->string('cProd', 60)->nullable(true)->comment('Código do produto ou serviço');
            $table->string('cEAN', 14)->nullable(true)->comment('GTIN (Global Trade Item Number) do produto, antigo código EAN ou código de barras');
            $table->string('xProd', 120)->nullable(true)->comment('Descrição do produto ou serviço');
            $table->string('NCM', 8)->nullable(true)->comment('Código NCM com 8 dígitos');
            $table->string('NVE', 6)->nullable(true)->comment('Codificação NVE - Nomenclatura de Valor Aduaneiro e Estatística.');
            $table->string('CEST', 7)->nullable(true)->comment('Código CEST');             
            $table->string('indEscala', 1)->nullable(true)->comment('Indicador de Escala Relevante'); 
            $table->string('CNPJFab', 14)->nullable(true)->comment('CNPJ do Fabricante da Mercadoria'); 
            $table->string('cBenef',10)->nullable(true)->comment('Código de Benefício Fiscal na UF aplicado ao item'); 
            $table->string('EXTIPI',3)->nullable(true)->comment('EX_TIPI'); 
            $table->string('CFOP', 4)->nullable(true)->comment('Código Fiscal de Operações e Prestações'); 
            $table->string('uCom', 6)->nullable(true)->comment('Unidade Comercial'); 
            $table->string('qCom', 15)->nullable(true)->comment('Quantidade Comercial'); 
            $table->string('vUnCom', 22)->nullable(true)->comment('Valor Unitário de Comercialização'); 
            $table->string('vProd', 16)->nullable(true)->comment('Valor Total Bruto dos Produtos ou Serviços.'); 
            $table->string('cEANTrib', 14)->nullable(true)->comment('GTIN (Global Trade Item Number) da unidade tributável, antigo código EAN ou código de barras'); 
            $table->string('uTrib', 6)->nullable(true)->comment('Unidade Tributável'); 
            $table->string('qTrib', 15)->nullable(true)->comment('Quantidade Tributável'); 
            $table->string('vUnTrib', 22)->nullable(true)->comment('Valor Unitário de tributação'); 
            $table->string('vFrete',16)->nullable(true)->comment('Valor Total do Frete'); 
            $table->string('vSeg', 16)->nullable(true)->comment('Valor Total do Seguro'); 
            $table->string('vDesc', 16)->nullable(true)->comment('Valor do Desconto'); 
            $table->string('vOutro', 16)->nullable(true)->comment('Outras despesas acessórias'); 
            $table->string('indTot', 1)->nullable(true)->comment('Indica se valor do Item (vProd) entra no valor total da NF-e (vProd)');
            $table->string('nLote',20)->comment('Número do Lote do produto');
            $table->string('qLote', 12)->comment('Quantidade de produto no Lote');
            $table->string('dFab', 10)->comment('Data de fabricação/ Produção');
            $table->string('dVal', 10)->comment('Data de validade'); 
            $table->string('cAgreg', 20)->comment('Código de Agregação');
            $table->boolean('valid')->default(false)->comment(  'XML é valido?' );
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('xml_nf_body');
    }
};
