<div class="min-h-screen bg-gray-200 p-4 font-mono text-black">
    @livewire('nav')
    @if (session()->has('message'))
        <div class="bg-green-300 border border-green-600 p-2 mb-4">
            {{ session('message') }}
        </div>
    @elseif (session()->has('messageError'))
        <div class="bg-red-300 border border-red-600 p-2 mb-4">
            {{ session('messageError') }}
        </div>
    @endif

    <div class="bg-zinc-300 border border-gray-600 p-4 shadow-lg" id="sectorPermissionForm">
        @if ($screenAction == 'create' || $screenAction == 'edit')
            <div class="flex justify-end mb-4">
                <button wire:click="showTable" class="bg-gray-300 border border-gray-600 px-4 py-2 shadow-inner hover:bg-gray-400">
                    <i class="bi bi-x"></i>
                </button>
            </div>
            <form wire:submit.prevent="{{ $screenAction == 'create' ? 'store' : 'update' }}">
                <div class="mb-4">
                    <label class="block">Sector</label>
                    <select wire:model="sector_id" class="border border-gray-600 p-2 w-full">
                        <option value="">Select Sector</option>
                        @foreach($sectors as $sector)
                            <option value="{{ $sector->id }}">{{ $sector->sector }}</option>
                        @endforeach
                    </select>
                    @error('sector_id') <span class="error">{{ $message }}</span> @enderror
                </div>

                <div class="mb-4">
                    <label class="block">Permission</label>
                    <select wire:model="permission_id" class="border border-gray-600 p-2 w-full">
                        <option value="">Select Permission</option>
                        @foreach($permissions as $permission)
                            <option value="{{ $permission->id }}">{{ $permission->route }}</option>
                        @endforeach
                    </select>
                    @error('permission_id') <span class="error">{{ $message }}</span> @enderror
                </div>

                <div class="mb-4">
                    <label class="block">Level</label>
                    <input type="number" wire:model="level" class="border border-gray-600 p-2 w-full">
                    @error('level') <span class="error">{{ $message }}</span> @enderror
                </div>
                <div class="mb-4">
                    <label class="block">Read/Write</label>
                    <select wire:model="read_write" class="border border-gray-600 p-2 w-full">
                        <option value="">Select Permission</option>
                        <option value="R">Read</option>
                        <option value="W">Write</option>
                    </select>
                    @error('permission_id') <span class="error">{{ $message }}</span> @enderror
                </div>
                <div class="mb-4">
                    <label class="">Enabled</label>
                    <input type="checkbox" wire:model="enabled" class="border border-gray-600 p-2">
                </div>

                <button type="submit" class="bg-gray-300 border border-gray-600 px-4 py-2 shadow-inner hover:bg-gray-400">
                    <i class="bi bi-save"></i> {{ $screenAction == 'create' ? 'Add Permission' : 'Update Permission' }}
                </button>
            </form>
        @else
            <div class="mb-4">
                <button wire:click="create" class="bg-gray-300 border border-gray-600 px-4 py-2 shadow-inner hover:bg-gray-400">
                    <i class="bi bi-plus"></i> Add Permission
                </button>
            </div>
            <div class="mb-4">
                <input type="text" wire:model.live="search" placeholder="Search by sector or permission" class="border border-gray-600 p-2 w-full">
            </div>
            <table class="min-w-full border border-gray-600">
                <thead>
                    <tr class="bg-gray-300">
                        <th class="border border-gray-600 p-1">Sector</th>
                        <th class="border border-gray-600 p-1">Permission</th>
                        <th class="border border-gray-600 p-1">Level</th>
                        <th class="border border-gray-600 p-1">Read/Write</th>
                        <th class="border border-gray-600 p-1">Enabled</th>
                        <th class="border border-gray-600 p-1  w-32 ">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($sectorPermissions as $sectorPermission)
                        <tr class="bg-gray-200">
                            <td class="border border-gray-600 p-1">{{ $sectorPermission->sector->sector }}</td>
                            <td class="border border-gray-600 p-1">{{ $sectorPermission->permission->route }}</td>
                            <td class="border border-gray-600 p-1">{{ $sectorPermission->level }}</td>
                            <td class="border border-gray-600 p-1">{{ $sectorPermission->read_write }}</td>
                            <td class="border border-gray-600 p-1">{{ $sectorPermission->enabled ? 'Yes' : 'No' }}</td>
                            <td class="border border-gray-600 p-1 flex justify-center space-x-1">
                                <button wire:click="edit({{ $sectorPermission->id }})" class="bg-gray-300 border border-gray-600 px-2 py-1 shadow-inner hover:bg-gray-400">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button wire:click="confirmDeletion({{ $sectorPermission->id }})" class="bg-gray-300 border border-gray-600 px-2 py-1 shadow-inner hover:bg-gray-400">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
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
