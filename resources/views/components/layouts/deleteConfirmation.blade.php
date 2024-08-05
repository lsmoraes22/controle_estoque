<div class="{{config('tailwind.divBlur')}}">
    <div class="{{config('tailwind.divAlertDelete')}}">
        <div class="mb-4">
            @yield('messageDeleteConfirmation')
        </div>
        <div class="flex justify-end space-x-4">
            <button wire:click="delete({{$modelToDelete}})" class="{{config('tailwind.buttonAlertDelete')}}">
                <i class="bi bi-trash"></i> Delete
            </button>
            <button wire:click="$set('confirmingDeletion', false)" class="{{config('tailwind.buttonAlertCancel')}}">
                <i class="bi bi-x"></i> Cancel
            </button>
        </div>
    </div>
</div>