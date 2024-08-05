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
            $opitions2[] =
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
                'caption' => 'Save User',
                'iconClass' => 'bi bi-save',
            ],
        ];
    @endphp
    @include('components.layouts.headLivewire', ['headLable' => 'Add Permission']) 
    @include('components.layouts.tableLivewire',['models' => $sectorPermissions])
    @if ($confirmingDeletion)
        @include('components.layouts.deleteConfirmation')
    @endif
    @if ($screenAction == 'create' || $screenAction == 'edit')
        @include('components.layouts.formContainer',['title' => 'Permission'])
    @endif
    {{ $sectorPermissions->links() }}
</div>