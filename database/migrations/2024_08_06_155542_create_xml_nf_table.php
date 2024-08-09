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
        Schema::create('xml_nf_header', function (Blueprint $table) {
            $table->id();
            $table->enum('tipoNF', ['compra', 'venda']);
            $table->string('idnf',47)->unique()->comment('Identificador da TAG a ser assinada');
            $table->string('versao',4)->nullable(true)->comment('Versão do leiaute');
            $table->string('cUF',2)->nullable(true)->comment('Código da UF do emitente do Documento Fiscal');
            $table->string('cNF',9)->nullable(true)->comment('Código Numérico que compõe a Chave de Acesso');
            $table->string('natOp',60)->nullable(true)->comment('Descrição da Natureza da Operação');
            $table->string('mod',2)->nullable(true)->comment('Código do Modelo do Documento Fiscal');
            $table->string('serie',3)->nullable(true)->comment('Série do Documento Fiscal');
            $table->string('nNF',9)->nullable(true)->comment('Número do Documento Fiscal');
            $table->string('dhEmi',25)->nullable(true)->comment('Data e hora de emissão do Documento Fiscal');
            $table->string('dhSaiEnt',25)->nullable(true)->comment('Data e hora de Saída ou da Entrada da Mercadoria/Produto');
            $table->string('tpNF',1)->nullable(true)->comment('Tipo de Operação ');     
            $table->string('cMunFG',7)->nullable(true)->comment('Código do Município de Ocorrência do Fato Gerador'); 
            $table->string('tpImp',1)->nullable(true)->comment('Formato de Impressão do DANFE');
            $table->string('tpEmis',1)->nullable(true)->comment('Tipo de Emissão da NF-e');
            $table->string('cDV',1)->nullable(true)->comment('Dígito Verificador da Chave de Acesso da NF-e');
            $table->string('tpAmb',1)->nullable(true)->comment('Identificação do Ambiente');
            $table->string('finNFe',1)->nullable(true)->comment('Finalidade de emissão da NF-e');
            $table->string('procEmi',1)->nullable(true)->comment('Processo de emissão da NF-e');
            $table->string('indFinal',1)->nullable(true)->comment('Indica operação com Consumidor final');
            $table->string('indPres',1)->nullable(true)->comment('Indicador de presença do comprador no estabelecimento comercial no momento da operação');
            $table->string('dhCont',25)->nullable(true)->comment('Data e Hora da entrada em contingência');
            $table->string('xJust',255)->nullable(true)->comment('Justificativa da entrada em contingência');
            $table->string('refCTe', 44)->nullable(true)->comment('Chave de acesso da NF-e referenciada');
            $table->string('nECF', 3)->nullable(true)->comment('Número de ordem sequencial do ECF');
            $table->string('nCOO', 6)->nullable(true)->comment('Número do Contador de Ordem de Operação - COO');
            $table->string('refNFe', 44)->nullable(true)->comment('Chave de acesso da NF-e referenciada');
            $table->string('AAMM', 4)->nullable(true)->comment('Ano e Mês de emissão da NF-e');
            //remetente da NF-e
            $table->string('emitCNPJ', 14)->nullable(true)->comment(  'CNPJ do emitente');
            $table->string('emitCPF', 11)->nullable(true)->comment( 'CPF do remetente');
            $table->string('emitxNome', 60)->nullable(true)->comment( 'Razão Social ou Nome do emitente');
            $table->string('emitxFant', 60)->nullable(true)->comment( 'Nome fantasia  do emitente');
            $table->string('emitxLgr', 60)->nullable(true)->comment( 'Logradouro do emitente');
            $table->string('emitnro', 60)->nullable(true)->comment( 'Número do emitente' );
            $table->string('emitxCpl', 60)->nullable(true)->comment( 'Complemento do emitente');
            $table->string('emitxBairro', 60)->nullable(true)->comment( 'Bairro do emitente');
            $table->string('emitcMun', 7)->nullable(true)->comment( 'Código do município do emitente'); 
            $table->string('emitxMun', 60)->nullable(true)->comment( 'Nome do município do emitente');
            $table->string('emitUF', 2)->nullable(true)->comment( 'Sigla da UF do emitente');
            $table->string('emitCEP', 8)->nullable(true)->comment( 'Código do CEP do emitente'); 
            $table->string('emitcPais', 4)->nullable(true)->comment( 'Código do País do emitente'); 
            $table->string('emitxPais', 60)->nullable(true)->comment( 'Nome do País do emitente');
            $table->string('emitfone', 14)->nullable(true)->comment( 'telefone do emitente');
            $table->string('emitIE', 14)->nullable(true)->comment( 'Inscrição Estadual do Emitente do emitente');
            $table->string('emitIEST', 14)->nullable(true)->comment( 'IE do Substituto Tributário do emitente');
            $table->string('emitIM', 15)->nullable(true)->comment( 'Inscrição Municipal do Prestador de Serviço do emitente');
            $table->string('emitCNAE', 7)->nullable(true)->comment( 'CNAE fiscal do emitente');
            $table->string('emitCRT', 1)->nullable(true)->comment( 'Código de Regime Tributário do emitente');
            
            //destinatario da NF-e
            $table->string('destCNPJ', 14)->nullable(true)->comment( 'CNPJ do destinatario');
            $table->string('destCPF', 11)->nullable(true)->comment(  'CPF do destinatario');
            $table->string('idEstrangeiro', 20)->nullable(true)->comment(  'Identificação do destinatário no caso de comprador estrangeiro');
            $table->string('destxNome', 60)->nullable(true)->comment(  'Razão Social ou Nome do destinatario');
            $table->string('destemail', 60)->nullable(true)->comment(  'email do destinatario');
            $table->string('destxLgr', 60)->nullable(true)->comment(  'Logradouro do destinatario');
            $table->string('destnro', 60)->nullable(true)->comment(  'Número do destinatario');
            $table->string('destxCpl', 60)->nullable(true)->comment( 'Complemento' );
            $table->string('destxBairro', 60)->nullable(true)->comment(  'Bairro' );
            $table->string('destcMun', 7)->nullable(true)->comment(  'Código do município'); 
            $table->string('destxMun', 60)->nullable(true)->comment(  'Nome do município' );
            $table->string('destUF', 2)->nullable(true)->comment(  'Sigla da UF' );
            $table->string('destCEP', 8)->nullable(true)->comment(  'Código do CEP' ); 
            $table->string('destcPais', 4)->nullable(true)->comment(  'Código do País' ); 
            $table->string('destxPais', 60)->nullable(true)->comment(  'Nome do País' );
            $table->string('destfone', 14)->nullable(true)->comment(  'Telefone do destinatario' );
            $table->string('indIEDest', 1)->nullable(true)->comment(  'Indicador da IE do Destinatário' ); 
            $table->string('destIE', 14)->nullable(true)->comment(  'Inscrição Estadual do destinatario' );
            $table->string('destISUF', 9)->nullable(true)->comment(  'Inscrição na SUFRAMA' );
            $table->string('destIM', 15)->nullable(true)->comment(  'Inscrição Municipal do Prestador de Serviço' );
            
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
