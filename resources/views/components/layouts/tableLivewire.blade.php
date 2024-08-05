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
                <td class="{{ config('tailwind.td') }} space-x-1">
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
