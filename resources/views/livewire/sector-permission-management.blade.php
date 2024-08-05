<div>
    @php
        $options1 = [
            [
                'caption' => 'Select Sector',
                'value'   => '',
            ]
        ];
        $options2 = [
            [
                'caption' => 'Select Permission',
                'value'   => '',
            ]
        ];
        foreach ($sectors as $sector){
            $options1[] = 
            [
                'caption' => $sector->sector,
                'value'   => $sector->id,
            ];
        }
        
        foreach ($permissions as $permission){
            $options2[] =
            [
                'caption' => $permission->route,
                'value' => $permission->id
            ];
        }
        $inputs = [
            [
                'model' => 'sector_id',
                'type'  => 'select',
                'label' => 'Sector',
                'options' => $options1
            ],
            [
                'model' => 'permission_id',
                'type'  => 'select',
                'label' => 'Permission',
                'options' => $options2
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
                'model' => 'read_write',
                'type'  => 'select',
                'label' => 'Read/Write',
                'options' => [
                    [
                        'value' => 'R',
                        'caption' => 'Read'
                    ],
                    [
                        'value' => 'W',
                        'caption' => 'Write'
                    ]
                ],
            ],
            [
                'type'  => 'button.submit',
                'caption' => 'Save Permission',
                'iconClass' => 'bi bi-save',
            ],
        ];
    @endphp
    @include('components.layouts.headLivewire', ['headLable' => 'Add Permission', 'placeholderSearch' => 'Search by Sector or Permission']) 
    @include('components.layouts.tableLivewire',['models' => $sectorPermissions ])
    @section('messageDeleteConfirmation') 
    <p>
        Are you sure you want to take this action. <br> 
        This cannot be undone! <br>
        Consider disabling the permission!
    </p>
    @endsection
    @if ($confirmingDeletion)
        @include('components.layouts.deleteConfirmation',['modelToDelete' => $sectorPermissionToDelete ])
    @endif
    @if ($screenAction == 'create' || $screenAction == 'edit')
        @include('components.layouts.formContainer',['title' => 'Permission'])
    @endif
    {{ $sectorPermissions->links() }}
</div>