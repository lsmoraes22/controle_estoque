<div>
    @php
        $options = [
            [
                'caption' => 'Select Warehouse',
                'value'   => '',
            ]
        ];
        foreach ($warehouses as $warehouse){
            $options[] = 
            [
                'caption' => "$warehouse->warehouse - $warehouse->description",
                'value'   => $warehouse->warehouse,
            ];
        }
        $inputs = [
            [
                'model' => 'warehouse',
                'type'  => 'select',
                'label' => 'Warehouse',
                'options' => $options
            ],
            [
                'model' => 'hall',
                'type'  => 'number',
                'label' => 'hall',
            ],
            [
                'model' => 'position',
                'type'  => 'number',
                'label' => 'Position',
            ],
            [
                'model' => 'level',
                'type'  => 'number',
                'label' => 'Level',
            ],
            [
                'model' => 'enabled',
                'type'  => 'checkbox',
                'label' => 'Enable',
            ],
            [
                'model' => 'immobilized',
                'type'  => 'checkbox',
                'label' => 'Immobilized',
            ],
            [
                'type'  => 'button.submit',
                'caption' => 'Save Structure',
                'iconClass' => 'bi bi-save',
            ],
        ];
    @endphp
    @include('components.layouts.headLivewire', ['headLable' => 'Add Structure', 'placeholderSearch' => "Search by ID "])
    @include('components.layouts.detailstableLivewire', [
        'models' => $structures, 
        'buttonList' => true, 
        'buttonDetails' => false,
        'buttonEdit' => true,
        'buttonDelete' => true,
    ])

    @if($screenAction == 'edit') 
        @unset($inputs[0],$inputs[1],$inputs[2],$inputs[3])
        @include('components.layouts.formContainer',['title' => 'Edit Structure']) 
    @elseif ($screenAction == 'create')
        @include('components.layouts.formContainer',['title' => 'Create Structure'])
    @endif
    @section('messageDeleteConfirmation') 
    <p>
        Are you sure you want to take this action. <br> 
        This will remove other cascading data and cannot be undone! <br>
        Consider disabling the Structure!
    </p>
    @endsection
    @if ($confirmingDeletion) 
        @include('components.layouts.deleteConfirmation',['modelToDelete' => $structureToDelete ])
    @endif
    {{ $structures->links() }}
</div>
