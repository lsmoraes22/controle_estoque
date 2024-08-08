<?php 

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Services\XmlToObjectConverter;
use Illuminate\Support\Facades\Storage;
use Exception;

class NfeXmlDefault extends Component
{
    use WithFileUploads;

    public $xmlFile;
    public $errorMessage = '';
    public $message = '';

    public function save()
    {
        $this->validate([
            'xmlFile' => 'required|file|mimes:xml|max:2048', // Validação para o arquivo XML
        ]);

        try {
            // Armazena o arquivo no local temporário padrão do Laravel
            $path = $this->xmlFile->store('xml');
            // Renomeia o arquivo para 'config.xml'
            $newPath = 'xml/NfeConfig.xml';
            Storage::move($path, $newPath);

            $this->errorMessage = ''; // Clear any previous error message
            $this->message = 'File is uploaded and renamed successfully!';
            session()->flash('message', $this->message);
        } catch (Exception $e) {
            $this->errorMessage = $e->getMessage();
            session()->flash('messageError', $this->errorMessage);
        }

        $this->xmlFile = '';
    }

    public function render()
    {
        $path = realpath(base_path('storage/app/xml/NfeConfig.xml'));
        // Certifique-se de que o caminho seja válido antes de processar
        if ($path && file_exists($path)) {
            $elements = XmlToObjectConverter::getElements($path);
        } else {
            $elements = [];
            $this->errorMessage = 'O arquivo XML não foi encontrado.';
        }
        return view('livewire.nfe-xml-default', [
            'elements' => $elements,
            'errorMessage' => $this->errorMessage,
        ]);
    }
}
