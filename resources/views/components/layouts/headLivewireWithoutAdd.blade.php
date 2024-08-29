<div class="{{ config('tailwind.divInput') }}">
    {{$headLable}} 
    <div class="{{config('tailwind.divInput')}}">
        <input type="text" wire:model.live="search" placeholder="{{$placeholderSearch}}" class="{{config('tailwind.searchInput')}}">
    </div>
</div>