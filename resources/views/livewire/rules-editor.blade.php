<div>
    <h2>Editor de Regras</h2>

    @if (session()->has('message'))
        <div class="alert alert-success">
            {{ session('message') }}
        </div>
    @endif

    <form wire:submit.prevent="saveRules">
        <div class="grid grid-cols-1 gap-4">
            {{-- Chamada recursiva para exibir os checkboxes --}}
            @include('partials._rules_recursive', ['rules' => $rules, 'parentKey' => null])
        </div>

        <button type="submit" class="{{config('tailwind.button')}}">Salvar Regras</button>
    </form>
</div>
