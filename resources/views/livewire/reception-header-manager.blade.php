<div>
    @include('components.layouts.headLivewireWithoutAdd', ['headLable' => 'Reception', 'placeholderSearch' => 'Search by status '])
    <button class="{{$this->status=='to receive' ? config('tailwind.buttonSelected') : config('tailwind.button')}} w-52 mb-3"  wire:click="status_filter('to receive')">to receive</button>
    <button class="{{$this->status=='reception in progress' ? config('tailwind.buttonSelected') : config('tailwind.button')}} w-52 mb-3"  wire:click="status_filter('reception in progress')">in progress</button>
    <button class="{{$this->status=='received' ? config('tailwind.buttonSelected') : config('tailwind.button')}} w-52 mb-3"  wire:click="status_filter('received')">received</button>
    <button class="{{$this->status=='on hold' ? config('tailwind.buttonSelected') : config('tailwind.button')}} w-52 mb-3"  wire:click="status_filter('on hold')">on hold</button>
    <button class="{{$this->status=='canceled' ? config('tailwind.buttonSelected') : config('tailwind.button')}} w-52 mb-3"  wire:click="status_filter('canceled')">canceled</button>
    @include('components.layouts.detailstableLivewire', [
        'models' => $receptionHeaders, 
        'buttonList' => true, 
        'buttonDetails' => true,
        'buttonEdit' => true,
        'buttonDelete' => false,
    ])
    @include('components.layouts.detailsLivewire')
    @section('messageDeleteConfirmation')
    <p>
        Are you sure you want to delete this product? <br>
        This action cannot be undone and may affect other related data!
    </p>
    @endsection
    @section('messageReceptionConfirmation')
    <p>
        Are you sure you want to finish the reception incomplete? <br>
        This action cannot be undone!
    </p>
    @endsection
    @if ($confirmLowerReception)
        @include('components.layouts.receptionConfirmation', ['modelToRecept' => $receptionHeaderToDelete])
    @endif
    @if ($confirmingDeletion)
        @include('components.layouts.deleteConfirmation', ['modelToDelete' => $receptionHeaderToDelete])
    @endif

    @if ($screenAction == 'create' || $screenAction == 'edit')
        @include('components.layouts.formContainer', ['title' => 'Reception'])
    @endif
    {{ $receptionHeaders->links() }}
</div>
