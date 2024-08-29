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
    @include('components.layouts.headLivewire', ['headLable' => 'Add User', 'placeholderSearch' => "Search by sector, name or email "])
    @include('components.layouts.detailstableLivewire', [
        'models' => $users, 
        'buttonList' => false, 
        'buttonDetails' => true,
        'buttonEdit' => true,
        'buttonDelete' => false,
    ])
    @include('components.layouts.detailsLivewire',['models' => $users])
    @if ($screenAction == 'create' || $screenAction == 'edit')
        @include('components.layouts.formContainer',['title' => 'Create User'])
    @endif
    @section('messageDeleteConfirmation') 
    <p>
        Are you sure you want to take this action. <br> 
        This will remove other cascading data and cannot be undone! <br>
        Consider disabling the user!
    </p>
    @endsection
    @if ($confirmingDeletion) 
        @include('components.layouts.deleteConfirmation',['modelToDelete' => $userToDelete ])
    @endif
    {{ $users->links() }}
</div>
