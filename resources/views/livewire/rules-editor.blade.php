<div>
    <h2>Editor de Regras</h2>
    <h5>Selecione as regras obrigatórias</h5>

    @if (session()->has('message'))
        <div class="{{config('tailwind.message')}}">
            {{ session('message') }}
        </div>
    @endif

    <form wire:submit.prevent="saveRules">
        <div>
            {{-- Chamada recursiva para exibir os checkboxes --}}
            @include('partials._rules_recursive', ['rules' => $rules, 'parentKey' => null])
        </div>

        <button type="submit" class="{{config('tailwind.button')}}">Salvar Regras</button>
    </form>
</div>
