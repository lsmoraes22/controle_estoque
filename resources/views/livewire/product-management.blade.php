<div>
    @php
        $inputs = [
            [
                'model' => 'product.description',
                'type'  => 'text',
                'label' => 'Description',
            ],
            [
                'model' => 'product.sale_price',
                'type'  => 'number',
                'label' => 'Sale Price',
            ],
            [
                'model' => 'product.purchase_price',
                'type'  => 'number',
                'label' => 'Purchase Price',
            ],
            [
                'model' => 'product.category_id',
                'type'  => 'number',
                'label' => 'Category ID',
            ],
            [
                'model' => 'product.unit',
                'type'  => 'text',
                'label' => 'Unit',
            ],
            [
                'model' => 'product.unit_per_box',
                'type'  => 'number',
                'label' => 'Units per Box',
            ],
            [
                'model' => 'product.box_ballast',
                'type'  => 'number',
                'label' => 'Box Ballast',
            ],
            [
                'model' => 'product.ballast_per_layer',
                'type'  => 'number',
                'label' => 'Ballast per Layer',
            ],
            [
                'model' => 'product.box_weight',
                'type'  => 'number',
                'label' => 'Box Weight',
            ],
            [
                'model' => 'product.shelflife',
                'type'  => 'date',
                'label' => 'Shelf Life',
            ],
            [
                'model' => 'product.supplier_default_id',
                'type'  => 'number',
                'label' => 'Default Supplier ID',
            ],
            [
                'model' => 'product.abc_curve',
                'type'  => 'text',
                'label' => 'ABC Curve',
            ],
            [
                'model' => 'product.enabled',
                'type'  => 'checkbox',
                'label' => 'Enabled',
            ],
            [
                'type'  => 'button.submit',
                'caption' => $screenAction == 'edit' ? 'Update Product' : 'Save Product',
                'iconClass' => 'bi bi-save',
            ],
        ];
    @endphp

    @include('components.layouts.headLivewire', ['headLable' => 'Add Product', 'placeholderSearch' => 'Search by Product Description'])
    @include('components.layouts.tableLivewire', ['models' => $products])

    @section('messageDeleteConfirmation')
    <p>
        Are you sure you want to delete this product? <br>
        This action cannot be undone and may affect other related data.
    </p>
    @endsection

    @if ($confirmingDeletion)
        @include('components.layouts.deleteConfirmation', ['modelToDelete' => $productToDelete])
    @endif

    @if ($screenAction == 'create' || $screenAction == 'edit')
        @include('components.layouts.formContainer', ['title' => 'Product'])
    @endif

    {{ $products->links() }}
</div>
