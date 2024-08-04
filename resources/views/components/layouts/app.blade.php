<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Controle de Estoque</title>
    
    @livewireStyles
    <link href="tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="bootstrap-icons.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="{{config('tailwind.divContainer')}}">
    @livewire('nav')
    @if (session()->has('message'))
        <div class="{{config('tailwind.divMassage')}}">
            {{ session('message') }}
        </div>
    @elseif (session()->has('messageError'))
        <div class="{{config('tailwind.divmessageError')}}">
            {{ session('messageError') }}
        </div>
    @endif
    <div class="{{config('tailwind.divFormContainer1')}}" id="userId" >
        {{$slot}}
    </div>
</div>
    @livewireScripts
</body>
</html>