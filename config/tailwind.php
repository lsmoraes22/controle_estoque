<?php 

return [
    'divContainer'      => 'grid grid-cols-[auto_1fr] min-h-screen bg-gray-200 font-mono text-black',
    'navbar'            => 'bg-zinc-300 border border-gray-600 h-full transition-all duration-300', // Largura fixa de 256px
    'nav-list'          => 'flex flex-col text-black',
    'nav-item'          => 'bg-gray-300 border border-gray-400 px-2 py-1 rounded',
    'nav-item-logout'   => 'nav-link text-blue-500',
    
    //'divContainer'      => 'grid-cols-2 min-h-screen bg-gray-200 p-4 font-mono text-black',
    'divMassage'        => 'bg-green-300 border border-green-600 p-2 mb-4',
    'divmessageError'   => 'bg-red-300 border border-red-600 p-2 mb-4',
    'divFormContainer1' => 'bg-zinc-300 border border-gray-600 rounded-md p-4 shadow-lg ', //overflow-x-scroll
    'divFormContainer2' => 'absolute flex z-50 overflow-y-scroll justify-center inset-0 ', //p-8 fixed   
    'divFormPanel'      => 'absolute bg-opacity-100 bg-gray-300 border border-gray-600 rounded-md w-96 my-2', // my-4
    'divFormPanelTop'   => 'flex justify-end bg-opacity-100 bg-stone-400 border-stone-600 rounded-t-md mb-1',
    'divFormPanelBody'  => 'px-4',
    'closeButton'       => 'bg-red-500 border border-red-600 px-2 shadow-inner rounded-md hover:bg-red-400',
    'searchInput'       => 'border border-gray-600 rounded-sm p-1 mt-4 w-full',
    'divInput'          => 'mb-4',
    'divBlur'           => 'fixed inset-0 flex items-center justify-center z-50',
    'divAlertDelete'    => 'bg-white border border-gray-600 rounded-md p-4 shadow-lg',
    //table
    'table'             => 'min-w-full border border-gray-600  overflow-x-auto table-auto',
    'trth'              =>'bg-gray-300',
    'trtd'              =>'bg-gray-200',
    'td'                => 'border border-gray-400 px-5 ',
    //table
    'labelInput'        => 'block',
    'formInput'         => 'border border-gray-600 px-2 w-full',
    'formCheckBox'      => 'border border-gray-600 p-2',
    'formSelect'        => 'border border-gray-600 px-2 w-full',
    'formRadio'         => 'border border-gray-600',
    'formTextArea'      => 'border border-gray-600',
    'button'            => 'bg-gray-300 border border-gray-600 px-2 shadow-inner rounded-md hover:bg-gray-400',
    'saveButton'        => 'bg-green-300 border border-green-600 px-2 shadow-inner rounded-md hover:bg-green-400',
    'buttonAlertDelete' => 'bg-red-500 border border-red-600 rounded-md px-2 py-1 shadow-inner hover:bg-red-600 text-white',
    'buttonAlertCancel' => 'bg-gray-300 border border-gray-600 rounded-md px-2 py-1 shadow-inner hover:bg-gray-400',
    // Adicione mais classes conforme necessário
    'messageError' => 'bg-red-300 border border-red-600 p-2 mb-4',
    'message'  => 'bg-green-300 border border-green-600 p-2 mb-4'
];