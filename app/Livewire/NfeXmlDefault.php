<?php 

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Services\XmlToObjectConverter;
use Exception;

class NfeXmlDefault extends Component
{
    use WithFileUploads;

    public $xmlFile;
    public $elements = [];
    public $errorMessage = '';

    public function save()
    {
        $this->validate([
            'xmlFile' => 'required|file|mimes:xml|max:2048', // Validação para o arquivo XML
        ]);

        try {
            $this->xmlFile->store(path: 'resources/xml');
            // $filePath = $this->xmlFile->store(path: 'resources/xml');
            // $fullPath = storage_path('resources/' . $filePath);
            // $this->elements = XmlToObjectConverter::getElements($fullPath);
            $this->errorMessage = ''; // Clear any previous error message
        } catch (Exception $e) {
            $this->errorMessage = $e->getMessage();
            $this->elements = []; // Clear elements on error
            return false;
        }
        return true;
    }

    public function render()
    {
        return view('livewire.nfe-xml-default', [
            'elements' => $this->elements,
            'errorMessage' => $this->errorMessage,
        ]);
    }
}
