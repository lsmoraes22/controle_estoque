<div>
    @php
        $options = [
            [
                'caption' => 'Select Sector',
                'value'   => '',
            ]
        ];
        foreach ($sectors as $sector){
            $options[] = 
            [
                'caption' => $sector->sector,
                'value'   => $sector->id,
            ];
        }
        $inputs = [
            [
                'model' => 'name',
                'type'  => 'text',
                'label' => 'Name',
            ],
            [
                'model' => 'email',
                'type'  => 'email',
                'label' => 'Email',
            ],
            [
                'model' => 'sector_id',
                'type'  => 'select',
                'label' => 'Sector',
                'options' => $options
            ],
            [
                'model' => 'enabled',
                'type'  => 'checkbox',
                'label' => 'Enable',
            ],
            [
                'model' => 'level',
                'type'  => 'number',
                'label' => 'Level',
            ],
            [
                'model' => 'password',
                'type'  => 'password',
                'label' => 'Password',
            ],
            [
                'model' => 'password_confirmation',
                'type'  => 'password',
                'label' => 'Confirm Password',
            ],
            [
                'type'  => 'button.submit',
                'caption' => 'Save User',
                'iconClass' => 'bi bi-save',
            ],
        ];
    @endphp    
    @include('headLivewire', ['headLable' => 'Add User'])
    @include('tableLivewire',['models' => $users])
    @if ($confirmingDeletion)
        @include('deleteConfirmation')
    @endif
    @if ($screenAction == 'create' || $screenAction == 'edit')
        @include('formContainer',['title' => 'Create User'])
    @endif
    {{ $users->links() }}
</div>
