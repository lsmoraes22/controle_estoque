<form wire:submit.prevent="{{ $screenAction == 'create' ? 'store' : 'update' }}">
    @foreach ($inputs as $input)
        <div class="{{ config('tailwind.divInput') }}">
            @if(@isset($input['label']))
                <label class="{{config('tailwind.labelInput')}}">{{ $input['label'] }}</label>
            @endif
            @switch($input['type'])
                @case('text')
                @case('number')
                @case('date')
                @case('email')
                @case('password')
                @case('file')
                    <input type="{{$input['type']}}" wire:model="{{$input['model']}}" class="{{config('tailwind.formInput')}}">
                    @break
                @case('checkbox')
                    <input type="{{$input['type']}}" wire:model="{{$input['model']}}" class="{{config('tailwind.formCheckBox')}}">
                    @break
                @case('select')
                    <select wire:model="{{$input['model']}}" class="{{config('tailwind.formSelect')}}">
                        @foreach ($input['options'] as $option)
                            <option value="{{ $option['value'] }}">{{ $option['caption'] }}</option>
                        @endforeach 
                    </select>
                    @break
                @case('button.submit')
                    <button type="submit" class="{{config('tailwind.saveButton')}}">
                        <i class="{{$input['iconClass']}}"></i> 
                    </button> {{ $input['caption'] }}
                    @break
                @case('radio')
                    @foreach ($input['options'] as $option)
                        {{ $option['caption'] }} <input type="{{$input['type']}}" wire:model="{{$input['model']}}" class="{{config('tailwind.formRadio')}}">
                    @endforeach
                    @break
                @case('textarea')
                    <textarea wire:model="{{$input['model']}}" class="{{config('tailwind.formTextArea')}}" >
                    </textarea>
                    @break
                @default
                    @dd('input not found')
            @endswitch
            @if(@isset($input['model']))
                @error($input['model']) <span class="{{config('tailwind.messageError')}}">{{ $message }}</span> @enderror
            @endif
        </div>
    @endforeach
</form>