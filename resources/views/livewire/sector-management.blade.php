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

    <div class="bg-zinc-300 border border-gray-600 p-4 shadow-lg" id="sectorForm">
        @if ($screenAction == 'create' || $screenAction == 'edit')
            <div class="flex justify-end mb-4">
                <button wire:click="showTable" class="bg-gray-300 border border-gray-600 px-4 py-2 shadow-inner hover:bg-gray-400">
                    <i class="bi bi-x"></i>
                </button>
            </div>
            <form wire:submit.prevent="{{ $screenAction == 'create' ? 'store' : 'update' }}">
                <div class="mb-4">
                    <label class="block">Sector</label>
                    <input type="text" wire:model="sector" class="border border-gray-600 p-2 w-full">
                    @error('sector') <span class="error">{{ $message }}</span> @enderror
                </div>

                <div class="mb-4">
                    <label class="">Enabled</label>
                    <input type="checkbox" wire:model="enabled" class="border border-gray-600 p-2">
                </div>

                <button type="submit" class="bg-gray-300 border border-gray-600 px-4 py-2 shadow-inner hover:bg-gray-400">
                    <i class="bi bi-save"></i> {{ $screenAction == 'create' ? 'Add Sector' : 'Update Sector' }}
                </button>
            </form>
        @else
            <div class="mb-4">
                <button wire:click="create" class="bg-gray-300 border border-gray-600 px-4 py-2 shadow-inner hover:bg-gray-400">
                    <i class="bi bi-plus"></i> Add Sector
                </button>
            </div>
            <div class="mb-4">
                <input type="text" wire:model.live="search" placeholder="Search by sector" class="border border-gray-600 p-2 w-full"><!---->
            </div>
            <table class="min-w-full border border-gray-600">
                <thead>
                    <tr class="bg-gray-300">
                        <th class="border border-gray-600 p-1">Id</t>
                        <th class="border border-gray-600 p-1">Sector</th>
                        <th class="border border-gray-600 p-1">Enabled</th>
                        <th class="border border-gray-600 p-1 w-32">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($sectors as $sector)
                        <tr class="bg-gray-200">
                            <td class="border border-gray-600 p-1">{{ $sector->id }}</td>
                            <td class="border border-gray-600 p-1">{{ $sector->sector }}</td>
                            <td class="border border-gray-600 p-1">{{ $sector->enabled ? 'Yes' : 'No' }}</td>
                            <td class="border border-gray-600 p-1 flex justify-center space-x-1">
                                <button wire:click="edit({{ $sector->id }})" class="bg-gray-300 border border-gray-600 px-2 py-1 shadow-inner hover:bg-gray-400">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button wire:click="confirmDeletion({{ $sector->id }})" class="bg-gray-300 border border-gray-600 px-2 py-1 shadow-inner hover:bg-gray-400">
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
                    <p>Are you sure you want to delete this sector?</p>
                    <p>This action cannot be undone and will delete cast records from other tables. <br>
                    Consider just disabling the sector.</p>
                </div>
                <div class="flex justify-end space-x-4">
                    <button wire:click="delete({{$sectorToDelete}})" class="bg-red-500 border border-red-600 px-4 py-2 shadow-inner hover:bg-red-600 text-white">
                        <i class="bi bi-trash"></i> Delete
                    </button>
                    <button wire:click="$set('confirmingDeletion', false)" class="bg-gray-300 border border-gray-600 px-4 py-2 shadow-inner hover:bg-gray-400">
                        <i class="bi bi-x"></i> Cancel
                    </button>
                </div>
            </div>
        </div>
    @endif
    {{ $sectors->links() }}
</div>