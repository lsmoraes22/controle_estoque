<div>
    <h1 class="text-xl font-semibold mb-4">Stock</h1>
    <div class="flex flex-wrap gap-4 mb-4">
        <label>Product
            <input type="search" wire:model.live.debounce.300ms="search" placeholder="Product ID or description" class="border rounded px-2 py-1">
        </label>
        <label>Warehouse
            <input type="text" wire:model.live.debounce.300ms="warehouse" class="border rounded px-2 py-1">
        </label>
        <label>Batch
            <input type="search" wire:model.live.debounce.300ms="batch" class="border rounded px-2 py-1">
        </label>
        <label>Status
            <select wire:model.live="status" class="border rounded px-2 py-1">
                <option value="">All statuses</option>
                @foreach (['to store', 'stored', 'reapro', 'homogeneo', 'picking'] as $stockStatus)
                    <option value="{{ $stockStatus }}">{{ $stockStatus }}</option>
                @endforeach
            </select>
        </label>
    </div>
    <div class="overflow-x-auto">
        <table class="{{ config('tailwind.table') }}">
            <thead>
                <tr class="{{ config('tailwind.trth') }}">
                    @foreach (['Stock ID', 'Product ID', 'Product description', 'Quantity', 'Unit', 'Batch', 'Fabrication date', 'Validity date', 'Supplier', 'Warehouse', 'Hall', 'Position', 'Level', 'Status'] as $label)
                        <th scope="col" class="{{ config('tailwind.td') }}">{{ $label }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse ($stocks as $stock)
                    <tr wire:key="stock-{{ $stock->id }}" class="{{ config('tailwind.trtd') }}">
                        @foreach ([$stock->id, $stock->product_id, $stock->product?->description, $stock->quantity, $stock->product?->unit, $stock->batch, $stock->fabrication, $stock->validity, $stock->supplier?->supplier, $stock->warehouse, $stock->hall, $stock->position, $stock->level, $stock->status] as $value)
                            <td class="{{ config('tailwind.td') }}">{{ $value ?? '—' }}</td>
                        @endforeach
                    </tr>
                @empty
                    <tr><td colspan="14" class="{{ config('tailwind.td') }}">No stock found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $stocks->links() }}
</div>
