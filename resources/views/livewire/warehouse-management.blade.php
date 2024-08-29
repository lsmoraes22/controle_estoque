<div>
    @php
        $inputs = [
            [
                'model' => 'warehouse.warehouse',
                'type'  => 'text',
                'label' => 'Warehouse ID',
            ],
            [
                'model' => 'warehouse.description',
                'type'  => 'text',
                'label' => 'Description',
            ],
            [
                'model' => 'warehouse.multiple',
                'type'  => 'checkbox',
                'label' => 'Multiple',
            ],
            [
                'model' => 'warehouse.weight_max_alveolus',
                'type'  => 'number',
                'label' => 'Max Weight per Alveolus',
            ],
            [
                'options' => [ 
                    [
                        'caption' => 'P: physical',
                        'value' => 'P'
                    ],
                    [
                        'caption' => 'V: Virtual',
                        'value' => 'V'
                    ]
                ],
                'model' => 'warehouse.type',
                'type'  => 'select',
                'label' => 'Type',
            ],
            [
                'model' => 'warehouse.enabled',
                'type'  => 'checkbox',
                'label' => 'Enabled',
            ],
            [
                'type'  => 'button.submit',
                'caption' => $screenAction == 'edit' ? 'Update Warehouse' : 'Save Warehouse',
                'iconClass' => 'bi bi-save',
            ],
        ];
    @endphp 
    @include('components.layouts.headLivewire', ['headLable' => 'Add Warehouse', 'placeholderSearch' => 'Search by description '])
    @include('components.layouts.detailstableLivewire', [
        'models' => $warehouses, 
        'buttonList' => false, 
        'buttonDetails' => true,
        'buttonEdit' => true,
        'buttonDelete' => false,
    ])
    @include('components.layouts.detailsLivewire', [
        'models' => $warehouses
    ])
    @section('messageDeleteConfirmation')
    <p>
        Are you sure you want to delete this warehouse? <br>
        This action cannot be undone and may affect other related data.
    </p>
    @endsection

    @if ($confirmingDeletion)
        @include('components.layouts.deleteConfirmation', ['modelToDelete' => $warehouseToDelete])
    @endif

    @if ($screenAction == 'create' || $screenAction == 'edit')
        @unset($inputs[0])
        @include('components.layouts.formContainer', ['title' => 'Warehouse'])
    @endif

    {{ $warehouses->links() }}
</div>