<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Controle de Estoque</title>
    @livewireStyles
    <link href="{{ asset('tailwind.min.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('bootstrap-icons.min.css') }}">
    <link rel="stylesheet" href="{{ asset('style.css') }}">
</head>
<body class="bg-zinc-300 font-mono text-black min-h-screen">
    <div class="flex min-h-screen">
        @auth
            <div x-data="{ isOpen: true }" 
                 :class="isOpen ? 'w-8' : 'w-64'"
                 class="bg-zinc-300 text-white transition-all duration-300">
                @livewire('nav')
            </div>
        @endauth
        <div id="main" class="flex-grow p-8 bg-zinc-300 shadow-lg transition-all duration-300">
            @if (session()->has('message'))
                <div class="{{ config('tailwind.divMessage') }}">
                    {{ session('message') }}
                </div>
            @elseif (session()->has('messageError'))
                <div class="{{ config('tailwind.divMessageError') }}">
                    {{ session('messageError') }}
                </div>
            @endif
            <div class="{{ config('tailwind.divFormContainer1') }}" id="userId">
                {{ $slot }}
            </div>
        </div>
    </div>
    @livewireScripts
    <script src="jquery.min.js" ></script>
</body>
</html>
