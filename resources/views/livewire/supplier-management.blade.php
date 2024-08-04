<div>
    @section('form')
        <form wire:submit.prevent="{{ $screenAction == 'create' ? 'store' : 'update' }}">
            <div class="{{ config('tailwind.divInput') }}">
                <label class="{{config('tailwind.labelInput')}}">Supplier</label>
                <input type="text" wire:model="supplier" class="{{config('tailwind.formInput')}}">
                @error('supplier') <span class="error">{{ $message }}</span> @enderror
            </div>
            
            <div class="{{ config('tailwind.divInput') }}">
                <label class="{{config('tailwind.labelInput')}}">Address</label>
                <input type="text" wire:model="address" class="{{config('tailwind.formInput')}}">
                @error('address') <span class="error">{{ $message }}</span> @enderror
            </div>
            
            <div class="{{ config('tailwind.divInput') }}">
                <label class="{{config('tailwind.labelInput')}}">Phone 1</label>
                <input type="text" wire:model="phone1" class="{{config('tailwind.formInput')}}">
                @error('phone1') <span class="error">{{ $message }}</span> @enderror
            </div>
            
            <div class="{{ config('tailwind.divInput') }}">
                <label class="{{config('tailwind.labelInput')}}">Phone 2</label>
                <input type="text" wire:model="phone2" class="{{config('tailwind.formInput')}}">
                @error('phone2') <span class="error">{{ $message }}</span> @enderror
            </div>
            
            <div class="{{ config('tailwind.divInput') }}">
                <label class="{{config('tailwind.labelInput')}}">Phone 3</label>
                <input type="text" wire:model="phone3" class="{{config('tailwind.formInput')}}">
                @error('phone3') <span class="error">{{ $message }}</span> @enderror
            </div>
            
            <div class="{{ config('tailwind.divInput') }}">
                <label class="{{config('tailwind.labelInput')}}">Documents</label>
                <input type="text" wire:model="documents" class="{{config('tailwind.formInput')}}">
                @error('documents') <span class="error">{{ $message }}</span> @enderror
            </div>
            
            <div class="{{ config('tailwind.divInput') }}">
                <label class="">Enable</label>
                <input type="checkbox" wire:model="enable" class="{{config('tailwind.formCheckBox')}}">
            </div>
            
            <button type="submit" class="{{config('tailwind.saveButton')}}">
                <i class="bi bi-save"></i> {{ $screenAction == 'create' ? 'Add Supplier' : 'Update Supplier' }}
            </button>
        </form>
    @endsection
    @section('head')
        <div class="{{config('tailwind.divInput')}}">
            <button wire:click="create" class="{{config('tailwind.button')}}">
                <i class="bi bi-plus-circle"></i>
            </button> Add Supplier
        </div>
        <div class="{{config('tailwind.divInput')}}">
            <input type="text" wire:model.live="search" placeholder="Search by supplier" class="{{config('tailwind.searchInput')}}"><!---->
        </div>
    @endsection
    @section('table')
        <table class="{{config('tailwind.table')}}">
            <thead>
                <tr class="{{config('tailwind.trth')}}">
                    <th class="{{config('tailwind.td')}}">Supplier</th>
                    <th class="{{config('tailwind.td')}}">Address</th>
                    <th class="{{config('tailwind.td')}}">Phone 1</th>
                    <th class="{{config('tailwind.td')}}">Phone 2</th>
                    <th class="{{config('tailwind.td')}}">Phone 3</th>
                    <th class="{{config('tailwind.td')}}">Documents</th>
                    <th class="{{config('tailwind.td')}}">Enable</th>
                    <th class="{{config('tailwind.td')}}">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($suppliers as $supplier)
                <tr class="{{config('tailwind.trtd')}}" >
                    <td class="{{config('tailwind.td')}}">{{ $supplier->supplier }}</td>
                    <td class="{{config('tailwind.td')}}">{{ $supplier->address }}</td>
                    <td class="{{config('tailwind.td')}}">{{ $supplier->phone1 }}</td>
                    <td class="{{config('tailwind.td')}}">{{ $supplier->phone2 }}</td>
                    <td class="{{config('tailwind.td')}}">{{ $supplier->phone3 }}</td>
                    <td class="{{config('tailwind.td')}}">{{ $supplier->documents }}</td>
                    <td class="{{config('tailwind.td')}}">{{ $supplier->enable ? 'Yes' : 'No' }}</td>
                    <td class="{{config('tailwind.td')}} space-x-1 ">
                        <button wire:click="edit({{ $supplier->id }})" class="{{config('tailwind.button')}}">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button wire:click="confirmDeletion({{ $supplier->id }})" class="{{config('tailwind.button')}}">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @endsection
    @section('sectionDeleteConfirmation')
        <p>Are you sure you want to delete this supplier?</p>
    @endsection
    @include('formContainer', ['screenAction' => $screenAction, 'confirmingDeletion' => $confirmingDeletion])
    @yield('formContainer')
    {{ $suppliers->links() }}
</div>
