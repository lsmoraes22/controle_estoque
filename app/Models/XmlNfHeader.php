<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class XmlNfHeader extends Model
{
    // Define o nome da tabela associada ao modelo
    protected $table = 'xml_nf_header';
    public $timestamps = false;

    // Especifica quais campos podem ser atribuídos em massa
    protected $fillable = [
        'tipoNF', 'idnf', 'versao', 'cUF', 'cNF', 'natOp', 'mod', 'serie', 'nNF', 'dhEmi',
        'dhSaiEnt', 'tpNF', 'cMunFG', 'tpImp', 'tpEmis', 'cDV', 'tpAmb', 'finNFe', 'procEmi',
        'indFinal', 'indPres', 'dhCont', 'xJust', 'refCTe', 'nECF', 'nCOO', 'refNFe', 'AAMM',
        'emitCNPJ', 'emitCPF', 'emitxNome', 'emitxFant', 'emitxLgr', 'emitnro', 'emitxCpl',
        'emitxBairro', 'emitcMun', 'emitxMun', 'emitUF', 'emitCEP', 'emitcPais', 'emitxPais',
        'emitfone', 'emitIE', 'emitIEST', 'emitIM', 'emitCNAE', 'emitCRT', 'destCNPJ', 'destCPF',
        'idEstrangeiro', 'destxNome', 'destemail', 'destxLgr', 'destnro', 'destxCpl', 'destxBairro',
        'destcMun', 'destxMun', 'destUF', 'destCEP', 'destcPais', 'destxPais', 'destfone', 'indIEDest',
        'destIE', 'destISUF', 'destIM'
    ];
}
