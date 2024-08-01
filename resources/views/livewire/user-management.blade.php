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
    <div class="bg-zinc-300 border border-gray-600 p-4 shadow-lg" id="userForm">
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
                            <label class="block">Name</label>
                            <input type="text" wire:model="name" class="border border-gray-600 p-2 w-full">
                            @error('name') <span class="error">{{ $message }}</span> @enderror
                        </div>

                        <div class="mb-4">
                            <label class="block">Email</label>
                            <input type="email" wire:model="email" class="border border-gray-600 p-2 w-full">
                            @error('email') <span class="error">{{ $message }}</span> @enderror
                        </div>

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
                            <label class="">Enabled</label>
                            <input type="checkbox" wire:model="enabled" class="border border-gray-600 p-2">
                        </div>
                        <div class="mb-4">
                            <label class="block">Level</label>
                            <input type="number" wire:model="level" class="border border-gray-600 p-2 w-full">
                            @error('level') <span class="error">{{ $message }}</span> @enderror
                        </div>
                        <div class="mb-4">
                            <label class="block">Password</label>
                            <input type="password" wire:model="password" class="border border-gray-600 p-2 w-full">
                            @error('password') <span class="error">{{ $message }}</span> @enderror
                        </div>

                        <div class="mb-4">
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
            <div class="mb-4">
                <button wire:click="create" class="bg-gray-300 border border-gray-600 px-4 py-2 shadow-inner hover:bg-gray-400">
                    <i class="bi bi-plus"></i> Add User
                </button>
            </div>
            <div class="mb-4">
                <input type="text" wire:model.live="search" placeholder="Search by name, email or sector" class="border border-gray-600 p-2 w-full"><!---->
            </div>
            <table class="min-w-full border border-gray-600">
                <thead>
                    <tr class="bg-gray-300">
                        <th class="border border-gray-600 p-1">Name</th>
                        <th class="border border-gray-600 p-1">Email</th>
                        <th class="border border-gray-600 p-1">Enabled</th>
                        <th class="border border-gray-600 p-1">Sector</th>
                        <th class="border border-gray-600 p-1 w-32">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($users as $user)
                        <tr class="bg-gray-200">
                            <td class="border border-gray-600 p-1">{{ $user->name }}</td>
                            <td class="border border-gray-600 p-1">{{ $user->email }}</td>
                            <td class="border border-gray-600 p-1">{{ $user->enabled ? 'Yes' : 'No' }}</td>
                            <td class="border border-gray-600 p-1">{{ $user->sector->sector }}</td>
                            <td class="border border-gray-600 p-1 flex justify-center space-x-1">
                                <button wire:click="edit({{ $user->id }})" class="bg-gray-300 border border-gray-600 px-2 py-1 shadow-inner hover:bg-gray-400">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button wire:click="confirmDeletion({{ $user->id }})" class="bg-gray-300 border border-gray-600 px-2 py-1 shadow-inner hover:bg-gray-400">
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
                    <p>Are you sure you want to delete this User?</p>
                    <p>This action cannot be undone and will delete cast records from other tables. <br>
                    Consider just disabling the User.</p>
                </div>
                <div class="flex justify-end space-x-4">
                    <button wire:click="delete({{$userToDelete}})" class="bg-red-500 border border-red-600 px-4 py-2 shadow-inner hover:bg-red-600 text-white">
                        <i class="bi bi-trash"></i> Delete
                    </button>
                    <button wire:click="$set('confirmingDeletion', false)" class="bg-gray-300 border border-gray-600 px-4 py-2 shadow-inner hover:bg-gray-400">
                        <i class="bi bi-x"></i> Cancel
                    </button>
                </div>
            </div>
        </div>
    @endif
    {{ $users->links() }}
</div>