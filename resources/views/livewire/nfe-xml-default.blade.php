<div>
    @if ($errorMessage)
        <div class="alert alert-danger">
            {{ $errorMessage }}
        </div>
    @endif

    <form wire:submit.prevent="save">
        <div class="form-group">
            <label for="xmlFile">Upload XML File</label>
            <input type="file" class="form-control" id="xmlFile" wire:model="xmlFile">
            @error('xmlFile') <span class="text-danger">{{ $message }}</span> @enderror
            <button type="submit" class="{{ config('tailwind.button') }}">
                <i class="bi bi-upload"></i>
                <span wire:loading>Uploading...</span>
            </button>
        </div>
    </form>

    @if ($elements)
        <h3>XML Elements</h3>
        <table class="{{ config('tailwind.table') }}">
            <thead>
                <tr class="{{ config('tailwind.trth') }}">
                    <th class="{{ config('tailwind.td') }}">Name</th>
                    <th class="{{ config('tailwind.td') }}">Attributes</th>
                    <th class="{{ config('tailwind.td') }}">Documentation</th>
                    <th class="{{ config('tailwind.td') }}">Pattern</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($elements as $element)
                    <tr class="{{ config('tailwind.trth') }}">
                        <td class="{{ config('tailwind.td') }}">{{ $element['name'] }}</td>
                        <td class="{{ config('tailwind.td') }}">
                            @foreach ($element['attributes'] as $key => $value)
                                <strong>{{ $key }}:</strong> {{ $value }}<br>
                            @endforeach
                        </td>
                        <td class="{{ config('tailwind.td') }}">{{ $element['documentation'] }}</td>
                        <td class="{{ config('tailwind.td') }}">{{ $element['pattern'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
