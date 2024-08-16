<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;   
use SimpleXMLElement;
use App\Models as M;
use App\Traits\ActionLoggable;

class ProcessNFeXml extends Command
{
    use ActionLoggable;
    protected $signature = 'nfe:process';
    protected $description = 'Processa os arquivos XML de Nota Fiscal e os salva no banco de dados';

    public function __invoke(){
        $this->handleNF('compra');
        $this->handleNF('venda');
    }
    private function handleNF($tipoNF)
    {
        // Carregar as regras do arquivo JSON ou cache
        $rules = Cache::remember('XmlNfRules', now()->addDay(), function () {
            $rulesPath = storage_path('app/xml/nfs/rules.json');
            if (Storage::exists('xml/nfs/rules.json')) {
                return json_decode(Storage::get('xml/nfs/rules.json'), true);
            }
            return [];
        });

        $directoryIn = ($tipoNF=='compra') ? 'xml/nfs/inbound' : 'xml/nfs/outbound';
        $files = Storage::files($directoryIn);
        
        foreach ($files as $file) {
            try {
                $xmlContent = Storage::get($file);
                $xml = new SimpleXMLElement($xmlContent);
                // Verificar duplicidade antes de inserir
                $exists = DB::table('xml_nf_header')->where('idnf', $xml->infNFe->attributes()->Id)->exists();
                $directoryOut = ($tipoNF=='compra') ? 'xml/nfs/processado/inbound/' : 'xml/nfs/processado/outbound/';
                if ($exists) {
                    $this->logAction('Duplicate entry found for idnf: ', ['$xml->infNFe' => $xml->infNFe->attributes()->Id], 'error');
                    Storage::move($file, $directoryOut . basename($file));
                    return false;
                }
                $headerValid = $this->validateXml($xml, $rules['header'] ?? []);
                // Extraindo dados do XML
                $headerData = [
                    'tipoNF' => $tipoNF,
                    'idnf' => (string) $xml->infNFe->attributes()->Id,
                    'versao' => (string) $xml->infNFe->attributes()->versao,
                    'cUF' => (string) $xml->infNFe->ide->cUF,
                    'cNF' => (string) $xml->infNFe->ide->cNF,
                    'natOp' => (string) $xml->infNFe->ide->natOp,
                    'mod' => (string) $xml->infNFe->ide->mod,
                    'serie' => (string) $xml->infNFe->ide->serie,
                    'nNF' => (string) $xml->infNFe->ide->nNF,
                    'dhEmi' => (string) $xml->infNFe->ide->dhEmi,
                    'dhSaiEnt' => (string) $xml->infNFe->ide->dhSaiEnt,
                    'tpNF' => (string) $xml->infNFe->ide->tpNF,
                    'cMunFG' => (string) $xml->infNFe->ide->cMunFG,
                    'tpImp' => (string) $xml->infNFe->ide->tpImp,
                    'tpEmis' => (string) $xml->infNFe->ide->tpEmis,
                    'cDV' => (string) $xml->infNFe->ide->cDV,
                    'tpAmb' => (string) $xml->infNFe->ide->tpAmb,
                    'finNFe' => (string) $xml->infNFe->ide->finNFe,
                    'procEmi' => (string) $xml->infNFe->ide->procEmi,
                    'indFinal' => (string) $xml->infNFe->ide->indFinal,
                    'indPres' => (string) $xml->infNFe->ide->indPres,
                    'dhCont' => (string) $xml->infNFe->ide->dhCont,
                    'xJust' => (string) $xml->infNFe->ide->xJust,
                    'refCTe' => (string) $xml->infNFe->ide->refCTe,
                    'nECF' => (string) $xml->infNFe->ide->nECF,
                    'nCOO' => (string) $xml->infNFe->ide->nCOO,
                    'refNFe' => (string) $xml->infNFe->ide->refNFe,
                    'AAMM' => (string) $xml->infNFe->ide->AAMM,
                    'emitCNPJ' => (string) $xml->infNFe->emit->CNPJ,
                    'emitCPF' => (string) $xml->infNFe->emit->CPF,
                    'emitxNome' => (string) $xml->infNFe->emit->xNome,
                    'emitxFant' => (string) $xml->infNFe->emit->xFant,
                    'emitxLgr' => (string) $xml->infNFe->emit->enderEmit->xLgr,
                    'emitnro' => (string) $xml->infNFe->emit->enderEmit->nro,
                    'emitxCpl' => (string) $xml->infNFe->emit->enderEmit->xCpl,
                    'emitxBairro' => (string) $xml->infNFe->emit->enderEmit->xBairro,
                    'emitcMun' => (string) $xml->infNFe->emit->enderEmit->cMun,
                    'emitxMun' => (string) $xml->infNFe->emit->enderEmit->xMun,
                    'emitUF' => (string) $xml->infNFe->emit->enderEmit->UF,
                    'emitCEP' => (string) $xml->infNFe->emit->enderEmit->CEP,
                    'emitcPais' => (string) $xml->infNFe->emit->enderEmit->cPais,
                    'emitxPais' => (string) $xml->infNFe->emit->enderEmit->xPais,
                    'emitfone' => (string) $xml->infNFe->emit->enderEmit->fone,
                    'emitIE' => (string) $xml->infNFe->emit->IE,
                    'emitIEST' => (string) $xml->infNFe->emit->IEST,
                    'emitIM' => (string) $xml->infNFe->emit->IM,
                    'emitCNAE' => (string) $xml->infNFe->emit->CNAE,
                    'emitCRT' => (string) $xml->infNFe->emit->CRT,
                    'destCNPJ' => (string) $xml->infNFe->dest->CNPJ,
                    'destCPF' => (string) $xml->infNFe->dest->CPF,
                    'idEstrangeiro' => (string) $xml->infNFe->dest->idEstrangeiro,
                    'destxNome' => (string) $xml->infNFe->dest->xNome,
                    'destemail' => (string) $xml->infNFe->dest->email,
                    'destxLgr' => (string) $xml->infNFe->dest->enderDest->xLgr,
                    'destnro' => (string) $xml->infNFe->dest->enderDest->nro,
                    'destxCpl' => (string) $xml->infNFe->dest->enderDest->xCpl,
                    'destxBairro' => (string) $xml->infNFe->dest->enderDest->xBairro,
                    'destcMun' => (string) $xml->infNFe->dest->enderDest->cMun,
                    'destxMun' => (string) $xml->infNFe->dest->enderDest->xMun,
                    'destUF' => (string) $xml->infNFe->dest->enderDest->UF,
                    'destCEP' => (string) $xml->infNFe->dest->enderDest->CEP,
                    'destcPais' => (string) $xml->infNFe->dest->enderDest->cPais,
                    'destxPais' => (string) $xml->infNFe->dest->enderDest->xPais,
                    'destfone' => (string) $xml->infNFe->dest->enderDest->fone,
                    'indIEDest' => (string) $xml->infNFe->dest->indIEDest,
                    'destIE' => (string) $xml->infNFe->dest->IE,
                    'destISUF' => (string) $xml->infNFe->dest->ISUF,
                    'destIM' => (string) $xml->infNFe->dest->IM,
                    'valid' => (int) $headerValid,
                ];

                // Salvando no banco de dados
                $header = (object) []; $header->id = null;
                $header = M\XmlNfHeader::create($headerData);
                // Preencher a tabela xml_nf_body com informações dos produtos
                foreach ($xml->infNFe->det as $item) {
                    $itemValid = true;
                    // $itemValid = !$headerValid ? $headerValid : $this->validateXml($item, $rules['body'] ?? []);
                    $bodyData = [
                        'id' => $header->id, // mesmo ID que o cabeçalho
                        'nItem' => (string) $item->attributes()->nItem,
                        'cProd' => (string) $item->prod->cProd,
                        'cEAN' => (string) $item->prod->cEAN,
                        'xProd' => (string) $item->prod->xProd,
                        'NCM' => (string) $item->prod->NCM,
                        'NVE' => (string) $item->prod->NVE,
                        'CEST' => (string) $item->prod->CEST,
                        'indEscala' => (string) $item->prod->indEscala,
                        'CNPJFab' => (string) $item->prod->CNPJFab,
                        'cBenef' => (string) $item->prod->cBenef,
                        'EXTIPI' => (string) $item->prod->EXTIPI,
                        'CFOP' => (string) $item->prod->CFOP,
                        'uCom' => (string) $item->prod->uCom,
                        'qCom' => (string) $item->prod->qCom,
                        'vUnCom' => (string) $item->prod->vUnCom,
                        'vProd' => (string) $item->prod->vProd,
                        'cEANTrib' => (string) $item->prod->cEANTrib,
                        'uTrib' => (string) $item->prod->uTrib,
                        'qTrib' => (string) $item->prod->qTrib,
                        'vUnTrib' => (string) $item->prod->vUnTrib,
                        'vFrete' => (string) $item->prod->vFrete,
                        'vSeg' => (string) $item->prod->vSeg,
                        'vDesc' => (string) $item->prod->vDesc,
                        'vOutro' => (string) $item->prod->vOutro,
                        'indTot' => (string) $item->prod->indTot,
                        'nLote' => (string) $item->prod->nLote,
                        'qLote' => (string) $item->prod->qLote,
                        'dFab' => (string) $item->prod->dFab,
                        'dVal' => (string) $item->prod->dVal,
                        'cAgreg' => (string) $item->prod->cAgreg,
                        'valid' => (int) $itemValid,
                    ];
                    M\XmlNfBody::create($bodyData);
                }
                // Mover o arquivo para outra pasta após o processamento, se necessário
                Storage::move($file, $directoryOut . basename($file));
                $this->logAction('File processed successfully. ', ['file' => $file], 'info');
            } catch (\Exception $e) {
                $this->logAction('Error processing file $file: ', ['message' => $e->getMessage()], 'error');
            }
        }
    }

    protected function validateXml($xml, $requiredFields)
    {
        foreach ($xml->children() as $elementName => $element) {
            // Verifica se o campo existe nas regras ($requiredFields)
            if (!isset($requiredFields[$elementName])) {
                // Ignora se o campo não estiver definido nas regras
                continue;
            }
            // Validação de atributos
            $attributes = $this->getAttributes($element);
            if (count($attributes) > 0) {
                foreach ($requiredFields as $key => $value) {
                    if (is_array($value) && $value) { // Se o campo é obrigatório
                        if (isset($attributes[$key])) { // Verifica se o atributo existe
                            if ((string) $attributes[$key] !== '') {
                                // Se o atributo estiver preenchido, remove das regras
                                unset($requiredFields[$key]);
                                continue;
                            } else {
                                // Atributo obrigatório está vazio
                                $this->logAction('Atributo obrigatório está faltando no XML', ['attribute' => $key], 'error');
                                return false;
                            }
                        }
                    }
                }
            }
    
            // Validação de subgrupos recursivamente
            if (is_array($requiredFields[$elementName])) {
                if (!$this->validateXml($element, $requiredFields[$elementName])) {
                    $this->logAction('Erro ao validar subgrupo', ['key' => $elementName], 'error');
                    return false;
                }
            } else {
                // Verifica se o valor do campo é obrigatório e está vazio
                if ($requiredFields[$elementName] && (string) $element === '') {
                    $this->logAction('Campo obrigatório está faltando no XML', ['field' => $elementName], 'error');
                    return false;
                }
            }
        }
    
        return true;
    }
            
        
    private function getAttributes(SimpleXMLElement $element)
    {
        $attributes = [];
        if ($element->attributes()) {
            foreach ($element->attributes() as $name => $value) {
                $attributes[(string)$name] = (string)$value;
            }
        }
        return $attributes;
    }        
}