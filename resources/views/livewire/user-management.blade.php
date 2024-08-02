<div>
    @if ($screenAction == 'create' || $screenAction == 'edit')
        <div class="{{config('tailwind.divFormContainer2')}}">
            <div class="{{config('tailwind.divFormPanel')}}">
                <div class="{{config('tailwind.divFormPanelTop')}}">
                    <button wire:click="showTable" class="{{config('tailwind.closeButton')}}">
                        <i class="bi bi-x"></i>
                    </button>
                </div>
                <form wire:submit.prevent="{{ $screenAction == 'create' ? 'store' : 'update' }}">
                    <div class="{{config('tailwind.divInput')}}">
                        <label class="{{config('tailwind.labelInput')}}">Name</label>
                        <input type="text" wire:model="name" class="border border-gray-600 p-2 w-full">
                        @error('name') <span class="error">{{ $message }}</span> @enderror
                    </div>

                    <div class="{{config('tailwind.divInput')}}">
                        <label class="block">Email</label>
                        <input type="email" wire:model="email" class="border border-gray-600 p-2 w-full">
                        @error('email') <span class="error">{{ $message }}</span> @enderror
                    </div>

                    <div class="{{config('tailwind.divInput')}}">
                        <label class="block">Sector</label>
                        <select wire:model="sector_id" class="border border-gray-600 p-2 w-full">
                            <option value="">Select Sector</option>
                            @foreach($sectors as $sector)
                                <option value="{{ $sector->id }}">{{ $sector->sector }}</option>
                            @endforeach
                        </select>
                        @error('sector_id') <span class="error">{{ $message }}</span> @enderror
                    </div>

                    <div class="{{config('tailwind.divInput')}}">
                        <label class="">Enabled</label>
                        <input type="checkbox" wire:model="enabled" class="border border-gray-600 p-2">
                    </div>
                    <div class="{{config('tailwind.divInput')}}">
                        <label class="block">Level</label>
                        <input type="number" wire:model="level" class="border border-gray-600 p-2 w-full">
                        @error('level') <span class="error">{{ $message }}</span> @enderror
                    </div>
                    <div class="{{config('tailwind.divInput')}}">
                        <label class="block">Password</label>
                        <input type="password" wire:model="password" class="border border-gray-600 p-2 w-full">
                        @error('password') <span class="error">{{ $message }}</span> @enderror
                    </div>
                    <div class="{{config('tailwind.divInput')}}">
                        <label class="block">Confirm Password</label>
                        <input type="password" wire:model="password_confirmation" class="border border-gray-600 p-2 w-full">
                        @error('password_confirmation') <span class="error">{{ $message }}</span> @enderror
                    </div>
                    <button type="submit" class="bg-gray-300 border border-gray-600 px-4 py-2 shadow-inner hover:bg-gray-400">
                        <i class="bi bi-save"></i> {{ $screenAction == 'create' ? 'Add User' : 'Update User' }}
                    </button>
                </form>       
            </div>
        </div>
         
    @else
            <div class="{{config('tailwind.divInput')}}">
                <button wire:click="create" class="{{config('tailwind.button')}}">
                    <i class="bi bi-plus-circle"></i> 
                </button> Add User
            </div>
            <div class="{{config('tailwind.divInput')}}">
                <input type="text" wire:model.live="search" placeholder="Search by name, email or sector" class="{{config('tailwind.searchInput')}}"><!---->
            </div>
            <table class="{{config('tailwind.table')}}">
                <thead>
                    <tr class="{{config('tailwind.trth')}}">
                        <th class="{{config('tailwind.td')}}">Name</th>
                        <th class="{{config('tailwind.td')}}">Email</th>
                        <th class="{{config('tailwind.td')}}">Enabled</th>
                        <th class="{{config('tailwind.td')}}">Sector</th>
                        <th class="{{config('tailwind.td')}} w-32">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($users as $user)
                        <tr class="{{config('tailwind.trtd')}}">
                            <td class="{{config('tailwind.td')}}">{{ $user->name }}</td>
                            <td class="{{config('tailwind.td')}}">{{ $user->email }}</td>
                            <td class="{{config('tailwind.td')}}">{{ $user->enabled ? 'Yes' : 'No' }}</td>
                            <td class="{{config('tailwind.td')}}">{{ $user->sector->sector }}</td>
                            <td class="{{config('tailwind.td')}} space-x-1">
                                <button wire:click="edit({{ $user->id }})" class="{{config('tailwind.button')}}">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button wire:click="confirmDeletion({{ $user->id }})" class="{{config('tailwind.button')}}">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
        <!-- Modal de Confirmação -->
        @if ($confirmingDeletion)
        <div class="{{config('tailwind.divBlur')}}">
            <div class="{{config('tailwind.divAlertDelete')}}">
                <div class="mb-4">
                    <p>Are you sure you want to delete this User?</p>
                    <p>This action cannot be undone and will delete cast records from other tables. <br>
                    Consider just disabling the User.</p>
                </div>
                <div class="flex justify-end space-x-4">
                    <button wire:click="delete({{$userToDelete}})" class="{{config('tailwind.buttonAlertDelete')}}">
                        <i class="bi bi-trash"></i> Delete
                    </button>
                    <button wire:click="$set('confirmingDeletion', false)" class="{{config('tailwind.buttonAlertCancel')}}">
                        <i class="bi bi-x"></i> Cancel
                    </button>
                </div>
            </div>
        </div>
    @endif
    {{ $users->links() }}
</div>