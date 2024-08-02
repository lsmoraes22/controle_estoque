<div>
    @if ($screenAction == 'create' || $screenAction == 'edit')
        <div class="{{config('tailwind.divContainer2')}}" id="sectorPermissionForm">
            <div class="{{config('tailwind.divFormPanel')}}">
                <div class="{{config('tailwind.divFormPanelTop')}}">
                    <button wire:click="showTable" class="{{config('tailwind.closeButton')}}">
                        <i class="bi bi-x"></i>
                    </button>
                </div>
            </div>
            <form wire:submit.prevent="{{ $screenAction == 'create' ? 'store' : 'update' }}">
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
                    <label class="block">Permission</label>
                    <select wire:model="permission_id" class="border border-gray-600 p-2 w-full">
                        <option value="">Select Permission</option>
                        @foreach($permissions as $permission)
                            <option value="{{ $permission->id }}">{{ $permission->route }}</option>
                        @endforeach
                    </select>
                    @error('permission_id') <span class="error">{{ $message }}</span> @enderror
                </div>

                <div class="{{config('tailwind.divInput')}}">
                    <label class="block">Level</label>
                    <input type="number" wire:model="level" class="border border-gray-600 p-2 w-full">
                    @error('level') <span class="error">{{ $message }}</span> @enderror
                </div>
                <div class="{{config('tailwind.divInput')}}">
                    <label class="block">Read/Write</label>
                    <select wire:model="read_write" class="border border-gray-600 p-2 w-full">
                        <option value="">Select Permission</option>
                        <option value="R">Read</option>
                        <option value="W">Write</option>
                    </select>
                    @error('permission_id') <span class="error">{{ $message }}</span> @enderror
                </div>
                <div class="{{config('tailwind.divInput')}}">
                    <label class="">Enabled</label>
                    <input type="checkbox" wire:model="enabled" class="border border-gray-600 p-2">
                </div>

                <button type="submit" class="bg-gray-300 border border-gray-600 px-4 py-2 shadow-inner hover:bg-gray-400">
                    <i class="bi bi-save"></i> {{ $screenAction == 'create' ? 'Add Permission' : 'Update Permission' }}
                </button>
            </form>
        </div>
    @else
        <div class="{{config('tailwind.divContainer2')}}" id="sectorPermissionForm">
            <div class="{{config('tailwind.divInput')}}">
                <button wire:click="create" class="{{config('tailwind.button')}}">
                    <i class="bi bi-plus-circle"></i> 
                </button> Add Permission
            </div>
            <div class="{{config('tailwind.divInput')}}">
                <input type="text" wire:model.live="search" placeholder="Search by sector or permission" class="{{config('tailwind.searchInput')}}">
            </div>
            <table class="{{config('tailwind.table')}}">
                <thead>
                    <tr class="{{config('tailwind.trth')}}">
                        <th class="{{config('tailwind.td')}}">Sector</th>
                        <th class="{{config('tailwind.td')}}">Permission</th>
                        <th class="{{config('tailwind.td')}}">Level</th>
                        <th class="{{config('tailwind.td')}}">Read/Write</th>
                        <th class="{{config('tailwind.td')}}">Enabled</th>
                        <th class="{{config('tailwind.td')}}  w-32 ">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($sectorPermissions as $sectorPermission)
                        <tr class="bg-gray-200">
                            <td class="{{config('tailwind.td')}}">{{ $sectorPermission->sector->sector }}</td>
                            <td class="{{config('tailwind.td')}}">{{ $sectorPermission->permission->route }}</td>
                            <td class="{{config('tailwind.td')}}">{{ $sectorPermission->level }}</td>
                            <td class="{{config('tailwind.td')}}">{{ $sectorPermission->read_write }}</td>
                            <td class="{{config('tailwind.td')}}">{{ $sectorPermission->enabled ? 'Yes' : 'No' }}</td>
                            <td class="{{config('tailwind.td')}} space-x-1">
                                <button wire:click="edit({{ $sectorPermission->id }})" class="{{config('tailwind.button')}}">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button wire:click="confirmDeletion({{ $sectorPermission->id }})" class="{{config('tailwind.button')}}">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
    <!-- Modal de Confirmação -->
    @if ($confirmingDeletion)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white border border-gray-600 p-4 shadow-lg">
                <div class="mb-4">
                    <p>Are you sure you want to delete this Permission?</p>
                </div>
                <div class="flex justify-end space-x-4">
                    <button wire:click="delete({{ $sectorPermissionToDelete }})" class="bg-red-500 border border-red-600 px-4 py-2 shadow-inner hover:bg-red-600 text-white">
                        <i class="bi bi-trash"></i> Delete
                    </button>
                    <button wire:click="$set('confirmingDeletion', false)" class="bg-gray-300 border border-gray-600 px-4 py-2 shadow-inner hover:bg-gray-400">
                        <i class="bi bi-x"></i> Cancel
                    </button>
                </div>
            </div>
        </div>
    @endif
    {{ $sectorPermissions->links('vendor.pagination.custom-pagination-links') }}
</div>
