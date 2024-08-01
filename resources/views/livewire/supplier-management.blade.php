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
    <div class="bg-zinc-300 border border-gray-600 p-4 shadow-lg" id="supplierForm">
        @if ($screenAction == 'create' || $screenAction == 'edit')
            <div class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center z-50 pt-40 overflow-scroll">
                <div class="bg-zinc-300 w-2/3 p-4 mt-4">
                    <div class="flex justify-end mb-4">
                        <button wire:click="showTable" class="bg-gray-300 border border-gray-600 px-4 py-2 shadow-inner hover:bg-gray-400">
                            <i class="bi bi-x"></i>
                        </button>
                    </div>
                    <form wire:submit.prevent="{{ $screenAction == 'create' ? 'store' : 'update' }}">
                        <div class="mb-4">
                            <label class="block">Supplier</label>
                            <input type="text" wire:model="supplier" class="border border-gray-600 p-2 w-full">
                            @error('supplier') <span class="error">{{ $message }}</span> @enderror
                        </div>

                        <div class="mb-4">
                            <label class="block">Address</label>
                            <input type="text" wire:model="address" class="border border-gray-600 p-2 w-full">
                            @error('address') <span class="error">{{ $message }}</span> @enderror
                        </div>

                        <div class="mb-4">
                            <label class="block">Phone 1</label>
                            <input type="text" wire:model="phone1" class="border border-gray-600 p-2 w-full">
                            @error('phone1') <span class="error">{{ $message }}</span> @enderror
                        </div>

                        <div class="mb-4">
                            <label class="block">Phone 2</label>
                            <input type="text" wire:model="phone2" class="border border-gray-600 p-2 w-full">
                            @error('phone2') <span class="error">{{ $message }}</span> @enderror
                        </div>

                        <div class="mb-4">
                            <label class="block">Phone 3</label>
                            <input type="text" wire:model="phone3" class="border border-gray-600 p-2 w-full">
                            @error('phone3') <span class="error">{{ $message }}</span> @enderror
                        </div>

                        <div class="mb-4">
                            <label class="block">Documents</label>
                            <input type="text" wire:model="documents" class="border border-gray-600 p-2 w-full">
                            @error('documents') <span class="error">{{ $message }}</span> @enderror
                        </div>

                        <div class="mb-4">
                            <label class="">Enable</label>
                            <input type="checkbox" wire:model="enable" class="border border-gray-600 p-2">
                        </div>

                        <button type="submit" class="bg-gray-300 border border-gray-600 px-4 py-2 shadow-inner hover:bg-gray-400">
                            <i class="bi bi-save"></i> {{ $screenAction == 'create' ? 'Add Supplier' : 'Update Supplier' }}
                        </button>
                    </form>
                </div>
            </div>
        @else
            <div class="mb-4">
                <button wire:click="create" class="bg-gray-300 border border-gray-600 px-4 py-2 shadow-inner hover:bg-gray-400">
                    <i class="bi bi-plus-circle"></i> Add New Supplier
                </button>
            </div>

            <div class="mb-4 flex items-center justify-between">
                <div class="flex">
                    <input type="text" wire:model="search" class="border border-gray-600 p-2 w-1/2" placeholder="Search suppliers">
                </div>
                <button wire:click="exportToExcel()" class="bg-gray-300 border border-gray-600 px-4 py-2 shadow-inner hover:bg-gray-400">
                    <i class="bi bi-file-earmark-excel"></i>
                </button>
            </div>

            <table class="border border-gray-600 w-full bg-white">
                <thead>
                    <tr class="bg-gray-200">
                        <th class="border border-gray-600 p-2">Supplier</th>
                        <th class="border border-gray-600 p-2">Address</th>
                        <th class="border border-gray-600 p-2">Phone 1</th>
                        <th class="border border-gray-600 p-2">Phone 2</th>
                        <th class="border border-gray-600 p-2">Phone 3</th>
                        <th class="border border-gray-600 p-2">Documents</th>
                        <th class="border border-gray-600 p-2">Enable</th>
                        <th class="border border-gray-600 p-2">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($suppliers as $supplier)
                        <tr>
                            <td class="border border-gray-600 p-2">{{ $supplier->supplier }}</td>
                            <td class="border border-gray-600 p-2">{{ $supplier->address }}</td>
                            <td class="border border-gray-600 p-2">{{ $supplier->phone1 }}</td>
                            <td class="border border-gray-600 p-2">{{ $supplier->phone2 }}</td>
                            <td class="border border-gray-600 p-2">{{ $supplier->phone3 }}</td>
                            <td class="border border-gray-600 p-2">{{ $supplier->documents }}</td>
                            <td class="border border-gray-600 p-2">{{ $supplier->enable ? 'Yes' : 'No' }}</td>
                            <td class="border border-gray-600 p-2">
                                <button wire:click="edit({{ $supplier->id }})" class="bg-gray-300 border border-gray-600 px-4 py-2 shadow-inner hover:bg-gray-400">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button wire:click="confirmDeletion({{ $supplier->id }})" class="bg-gray-300 border border-gray-600 px-4 py-2 shadow-inner hover:bg-gray-400">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="mt-4">
                {{ $suppliers->links() }}
            </div>

            @if ($confirmingDeletion)
                <div class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center z-50 pt-40">
                    <div class="bg-white w-1/3 p-4">
                        <div class="flex justify-end mb-4">
                            <button wire:click="$set('confirmingDeletion', false)" class="bg-gray-300 border border-gray-600 px-4 py-2 shadow-inner hover:bg-gray-400">
                                <i class="bi bi-x"></i>
                            </button>
                        </div>
                        <div class="mb-4 text-center">
                            <p>Are you sure you want to delete this supplier?</p>
                        </div>
                        <div class="flex justify-center">
                            <button wire:click="delete({{ $supplierToDelete }})" class="bg-red-500 border border-red-600 px-4 py-2 text-white shadow-inner hover:bg-red-600">
                                <i class="bi bi-trash"></i> Delete
                            </button>
                        </div>
                    </div>
                </div>
            @endif
        @endif
    </div>
</div>
