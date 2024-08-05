<div>
    @php
        $inputs = [
            [
                'model' => 'name',
                'type'  => 'text',
                'label' => 'Name',
            ],
            [
                'type'  => 'button.submit',
                'caption' => 'Save User',
                'iconClass' => 'bi bi-save',
            ],
        ]
    @endphp
    @include('components.layouts.headLivewire', ['headLable' => 'Add Sector'])
    @include('components.layouts.tableLivewire',['models' => $sectors])
    @if ($confirmingDeletion)
        @include('components.layouts.deleteConfirmation')
    @endif
    @if ($screenAction == 'create' || $screenAction == 'edit')
        @include('components.layouts.formContainer',['title' => 'Sector'])
    @endif
    {{ $sectors->links() }}
</div>