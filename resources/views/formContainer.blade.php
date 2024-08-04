@if (session()->has('message'))
    <div class="{{config('tailwind.message')}}">
        {{ session('message') }}
    </div>
@elseif (session()->has('messageError'))
    <div class="">
        {{ session('messageError') }}
    </div>
@endif
<div class="{{ config('tailwind.divFormContainer2') }}">
    <div class="{{ config('tailwind.divFormPanel') }}">
        <div class="{{ config('tailwind.divFormPanelTop') }}"> 
            <div style="margin:auto;">{{$title}}</div>
            <button wire:click="showTable" class="{{ config('tailwind.closeButton') }}">
                <i class="bi bi-x"></i>
            </button>
        </div>
        <div class="{{ config('tailwind.divFormPanelBody') }}">
            @include('formLivewire')
            @yield('formLivewire')
        </div> 
    </div>
</div>
