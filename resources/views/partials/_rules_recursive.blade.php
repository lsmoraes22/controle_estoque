@foreach ($rules as $key => $value)
    @php
        // Montar a chave completa para o wire:model
        $fullKey = $parentKey ? $parentKey . '.' . $key : $key;
    @endphp

    @if (is_array($value))
        <div class="ml-4">
            <h4>{{ $key }}:</h4>
            {{-- Chamada recursiva para exibir subelementos --}}
            @include('partials._rules_recursive', ['rules' => $value, 'parentKey' => $fullKey])
        </div>
    @else
        <div class="flex items-center">
            <label class="mr-2">{{ $key }}:</label>
            <input type="checkbox" wire:model="rules.{{ $fullKey }}" {{ $value ? 'checked' : '' }}>
        </div>
    @endif
@endforeach
