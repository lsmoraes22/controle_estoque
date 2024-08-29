<div>
    @include('components.layouts.headLivewireWithoutAdd', ['headLable' => 'Reception', 'placeholderSearch' => 'Search by Supplier '])
    <button class="{{$this->status=='to receive' ? config('tailwind.buttonSelected') : config('tailwind.button')}} w-52 mb-3"  wire:click="status_filter('0')">to receive</button>
    <button class="{{$this->status=='received' ? config('tailwind.buttonSelected') : config('tailwind.button')}} w-52 mb-3"  wire:click="status_filter('1')">received</button>
    @include('components.layouts.detailstableLivewire', [
        'models' => $receptionBodys, 
        'buttonList' => false, 
        'buttonDetails' => true,
        'buttonEdit' => true,
        'buttonDelete' => false,
    ])
    @include('components.layouts.detailsLivewire', [
        'models' => $receptionBodys
    ])
    @section('messageDeleteConfirmation')
    <p>
        Are you sure you want to delete this product? <br>
        This action cannot be undone and may affect other related data.
    </p>
    @endsection

    @if ($confirmingDeletion)
        @include('components.layouts.deleteConfirmation', ['modelToDelete' => $receptionBodyToDelete])
    @endif

    @if ($screenAction == 'create' || $screenAction == 'edit')
        @include('components.layouts.formContainer', ['title' => 'Reception'])
    @endif

    {{ $receptionBodys->links() }}
</div>
