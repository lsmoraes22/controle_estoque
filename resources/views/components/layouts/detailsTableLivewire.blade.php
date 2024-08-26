<table class="{{ config('tailwind.table') }}">
    <thead>
        <tr class="{{ config('tailwind.trth') }}">
            @foreach($fields as $field => $label)
                <th class="{{ config('tailwind.td') }}">{{ $label }}</th>
            @endforeach
            <th class="{{ config('tailwind.td') }} w-32">Actions</th>
        </tr>
    </thead>
    <tbody>
        @foreach($models as $model)
            <tr class="{{ config('tailwind.trtd') }}">
                @foreach($fields as $field => $label)
                    <td class="{{ config('tailwind.td') }}">
                        @if($field === 'enabled')
                            {{ $model->$field ? 'Yes' : 'No' }}
                        @elseif(in_array($field, array_keys($foreignFields)))
                            {{ $model->{$foreignFields[$field]['table']}->{$foreignFields[$field]['field']} }}
                        @else
                            {{ $model->$field }}
                        @endif
                    </td>
                @endforeach
                <td class="border border-gray-400 px-1 space-x-0">
                @php
                    $details = $model->toArray();
                    $textDetails = '';
                    foreach ($details as $key => $d) {
                        if (is_array($d)) {
                            // Se o valor for um array, converte para JSON ou implode os valores
                            $d = json_encode($d); // Ou você pode usar implode(', ', $d) se preferir uma string simples
                        }
                        $textDetails .= "$key => $d | ";
                    }
                @endphp

                <button itens:details="{{ $textDetails }}" 
                    onclick="document.getElementById('P_Details_products').innerHTML = this.getAttribute('itens:details').replace(/\|/gi,'<br>').replace(/\_/gi,' ');" class="{{ config('tailwind.button') }}">
                    <i class="bi bi-lamp"></i>
                </button>
                    <button wire:click="edit({{ $model->id }})" class="{{ config('tailwind.button') }}">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button wire:click="confirmDeletion({{ $model->id }})" class="{{ config('tailwind.button') }}">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
