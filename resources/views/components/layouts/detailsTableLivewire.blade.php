<table class="{{ config('tailwind.table') }}">
    <thead>
        @php
            $lgBtnTr = 0;
            if(isset($buttonDetails) && $buttonDetails){ $lgBtnTr+=12;}
            if(isset($buttonList) && $buttonList){ $lgBtnTr+=12;}
            if(isset($buttonEdit) && $buttonEdit){ $lgBtnTr+=12;}
            if(isset($buttonDelete) && $buttonDelete){ $lgBtnTr+=12;}
            $lgBtnTr ='w-'.$lgBtnTr;
        @endphp
        <tr class="{{ config('tailwind.trth') }}">
            @foreach($fields as $field => $label)
                <th class="{{ config('tailwind.td') }}">{{ $label }}</th>
            @endforeach
            <th class="{{ config('tailwind.td') }} {{$lgBtnTr}}">Actions</th>
        </tr>
    </thead>
    <tbody>
        @foreach($models as $model)
            <tr class="{{ config('tailwind.trtd') }}">
                @foreach($fields as $field => $label)
                    <td class="{{ config('tailwind.td') }} ">
                        @if($model->getTypeDataFields($field) === 'boolean')
                            {{ $model->$field ? 'Yes' : 'No' }}
                        @elseif(in_array($field, array_keys($foreignFields)))
                            {{ $model->{$foreignFields[$field]['table']}->{$foreignFields[$field]['field']} }}
                        @else
                            @if($model->getTypeDataFields($field) === 'boolean')
                                {{ $model->$field ? 'Yes' : 'No' }}
                            @else
                                {{ $model->$field }}
                            @endif
                        @endif
                    </td>
                @endforeach
                <td class="border border-gray-400 px-1 grid grid-flow-col "> <!--space-x-0-->
                @php
                    $details = $model->toArray();
                    $textDetails = '';
                    foreach ($details as $key => $d) {
                        if (is_array($d)) {
                            // Se o valor for um array, converte para JSON ou implode os valores
                            $d = json_encode($d); // Ou você pode usar implode(', ', $d) se preferir uma string simples
                        }
                        $d = $model->getTypeDataFields($key) === 'boolean' ? ( $d ? 'Yes' : 'No' ) : $d;
                        $textDetails .= "$key : $d | ";
                    }
                @endphp             
                    @if(isset($buttonDetails) && $buttonDetails)   
                        <button itens:details="{{ $textDetails }}" 
                            onclick="document.getElementById('P_Details_products').innerHTML = this.getAttribute('itens:details').replace(/\|/gi,'<br>').replace(/\_/gi,' ');" class="{{ config('tailwind.button') }}">
                            <i class="bi bi-lamp"></i>
                        </button>
                    @endif
                    @if(isset($buttonList) && $buttonList)
                        <button itens:link="receptions/{{$model->id}}"
                            onclick="window.location.href = this.getAttribute('itens:link').replace(/\|/gi,'<br>').replace(/\_/gi,' ');" class="{{ config('tailwind.button') }}">
                            <i class="bi bi-list"></i>
                        </button>
                    @endif
                    @if(isset($buttonEdit) && $buttonEdit)
                        <button wire:click="edit({{ $model->id }})" class="{{ config('tailwind.button') }}">
                            <i class="bi bi-pencil"></i>
                        </button>
                    @endif
                    @if(isset($buttonDelete) && $buttonDelete)
                        <button wire:click="confirmDeletion({{ $model->id }})" class="{{ config('tailwind.button') }}">
                            <i class="bi bi-trash"></i>
                        </button>
                    @endif
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
