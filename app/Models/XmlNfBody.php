<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class XmlNfBody extends Model
{
    protected $table = 'xml_nf_body';
    public $timestamps = false;

    // Definir os campos que podem ser preenchidos em massa
    protected $fillable = [
        'header', 'nItem', 'cProd', 'cEAN', 'xProd', 'NCM', 'NVE', 'CEST',
        'indEscala', 'CNPJFab', 'cBenef', 'EXTIPI', 'CFOP', 'uCom',
        'qCom', 'vUnCom', 'vProd', 'cEANTrib', 'uTrib', 'qTrib', 
        'vUnTrib', 'vFrete', 'vSeg', 'vDesc', 'vOutro', 'indTot',
        'nLote', 'qLote', 'dFab', 'dVal', 'cAgreg', 'valid'
    ];

    // Relacionamento um-para-um com XmlNfHeader
    public function XmlNfHeader()
    {
        return $this->belongsTo(XmlNfHeader::class, 'id');
    }
}
