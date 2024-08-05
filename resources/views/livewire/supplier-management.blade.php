<div>
    @php
        $inputs = [
            [
                'model' => 'supplier',
                'type'  => 'text',
                'label' => 'Supplier',
            ],
            [
                'model' => 'address',
                'type'  => 'text',
                'label' => 'Address',
            ],
            [
                'model' => 'phone1',
                'type'  => 'text',
                'label' => 'Phone 1',
            ],
            [
                'model' => 'phone2',
                'type'  => 'text',
                'label' => 'Phone 2',
            ],
            [
                'model' => 'phone3',
                'type' => 'text',
                'label' => 'Phone 3',
            ],
            [
                'model' => 'documents',
                'type'  => 'text',
                'label' => 'Documents',
            ],
            [
                'model' => 'enabled',
                'type'  => 'checkbox',
                'label' => 'Enabled',
            ],
            [
                'type'  => 'button.submit',
                'caption' => 'Save Supplier',
                'iconClass' => 'bi bi-save',
            ]
        ];
    @endphp
    @include('components.layouts.headLivewire', ['headLable' => 'Add Supplier', 'placeholderSearch' => "Search by Supplier, name or email "])
    @include('components.layouts.tableLivewire',['models' => $suppliers])
    @if ($screenAction == 'create' || $screenAction == 'edit')
        @include('components.layouts.formContainer',['title' => 'Create Supplier'])
    @endif
    @section('messageDeleteConfirmation') 
    <p>
        Are you sure you want to take this action. <br> 
        This will remove other cascading data and cannot be undone! <br>
        Consider disabling the supplier!
    </p>
    @endsection
    @if ($confirmingDeletion) 
        @include('components.layouts.deleteConfirmation',['modelToDelete' => $supplierToDelete ])
    @endif
    {{ $suppliers->links() }}
</div>