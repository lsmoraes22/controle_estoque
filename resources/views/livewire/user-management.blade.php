<div>
    @php
        $options = [
            [
                'caption' => 'Select Sector',
                'value'   => '',
            ],
            [
                'caption' => 'TI',
                'value'   => '1',
            ],
            [
                'caption' => 'Administrativo',
                'value'   => '2',
            ],
        ];
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
    @section('head')
        <div class="{{ config('tailwind.divInput') }}">
            <button wire:click="create" class="{{ config('tailwind.button') }}">
                <i class="bi bi-plus-circle"></i>
            </button> Add User
            <div class="{{config('tailwind.divInput')}}">
                <input type="text" wire:model.live="search" placeholder="Search by supplier" class="{{config('tailwind.searchInput')}}"><!---->
            </div>
        </div>
    @endsection
    @section('table')
        {{ $models = $users }}
        @include('tableLivewire')
    @endsection

    @section('deleteConfirmation')    
        <div class="{{ config('tailwind.divBlur') }}">
            <div class="{{ config('tailwind.divAlertDelete') }}">
                <div class="mb-4">
                    <p>Are you sure you want to delete this User?</p>
                    <p>This action cannot be undone and will delete cast records from other tables. <br>
                    Consider just disabling the User.</p>
                </div>
                <div class="flex justify-end space-x-4">
                    <button wire:click="delete({{ $userToDelete }})" class="{{ config('tailwind.buttonAlertDelete') }}">
                        <i class="bi bi-trash"></i> Delete
                    </button>
                    <button wire:click="$set('confirmingDeletion', false)" class="{{ config('tailwind.buttonAlertCancel') }}">
                        <i class="bi bi-x"></i> Cancel
                    </button>
                </div>
            </div>
        </div>
    @endsection
    @section('formContainer')
        @if ($screenAction == 'create' || $screenAction == 'edit')
            <div class="{{ config('tailwind.divFormContainer2') }}">
                <div class="{{ config('tailwind.divFormPanel') }}">
                    <div class="{{ config('tailwind.divFormPanelTop') }}">
                        <button wire:click="showTable" class="{{ config('tailwind.closeButton') }}">
                            <i class="bi bi-x"></i>
                        </button>
                    </div>
                    @include('formLivewire', ['screenAction' => $screenAction])
                    @yield('formLivewire')
                </div>
            </div>
        @endif
        @yield('head')
        @yield('table')
        @if ($confirmingDeletion)
            @yield('deleteConfirmation')
        @endif
    @endsection
    @include('formContainer', ['screenAction' => $screenAction, 'confirmingDeletion' => $confirmingDeletion])
    @yield('formContainer')
    {{ $users->links() }}
</div>
