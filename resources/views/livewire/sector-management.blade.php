<div>
    @php
        $inputs = [
            [
                'model' => 'sector',
                'type'  => 'text',
                'label' => 'Sector',
            ],
            [
                'model' => 'enabled',
                'type'  => 'checkbox',
                'label' => 'Enabled',
            ],
            [
                'type'  => 'button.submit',
                'caption' => 'Save Sector',
                'iconClass' => 'bi bi-save',
            ],
        ]
    @endphp
    @include('components.layouts.headLivewire', ['headLable' => 'Add Sector', 'placeholderSearch' => 'Search by Sector'])
    @include('components.layouts.detailsTableLivewire', [
        'models' => $sectors, 
        'buttonList' => false, 
        'buttonDetails' => true,
        'buttonEdit' => true,
        'buttonDelete' => false,
    ])
    @include('components.layouts.detailsLivewire',['models' => $sectors])
    @section('messageDeleteConfirmation') 
    <p>
        Are you sure you want to take this action. <br> 
        This will remove other cascading data and cannot be undone! <br>
        Consider disabling the Sector!
    </p>
    @endsection
    @if ($confirmingDeletion)
        @include('components.layouts.deleteConfirmation',['modelToDelete' => $sectorToDelete])
    @endif
    @if ($screenAction == 'create' || $screenAction == 'edit')
        @include('components.layouts.formContainer',['title' => 'Sector'])
    @endif
    {{ $sectors->links() }}
</div>