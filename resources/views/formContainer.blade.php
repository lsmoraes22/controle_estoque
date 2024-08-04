@section('formContainer')
    @if (session()->has('message'))
        <div class="{{config('tailwind.message')}}">
            {{ session('message') }}
        </div>
    @elseif (session()->has('messageError'))
        <div class="">
            {{ session('messageError') }}
        </div>
    @endif
    @if ($screenAction == 'create' || $screenAction == 'edit')
        <div class="{{ config('tailwind.divFormContainer2') }}">
            <div class="{{ config('tailwind.divFormPanel') }}">
                <div class="{{ config('tailwind.divFormPanelTop') }}">
                    <button wire:click="showTable" class="{{ config('tailwind.closeButton') }}">
                        <i class="bi bi-x"></i>
                    </button>
                </div>
                    @yield('form')
                </div>
            </div>
        </div>
    @endif
    @yield('head')
    @yield('table')
    @if ($confirmingDeletion)
        @include('deleteConfirmation')
        @yield('deleteConfirmation')
    @endif
@endsection
