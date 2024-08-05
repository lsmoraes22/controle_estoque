<div class="{{ config('tailwind.divInput') }}">
    <button wire:click="create" class="{{ config('tailwind.button') }}">
        <i class="bi bi-plus-circle"></i>
    </button> {{$headLable}}
    <div class="{{config('tailwind.divInput')}}">
        <input type="text" wire:model.live="search" placeholder="Search by supplier" class="{{config('tailwind.searchInput')}}">
    </div>
</div>