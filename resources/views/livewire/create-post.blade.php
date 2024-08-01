<div>
    {{$title}}
    {{$author}}
    @foreach ($posts as $post)
        <livewire:post-item wire:key="{{ $post->id }}"> 
    @endforeach
</div>
