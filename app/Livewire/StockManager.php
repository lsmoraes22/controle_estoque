<?php

namespace App\Livewire;

use App\Models\Stock;
use Livewire\Component;
use Livewire\WithPagination;

class StockManager extends Component
{
    use WithPagination;

    public string $search = '';

    public string $warehouse = '';

    public string $batch = '';

    public string $status = '';

    public function updating($property): void
    {
        if (in_array($property, ['search', 'warehouse', 'batch', 'status'], true)) {
            $this->resetPage();
        }
    }

    public function render()
    {
        $search = trim($this->search);
        $warehouse = trim($this->warehouse);
        $batch = trim($this->batch);
        $status = trim($this->status);

        $stocks = Stock::query()->with(['product', 'supplier'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('product_id', 'like', '%'.$search.'%')
                        ->orWhereHas('product', fn ($product) => $product->where('description', 'like', '%'.$search.'%'));
                });
            })
            ->when($warehouse !== '', fn ($query) => $query->where('warehouse', $warehouse))
            ->when($batch !== '', fn ($query) => $query->where('batch', 'like', '%'.$batch.'%'))
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->orderBy('stock.id')->paginate(10);

        return view('livewire.stock-management', ['stocks' => $stocks]);
    }
}
