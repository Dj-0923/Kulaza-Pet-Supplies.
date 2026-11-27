<?php
/** @var bool $ok */
/** @var bool $isStaff */
/** @var array $products */
/** @var \Illuminate\Support\ViewErrorBag $errors */
$pageTitle = 'Inventory';
include resource_path('views/layouts/header.php');
?>
<?php if (!$ok): ?>
<div class="card">You do not have access to this page.</div>
<?php else: ?>
<?php if (session('status')): ?><div class="card text-green-400 mb-4"><?= e(session('status')) ?></div><?php endif; ?>
<?php if (session('category_error')): ?><div class="card text-red-300 mb-4" role="alert"><?= e(session('category_error')) ?></div><?php endif; ?>
<?php if (session('brand_error')): ?><div class="card text-red-300 mb-4" role="alert"><?= e(session('brand_error')) ?></div><?php endif; ?>
<?php if (session('stock_status')): ?><div id="stockUpdateToast" class="fixed bottom-6 right-6 z-[60] max-w-sm rounded-lg border border-[#1f271f] bg-[#141914] px-4 py-3 text-sm text-green-400 shadow-lg" role="status" aria-live="polite"><?= e(session('stock_status')) ?></div><?php endif; ?>
<?php if ($errors->any()): ?><div class="card text-red-300 mb-4"><?php foreach ($errors->all() as $error): ?><p><?= e($error) ?></p><?php endforeach; ?></div><?php endif; ?>
<div class="flex flex-wrap gap-2 mb-4" id="cats" style="display:<?= $products ? 'flex' : 'none' ?>"></div>
<div class="card">
    <div class="flex flex-col gap-3 mb-4 sm:flex-row">
        <input type="text" id="search" placeholder="Search product..." class="inp">
        <?php if(!$isStaff): ?>
            <button type="button" id="openAdd" class="btn whitespace-nowrap sm:w-auto">+ Add Product</button>
        <?php endif; ?>
    </div>
    <div class="overflow-x-auto">
        <table class="tbl" style="table-layout:fixed;min-width:480px">
            <colgroup><col style="width:23%"><col style="width:30%"><col style="width:17%"><?php if(!$isStaff): ?><col style="width:15%"><col style="width:15%"><?php else: ?><col style="width:30%"><?php endif; ?></colgroup>
            <tr><th>Category</th><th>Product</th><th>Price</th><th>Stock</th><?php if(!$isStaff): ?><th style="text-align:right">Actions</th><?php endif; ?></tr>
            <tbody id="prod">
                <?php foreach($products as $p): ?>
                    <?php $stockUnit = $p['unit'] === 'sack' ? 'sacks' : $p['unit']; ?>
                    <tr data-id="<?= e($p['id']) ?>" data-cat="<?= e($p['category']) ?>" data-search="<?= e($p['category'].' '.$p['name'].' '.($p['brand'] ?? '')) ?>">
                        <td><?= e($p['category']) ?></td><td><div class="flex items-center gap-3"><?php if ($p['image_url']): ?><img src="<?= e($p['image_url']) ?>" alt="" class="h-11 w-11 shrink-0 rounded-lg object-cover"><?php else: ?><div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg border border-[#293329] bg-[#0b0e0b] text-xs text-gray-500">Image</div><?php endif; ?><div class="min-w-0"><span class="block truncate font-semibold"><?= e($p['name']) ?></span><span class="block truncate text-xs text-gray-400"><?= e($p['brand'] ?? 'No brand') ?></span></div></div></td>
                        <td>₱<?= e(number_format($p['price'], 2)) ?> /<?= e($p['unit']) ?></td>
                        <td><?= e($p['unit'] === 'kg' ? rtrim(rtrim(number_format($p['stock'], 2, '.', ''), '0'), '.') : number_format($p['stock'])) ?> <?= e($stockUnit) ?></td>
                        <?php if(!$isStaff): ?>
                            <td>
                                <div class="relative flex justify-end" data-product-action-container>
                                    <button type="button" class="btn-dark !rounded-lg !p-2" data-product-action-toggle aria-label="Product actions for <?= e($p['name']) ?>" aria-haspopup="true" aria-controls="product-actions-<?= e($p['id']) ?>" aria-expanded="false">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                            <circle cx="12" cy="5" r="1.8"/><circle cx="12" cy="12" r="1.8"/><circle cx="12" cy="19" r="1.8"/>
                                        </svg>
                                    </button>
                                    <div id="product-actions-<?= e($p['id']) ?>" class="hidden fixed z-[80] min-w-32 rounded-lg border border-[#1f271f] bg-[#141914] p-1 shadow-xl" data-product-action-menu>
                                        <button type="button" class="w-full rounded-md px-3 py-2 text-left text-sm hover:bg-[#1f271f]" data-edit-stock="<?= e($p['id']) ?>" data-stock-url="<?= e(route('products.stock', $p['id'])) ?>" data-product-name="<?= e($p['name']) ?>" data-category-id="<?= e($p['category_id']) ?>" data-category-name="<?= e($p['category']) ?>" data-brand-name="<?= e($p['brand'] ?? '') ?>" data-image-url="<?= e($p['image_url'] ?? '') ?>" data-stock="<?= e($p['stock']) ?>" data-price="<?= e($p['price']) ?>" data-unit="<?= e($p['unit']) ?>">Edit</button>
                                        <form method="POST" action="<?= e(route('products.destroy', $p['id'])) ?>">
                                            <?= csrf_field() ?>
                                            <?= method_field('DELETE') ?>
                                            <button type="button" class="w-full rounded-md px-3 py-2 text-left text-sm text-red-300 hover:bg-[#3a1c20]" data-delete-product data-product-name="<?= e($p['name']) ?>" data-product-category="<?= e($p['category']) ?>">Delete</button>
                                        </form>
                                    </div>
                                </div>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
                <tr id="none" class="<?= $products ? 'hidden' : '' ?>"><td colspan="<?= $isStaff ? 4 : 5 ?>" class="text-gray-400 text-center" style="padding:5rem 0"><?= $products ? 'No products match. Try another category or search.' : 'No products have been added yet.' ?></td></tr>
            </tbody>
        </table>
    </div>
</div>


<?php if(!$isStaff): ?>
<div id="modal" class="hidden fixed inset-0 z-50 items-center justify-center p-4" style="background:rgba(0,0,0,.7)">
    <form method="POST" action="<?= e(route('products.store')) ?>" id="addForm" enctype="multipart/form-data" class="card w-full max-w-xl max-h-[90vh] overflow-y-auto" style="background:#171c15;padding:24px">
        <?= csrf_field() ?>
        <h2 class="text-lg font-bold mb-4">Add Product</h2>
        <div class="space-y-4">
            <div><label class="lbl" for="pn">Product name</label><input id="pn" name="name" value="<?= e(old('name')) ?>" class="inp" placeholder="Enter product name" autocomplete="off" required></div>
            <div>
                <label id="categoryFieldLabel" class="lbl" for="categoryPickerButton">Category</label>
                <?php $selectedCategory = old('category', ''); ?>
                <?php $selectedCategoryName = collect($categoryOptions)->firstWhere('name', $selectedCategory)['name'] ?? ($selectedCategory === '__new_category__' ? '+ Add new category' : 'Choose a category'); ?>
                <input id="pc" name="category" type="hidden" value="<?= e($selectedCategory) ?>">
                <div id="categoryPicker" class="relative">
                    <button type="button" id="categoryPickerButton" class="inp flex items-center justify-between text-left" aria-haspopup="listbox" aria-expanded="false" aria-controls="categoryPickerMenu">
                        <span id="categoryPickerLabel"><?= e($selectedCategoryName) ?></span>
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                    </button>
                    <input id="newCategory" name="new_category" value="<?= e(old('new_category')) ?>" class="inp hidden pr-10" placeholder="Enter new category" autocomplete="off" disabled>
                    <button type="button" id="chooseExistingCategory" class="hidden absolute right-3 top-1/2 -translate-y-1/2 text-gray-300 hover:text-white" aria-label="Choose an existing category">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                    </button>
                    <div id="categoryPickerMenu" class="hidden absolute left-0 right-0 top-full z-40 mt-1 max-h-56 overflow-y-auto rounded-lg border border-[#394139] bg-[#0b0e0b] py-1 shadow-xl" role="listbox" aria-label="Categories">
                        <div id="categoryPickerOptions">
                            <?php foreach ($categoryOptions as $category): ?>
                                <div class="flex items-center gap-2 px-2 py-1">
                                    <button type="button" class="min-w-0 flex-1 truncate px-2 py-1 text-left text-sm hover:bg-[#1a241a]" data-select-category="<?= e($category->name) ?>" role="option"><?= e($category->name) ?></button>
                                    <button type="button" class="shrink-0 rounded px-2 py-1 text-sm font-bold text-red-300 hover:bg-[#3a1c20] hover:text-red-200" data-delete-category data-category-name="<?= e($category->name) ?>" data-delete-form="deleteCategoryForm-<?= e($category->id) ?>" aria-label="Delete category <?= e($category->name) ?>">x</button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <button type="button" id="chooseNewCategory" class="w-full px-4 py-2 text-left text-sm text-green-300 hover:bg-[#1a241a]">+ Add new category</button>
                    </div>
                </div>
            </div>
            <div>
                <label id="brandFieldLabel" class="lbl" for="brandPickerButton">Brand <span class="font-normal text-gray-500">(optional)</span></label>
                <?php $selectedBrand = old('brand', ''); ?>
                <?php $selectedBrandName = collect($brandOptions)->firstWhere('name', $selectedBrand)['name'] ?? ($selectedBrand === '__new_brand__' ? '+ Add new brand' : 'Choose a brand (optional)'); ?>
                <input id="pb" name="brand" type="hidden" value="<?= e($selectedBrand) ?>">
                <div id="brandPicker" class="relative">
                    <button type="button" id="brandPickerButton" class="inp flex items-center justify-between text-left" aria-haspopup="listbox" aria-expanded="false" aria-controls="brandPickerMenu">
                        <span id="brandPickerLabel"><?= e($selectedBrandName) ?></span>
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                    </button>
                    <input id="newBrand" name="new_brand" value="<?= e(old('new_brand')) ?>" class="inp hidden pr-10" placeholder="Enter brand name" autocomplete="off" aria-labelledby="brandFieldLabel" disabled>
                    <button type="button" id="chooseExistingBrand" class="hidden absolute right-3 top-1/2 -translate-y-1/2 text-gray-300 hover:text-white" aria-label="Choose an existing brand">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                    </button>
                    <div id="brandPickerMenu" class="hidden absolute left-0 right-0 top-full z-40 mt-1 max-h-56 overflow-y-auto rounded-lg border border-[#394139] bg-[#0b0e0b] py-1 shadow-xl" role="listbox" aria-label="Brands">
                        <div id="brandPickerOptions">
                            <?php foreach ($brandOptions as $brand): ?>
                                <div class="flex items-center gap-2 px-2 py-1">
                                    <button type="button" class="min-w-0 flex-1 truncate px-2 py-1 text-left text-sm hover:bg-[#1a241a]" data-select-brand="<?= e($brand->name) ?>" role="option"><?= e($brand->name) ?></button>
                                    <button type="button" class="shrink-0 rounded px-2 py-1 text-sm font-bold text-red-300 hover:bg-[#3a1c20] hover:text-red-200" data-delete-brand data-brand-name="<?= e($brand->name) ?>" data-delete-form="deleteBrandForm-<?= e($brand->id) ?>" aria-label="Delete brand <?= e($brand->name) ?>">x</button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <p id="brandNoCategoryOptions" class="hidden px-4 py-2 text-sm text-gray-400">No saved brands for this category yet.</p>
                        <button type="button" id="chooseNewBrand" class="w-full px-4 py-2 text-left text-sm text-green-300 hover:bg-[#1a241a]">+ Add new brand</button>
                    </div>
                </div>
            </div>
            <div>
                <label class="lbl" for="productUnit">Unit sold by</label>
                <select id="productUnit" name="unit" class="inp" required>
                    <option value="pcs" <?= old('unit', 'pcs') === 'pcs' ? 'selected' : '' ?>>Pieces (pcs)</option>
                    <option value="kg" <?= old('unit') === 'kg' ? 'selected' : '' ?>>Kilograms (kg)</option>
                    <option value="sack" <?= old('unit') === 'sack' ? 'selected' : '' ?>>Sack</option>
                </select>
                <p class="text-xs text-gray-400 mt-1">Price and stock are tracked using the selected unit.</p>
            </div>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div><label id="priceLabel" class="lbl" for="pp">Price per piece</label><input id="pp" name="price" type="number" min="0" step="0.01" value="<?= e(old('price')) ?>" class="inp" placeholder="Enter price per piece" required></div>
                <div><label id="stockLabel" class="lbl" for="pk">Stock (pcs)</label><input id="pk" name="stock" type="number" min="0" step="1" value="<?= e(old('stock')) ?>" class="inp" placeholder="Enter pieces in stock" required></div>
            </div>
            <div>
                <label class="lbl" for="productImage">Product image <span class="font-normal text-gray-500">(optional)</span></label>
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                    <img id="imagePreview" alt="Product image preview" class="hidden h-24 w-24 rounded-xl border border-[#394139] object-cover">
                    <input id="productImage" name="image" type="file" accept="image/*" class="inp file:mr-3 file:rounded-md file:border-0 file:bg-[#243024] file:px-3 file:py-2 file:text-sm file:text-white">
                </div>
                <p class="mt-1 text-xs text-gray-500">Upload JPG, PNG, GIF, or WebP up to 5 MB.</p>
            </div>
        </div>
        <div class="flex justify-end gap-2 mt-6">
            <button type="button" id="closeAdd" class="btn-dark">Cancel</button>
            <button type="submit" class="btn">Save Product</button>
        </div>
    </form>
</div>
<?php endif; ?>
<?php endif; ?>
<?php if (!$isStaff && $ok): ?>
    <?php foreach ($categoryOptions as $category): ?>
        <form id="deleteCategoryForm-<?= e($category->id) ?>" method="POST" action="<?= e(route('categories.destroy', $category->id)) ?>" class="hidden">
            <?= csrf_field() ?>
            <?= method_field('DELETE') ?>
        </form>
    <?php endforeach; ?>
    <?php foreach ($brandOptions as $brand): ?>
        <form id="deleteBrandForm-<?= e($brand->id) ?>" method="POST" action="<?= e(route('brands.destroy', $brand->id)) ?>" class="hidden">
            <?= csrf_field() ?>
            <?= method_field('DELETE') ?>
        </form>
    <?php endforeach; ?>
<?php endif; ?>
<?php if(!$isStaff && $ok): ?>
<div id="stockModal" class="hidden fixed inset-0 z-50 items-center justify-center p-4" style="background:rgba(0,0,0,.7)" role="dialog" aria-modal="true" aria-labelledby="stockModalTitle">
    <form method="POST" id="stockForm" enctype="multipart/form-data" class="card w-full max-w-md max-h-[90vh] overflow-y-auto" style="background:#171c15;padding:24px">
        <?= csrf_field() ?>
        <h2 id="stockModalTitle" class="text-lg font-bold mb-2">Edit Product</h2>
        <label class="lbl mt-3" for="stockProductName">Product name</label>
        <input id="stockProductName" name="name" type="text" maxlength="255" class="inp mb-4" required>
        <label class="lbl" for="stockCategory">Category</label>
        <input id="stockCategory" name="category_name" type="text" maxlength="255" class="inp mb-1" required>
        <input id="stockCategoryId" name="category_id" type="hidden">
        <p class="mb-4 text-xs text-gray-500">Edit the category name here. Changes apply to all products in this category.</p>
        <label class="lbl" for="stockBrand">Brand</label>
        <div id="stockBrandPicker" class="relative mb-1">
            <input id="stockBrand" name="brand_name" type="text" class="inp pr-10" placeholder="Type or choose a brand" maxlength="255" autocomplete="off" aria-haspopup="listbox" aria-expanded="false" aria-controls="stockBrandMenu">
            <button type="button" id="stockBrandToggle" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-300 hover:text-white" aria-label="Show brand options" aria-haspopup="listbox" aria-expanded="false" aria-controls="stockBrandMenu">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
            </button>
            <div id="stockBrandMenu" class="hidden absolute left-0 right-0 top-full z-[70] mt-1 max-h-48 overflow-y-auto rounded-lg border border-[#394139] bg-[#0b0e0b] py-1 shadow-xl" role="listbox" aria-label="Saved brands">
                <div id="stockBrandOptions">
                    <?php foreach ($brandOptions as $brand): ?>
                        <button type="button" class="block w-full px-3 py-2 text-left text-sm hover:bg-[#1a241a]" data-brand-option="<?= e($brand->name) ?>" role="option"><?= e($brand->name) ?></button>
                    <?php endforeach; ?>
                </div>
                <p id="stockBrandNoMatches" class="hidden px-3 py-2 text-sm text-gray-400">No saved brand matches this category. Save to add this brand.</p>
            </div>
        </div>
        <p class="mb-4 text-xs text-gray-500">Type a new name, choose a saved brand, or leave blank for no brand.</p>
        <div class="mb-4">
            <label class="lbl" for="stockImage">Product image</label>
            <div class="flex items-center gap-3">
                <img id="stockImagePreview" alt="Product image preview" class="hidden h-20 w-20 shrink-0 rounded-lg border border-[#394139] object-cover">
                <div id="stockImagePlaceholder" class="flex h-20 w-20 shrink-0 items-center justify-center rounded-lg border border-[#394139] bg-[#0b0e0b] text-xs text-gray-500">No image</div>
                <input id="stockImage" name="image" type="file" accept="image/*" class="inp min-w-0 flex-1 file:mr-2 file:rounded-md file:border-0 file:bg-[#243024] file:px-2 file:py-2 file:text-xs file:text-white">
            </div>
            <p class="mt-1 text-xs text-gray-500">Choose a new image to replace the current one. Maximum 5 MB.</p>
        </div>
        <label id="stockPriceLabel" class="lbl mt-4" for="stockPrice">Price per piece</label>
        <input id="stockPrice" name="price" type="number" min="0" max="99999999.99" step="0.01" class="inp" required>
        <label id="stockQuantityLabel" class="lbl mt-4" for="stockQuantity">Quantity</label>
        <div class="flex items-center gap-2">
            <button type="button" id="decreaseStockQuantity" class="btn-dark !px-2 !py-0 rounded-full" aria-label="Decrease quantity">−</button>
            <input id="stockQuantity" name="quantity" type="number" min="0" max="99999999.99" step="1" value="0" class="inp text-center" required>
            <button type="button" id="increaseStockQuantity" class="btn-dark !px-2 !py-0 rounded-full" aria-label="Increase quantity">+</button>
        </div>
        <div class="flex justify-end gap-2 mt-6">
            <button type="button" id="closeStock" class="btn-dark">Cancel</button>
            <button type="submit" class="btn">Save Changes</button>
        </div>
    </form>
</div>
<div id="deleteModal" class="hidden fixed inset-0 z-[60] items-center justify-center p-4 backdrop-blur-sm" style="background:rgba(4,7,4,.82)" role="dialog" aria-modal="true" aria-labelledby="deleteModalTitle" aria-describedby="deleteModalDescription">
    <div class="card w-full max-w-md" style="background:#141914;border-color:#422328;padding:24px;box-shadow:0 24px 80px rgba(0,0,0,.55)">
        <div class="flex items-start gap-4">
            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl border border-[#633039] bg-[#30171b] text-[#fda4af]" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="m19 6-1 14H6L5 6"/><path d="M10 11v5M14 11v5"/></svg>
            </div>
            <div class="min-w-0 flex-1">
                <p class="mb-1 text-[11px] font-semibold uppercase tracking-[.16em] text-[#d9868f]">Remove from catalog</p>
                <h2 id="deleteModalTitle" class="text-xl font-bold">Delete this product?</h2>
                <p id="deleteModalDescription" class="mt-2 text-sm leading-6 text-gray-400">This will permanently remove the product from your store. This action can't be undone.</p>
            </div>
        </div>
        <div class="mt-5 rounded-lg border border-[#252d25] bg-[#0b0e0b] px-4 py-3">
            <p id="deleteProductName" class="truncate text-sm font-semibold text-white"></p>
            <p id="deleteProductCategory" class="mt-1 text-xs text-gray-400"></p>
        </div>
        <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
            <button type="button" id="cancelDelete" class="btn-dark">Keep product</button>
            <button type="button" id="confirmDelete" class="btn-red !rounded-lg !px-4 !py-2 font-semibold" style="background:#7f2934">Delete product</button>
        </div>
    </div>
</div>
<?php endif; ?>
<?php if ($ok): ob_start(); ?><script>
    let cat = 'All';
    const $ = id => document.getElementById(id);
    const brandsByCategory = <?= json_encode($brandOptionsByCategory, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const stockUpdateToast = $('stockUpdateToast');
    if (stockUpdateToast) setTimeout(() => stockUpdateToast.remove(), 3000);
    const categoryPickerButton = $('categoryPickerButton');
    const categoryPickerMenu = $('categoryPickerMenu');
    const categoryPickerLabel = $('categoryPickerLabel');
    const categorySelect = $('pc');
    function updateCreateBrandOptions(resetSelection) {
        const availableBrands = brandsByCategory[categorySelect.value] || [];
        let visibleOptions = 0;
        $('brandPickerOptions').querySelectorAll('[data-select-brand]').forEach(option => {
            const row = option.parentElement;
            const isAvailable = availableBrands.includes(option.dataset.selectBrand);
            row.classList.toggle('hidden', !isAvailable);
            if (isAvailable) visibleOptions++;
        });
        $('brandNoCategoryOptions').classList.toggle('hidden', visibleOptions > 0);
        if (resetSelection && brandSelect.value && !availableBrands.includes(brandSelect.value)) {
            brandSelect.value = '';
            newBrandInput.value = '';
            updateBrandInput();
            $('brandPickerMenu').classList.add('hidden');
            brandPickerButton.setAttribute('aria-expanded', 'false');
        }
    }
    if (categoryPickerButton) {
        function closeCategoryPicker() {
            categoryPickerMenu.classList.add('hidden');
            categoryPickerButton.setAttribute('aria-expanded', 'false');
        }
        categoryPickerButton.onclick = () => {
            const isOpen = !categoryPickerMenu.classList.contains('hidden');
            categoryPickerMenu.classList.toggle('hidden', isOpen);
            categoryPickerButton.setAttribute('aria-expanded', String(!isOpen));
        };
        $('categoryPickerOptions').addEventListener('click', event => {
            const deleteButton = event.target.closest('[data-delete-category]');
            if (deleteButton) {
                event.preventDefault();
                event.stopPropagation();
                if (confirm(`Delete the "${deleteButton.dataset.categoryName}" category?`)) {
                    document.getElementById(deleteButton.dataset.deleteForm).requestSubmit();
                }
                return;
            }

            const option = event.target.closest('[data-select-category]');
            if (!option) return;
            const previousCategory = categorySelect.value;
            categorySelect.value = option.dataset.selectCategory;
            categoryPickerLabel.textContent = option.dataset.selectCategory;
            updateCategoryInput();
            updateCreateBrandOptions(previousCategory !== categorySelect.value);
            closeCategoryPicker();
        });
        $('chooseNewCategory').onclick = () => {
            const previousCategory = categorySelect.value;
            categorySelect.value = '__new_category__';
            updateCategoryInput();
            updateCreateBrandOptions(previousCategory !== categorySelect.value);
            closeCategoryPicker();
            newCategoryInput.focus();
        };
        $('chooseExistingCategory').onclick = () => {
            const previousCategory = categorySelect.value;
            categorySelect.value = '';
            updateCategoryInput();
            updateCreateBrandOptions(previousCategory !== categorySelect.value);
            categoryPickerButton.click();
        };
        document.addEventListener('click', event => {
            if (!event.target.closest('#categoryPicker')) closeCategoryPicker();
        });
    }
    const brandPickerButton = $('brandPickerButton');
    const brandPickerMenu = $('brandPickerMenu');
    const brandPickerLabel = $('brandPickerLabel');
    const brandSelect = $('pb');
    const newBrandInput = $('newBrand');
    const chooseExistingBrand = $('chooseExistingBrand');
    if (brandPickerButton) {
        function closeBrandPicker() {
            brandPickerMenu.classList.add('hidden');
            brandPickerButton.setAttribute('aria-expanded', 'false');
        }
        brandPickerButton.onclick = () => {
            const isOpen = !brandPickerMenu.classList.contains('hidden');
            brandPickerMenu.classList.toggle('hidden', isOpen);
            brandPickerButton.setAttribute('aria-expanded', String(!isOpen));
        };
        $('brandPickerOptions').addEventListener('click', event => {
            const deleteButton = event.target.closest('[data-delete-brand]');
            if (deleteButton) {
                event.preventDefault();
                event.stopPropagation();
                if (confirm(`Delete the "${deleteButton.dataset.brandName}" brand?`)) {
                    document.getElementById(deleteButton.dataset.deleteForm).requestSubmit();
                }
                return;
            }

            const option = event.target.closest('[data-select-brand]');
            if (!option) return;
            brandSelect.value = option.dataset.selectBrand;
            brandPickerLabel.textContent = option.dataset.selectBrand;
            updateBrandInput();
            closeBrandPicker();
        });
        $('chooseNewBrand').onclick = () => {
            brandSelect.value = '__new_brand__';
            updateBrandInput();
            newBrandInput.focus();
            closeBrandPicker();
        };
        document.addEventListener('click', event => {
            if (!event.target.closest('#brandPicker')) closeBrandPicker();
        });
    }
    function filter() {
        const terms = normalizeSearchText($('search').value).split(' ').filter(Boolean);
        let n = 0;
        const rows = document.querySelectorAll('#prod tr[data-cat]');
        rows.forEach(r => {
            const searchableText = normalizeSearchText(r.dataset.search);
            const matchesSearch = terms.every(term => searchableText.includes(term));
            const show = (cat === 'All' || r.dataset.cat === cat) && matchesSearch;
            r.classList.toggle('hidden', !show); if (show) n++;
        });
        $('cats').hidden = rows.length === 0;
        $('none').querySelector('td').textContent = rows.length === 0
            ? 'No products have been added yet.'
            : 'No products match. Try another category or search.';
        $('none').classList.toggle('hidden', n > 0);
    }
    function normalizeSearchText(value) {
        return value
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .toLocaleLowerCase()
            .replace(/[^\p{L}\p{N}]+/gu, ' ')
            .trim();
    }
    const modal = $('modal');
    const newCategoryInput = $('newCategory');
    const chooseExistingCategory = $('chooseExistingCategory');
    const categoryFieldLabel = $('categoryFieldLabel');
    function updateCategoryInput() {
        const isNew = categorySelect.value === '__new_category__';
        categoryPickerButton.classList.toggle('hidden', isNew);
        newCategoryInput.classList.toggle('hidden', !isNew);
        chooseExistingCategory.classList.toggle('hidden', !isNew);
        categoryFieldLabel.htmlFor = isNew ? 'newCategory' : 'categoryPickerButton';
        newCategoryInput.disabled = !isNew;
        newCategoryInput.required = isNew;
        if (!isNew) newCategoryInput.value = '';
    }
    function updateBrandInput() {
        const isNew = brandSelect.value === '__new_brand__';
        brandPickerButton.classList.toggle('hidden', isNew);
        newBrandInput.classList.toggle('hidden', !isNew);
        chooseExistingBrand.classList.toggle('hidden', !isNew);
        newBrandInput.disabled = !isNew;
        newBrandInput.required = isNew;
        if (!isNew) {
            newBrandInput.value = '';
            brandPickerLabel.textContent = brandSelect.value || 'Choose a brand (optional)';
        }
    }
    if (chooseExistingBrand) {
        chooseExistingBrand.onclick = () => {
            brandSelect.value = '';
            updateBrandInput();
            brandPickerButton.click();
        };
    }
    function updateUnitFields() {
        const units = {
            pcs: {price: 'Price per piece', stock: 'Stock (pcs)', amount: 'pieces'},
            kg: {price: 'Price per kg', stock: 'Stock (kg)', amount: 'kilograms'},
            sack: {price: 'Price per sack', stock: 'Stock (sacks)', amount: 'sacks'}
        };
        const unit = $('productUnit').value;
        const labels = units[unit];
        $('priceLabel').textContent = labels.price;
        $('pp').placeholder = `Enter ${labels.price.toLowerCase()}`;
        $('stockLabel').textContent = labels.stock;
        $('pk').step = unit === 'kg' ? '0.01' : '1';
        $('pk').placeholder = `Enter ${labels.amount} in stock`;
    }
    function renderTabs() {
        const box = $('cats');
        const categories = [...new Set(Array.from(document.querySelectorAll('#prod tr[data-cat]'), row => row.dataset.cat))];
        box.style.display = categories.length ? 'flex' : 'none';
        if (!categories.includes(cat)) cat = 'All';
        const tabCategories = ['All', ...categories];
        let tabs = [...box.querySelectorAll('button[data-category-tab]')];
        const canReuseTabs = tabs.length === tabCategories.length
            && tabs.every((tab, index) => tab.dataset.categoryTab === tabCategories[index]);

        if (!canReuseTabs) {
            box.replaceChildren();
            tabs = tabCategories.map(category => {
                const tab = document.createElement('button');
                tab.type = 'button';
                tab.dataset.categoryTab = category;
                tab.textContent = category;
                box.appendChild(tab);
                return tab;
            });
        }

        tabs.forEach((tab, index) => {
            const category = tabCategories[index];
            tab.className = 'pill' + (category === cat ? ' on' : '');
            tab.setAttribute('aria-pressed', String(category === cat));
            tab.onclick = () => {
                cat = category;
                renderTabs();
                filter();
            };
        });
    }
    renderTabs();
    $('search').oninput = filter;
    const stockModal = $('stockModal');
    const stockForm = $('stockForm');
    const stockQuantity = $('stockQuantity');
    const stockPrice = $('stockPrice');
    const stockCategoryInput = $('stockCategory');
    const stockCategoryIdInput = $('stockCategoryId');
    let activeStockCategoryName = '';
    const stockBrandInput = $('stockBrand');
    const stockBrandToggle = $('stockBrandToggle');
    const stockBrandMenu = $('stockBrandMenu');
    if (stockBrandInput && stockBrandToggle && stockBrandMenu) {
        function closeStockBrandMenu() {
            stockBrandMenu.classList.add('hidden');
            stockBrandInput.setAttribute('aria-expanded', 'false');
            stockBrandToggle.setAttribute('aria-expanded', 'false');
        }
        function updateStockCategoryBrand() {
            const selectedCategory = selectedStockCategoryName();
            const availableBrands = brandsByCategory[selectedCategory] || [];
            if (stockBrandInput.value && !availableBrands.includes(stockBrandInput.value)) {
                stockBrandInput.value = '';
            }
            closeStockBrandMenu();
        }
        function showStockBrandMenu() {
            const query = stockBrandInput.value.trim().toLocaleLowerCase();
            const selectedCategory = activeStockCategoryName;
            const availableBrands = brandsByCategory[selectedCategory] || [];
            const options = [...document.querySelectorAll('[data-brand-option]')];
            let visibleOptions = 0;
            options.forEach(option => {
                const matches = availableBrands.includes(option.dataset.brandOption)
                    && option.dataset.brandOption.toLocaleLowerCase().includes(query);
                option.classList.toggle('hidden', !matches);
                option.setAttribute('aria-selected', String(option.dataset.brandOption === stockBrandInput.value));
                if (matches) visibleOptions++;
            });
            $('stockBrandNoMatches').classList.toggle('hidden', visibleOptions > 0);
            stockBrandMenu.classList.remove('hidden');
            stockBrandInput.setAttribute('aria-expanded', 'true');
            stockBrandToggle.setAttribute('aria-expanded', 'true');
        }
        stockBrandInput.addEventListener('focus', showStockBrandMenu);
        stockBrandInput.addEventListener('input', showStockBrandMenu);
        stockBrandToggle.onclick = () => {
            if (stockBrandMenu.classList.contains('hidden')) showStockBrandMenu();
            else closeStockBrandMenu();
        };
        $('stockBrandOptions').addEventListener('click', event => {
            const option = event.target.closest('[data-brand-option]');
            if (!option) return;
            stockBrandInput.value = option.dataset.brandOption;
            closeStockBrandMenu();
        });
        document.addEventListener('click', event => {
            if (!event.target.closest('#stockBrandPicker')) closeStockBrandMenu();
        });
        document.addEventListener('keydown', event => {
            if (event.key === 'Escape') closeStockBrandMenu();
        });
    }
    let activeStockImageUrl = '';
    let stockImageObjectUrl = null;
    function showStockImage(imageUrl) {
        if (stockImageObjectUrl) {
            URL.revokeObjectURL(stockImageObjectUrl);
            stockImageObjectUrl = null;
        }
        const preview = $('stockImagePreview');
        preview.classList.toggle('hidden', !imageUrl);
        $('stockImagePlaceholder').classList.toggle('hidden', Boolean(imageUrl));
        if (imageUrl) preview.src = imageUrl;
        else preview.removeAttribute('src');
    }
    const stockImageInput = $('stockImage');
    if (stockImageInput) {
        stockImageInput.addEventListener('change', event => {
            const file = event.target.files[0];
            if (!file) {
                showStockImage(activeStockImageUrl);
                return;
            }
            if (stockImageObjectUrl) URL.revokeObjectURL(stockImageObjectUrl);
            stockImageObjectUrl = URL.createObjectURL(file);
            $('stockImagePreview').src = stockImageObjectUrl;
            $('stockImagePreview').classList.remove('hidden');
            $('stockImagePlaceholder').classList.add('hidden');
        });
    }
    const deleteModal = $('deleteModal');
    let activeDeleteForm = null;
    let activeDeleteButton = null;
    function closeDeleteModal() {
        deleteModal.classList.add('hidden');
        deleteModal.classList.remove('flex');
        if (activeDeleteButton) activeDeleteButton.focus();
        activeDeleteForm = null;
        activeDeleteButton = null;
    }
    function openDeleteModal(button) {
        closeProductActionMenus();
        activeDeleteButton = button;
        activeDeleteForm = button.closest('form');
        $('deleteProductName').textContent = button.dataset.productName;
        $('deleteProductCategory').textContent = `Category: ${button.dataset.productCategory}`;
        deleteModal.classList.remove('hidden');
        deleteModal.classList.add('flex');
        $('cancelDelete').focus();
    }
    if (deleteModal) {
        $('cancelDelete').onclick = closeDeleteModal;
        $('confirmDelete').onclick = () => {
            if (activeDeleteForm) activeDeleteForm.requestSubmit();
        };
        deleteModal.onclick = e => { if (e.target === deleteModal) closeDeleteModal(); };
        document.addEventListener('keydown', e => {
            if (e.key === 'Escape' && !deleteModal.classList.contains('hidden')) closeDeleteModal();
        });
    }
    let activeStockUnit = 'pcs';
    function adjustStockQuantity(amount) {
        const quantity = Number.parseFloat(stockQuantity.value);
        const current = Number.isFinite(quantity) && quantity >= 0 ? quantity : 0;
        stockQuantity.value = String(Math.min(99999999.99, Math.max(0, current + amount)).toFixed(2).replace(/\.?0+$/, ''));
    }
    let activeStockButton = null;
    function closeProductActionMenus() {
        document.querySelectorAll('[data-product-action-menu]').forEach(menu => menu.classList.add('hidden'));
        document.querySelectorAll('[data-product-action-toggle]').forEach(button => button.setAttribute('aria-expanded', 'false'));
    }
    function positionProductActionMenu(button, menu) {
        const buttonRect = button.getBoundingClientRect();
        const menuRect = menu.getBoundingClientRect();
        let top = buttonRect.bottom + 8;
        if (top + menuRect.height > window.innerHeight - 8) {
            top = Math.max(8, buttonRect.top - menuRect.height - 8);
        }
        const left = Math.max(8, Math.min(window.innerWidth - menuRect.width - 8, buttonRect.right - menuRect.width));
        menu.style.top = `${top}px`;
        menu.style.left = `${left}px`;
    }
    function closeStockModal() {
        stockModal.classList.add('hidden');
        stockModal.classList.remove('flex');
        if (activeStockButton) activeStockButton.focus();
        activeStockButton = null;
    }
    $('prod').addEventListener('click', e => {
        const actionToggle = e.target.closest('button[data-product-action-toggle]');
        if (actionToggle) {
            const menu = actionToggle.closest('[data-product-action-container]').querySelector('[data-product-action-menu]');
            const willOpen = menu.classList.contains('hidden');
            closeProductActionMenus();
            menu.classList.toggle('hidden', !willOpen);
            if (willOpen) positionProductActionMenu(actionToggle, menu);
            actionToggle.setAttribute('aria-expanded', String(willOpen));
            return;
        }
        const deleteButton = e.target.closest('button[data-delete-product]');
        if (deleteButton) {
            openDeleteModal(deleteButton);
            return;
        }
        const stockButton = e.target.closest('button[data-edit-stock]');
        if (stockButton) {
            closeProductActionMenus();
            activeStockButton = stockButton;
            stockForm.action = stockButton.dataset.stockUrl;
            $('stockProductName').value = stockButton.dataset.productName;
            activeStockCategoryName = stockButton.dataset.categoryName;
            stockCategoryInput.value = activeStockCategoryName;
            stockCategoryIdInput.value = stockButton.dataset.categoryId;
            $('stockBrand').value = stockButton.dataset.brandName;
            activeStockImageUrl = stockButton.dataset.imageUrl;
            $('stockImage').value = '';
            showStockImage(activeStockImageUrl);
            activeStockUnit = stockButton.dataset.unit;
            $('stockPriceLabel').textContent = activeStockUnit === 'kg' ? 'Price per kg' : activeStockUnit === 'sack' ? 'Price per sack' : 'Price per piece';
            $('stockQuantityLabel').textContent = activeStockUnit === 'kg' ? 'Quantity (kg)' : activeStockUnit === 'sack' ? 'Quantity (sacks)' : 'Quantity (pcs)';
            stockQuantity.step = activeStockUnit === 'kg' ? '0.01' : '1';
            stockQuantity.max = activeStockUnit === 'kg' ? '99999999.99' : '4294967295';
            $('decreaseStockQuantity').onclick = () => adjustStockQuantity(activeStockUnit === 'kg' ? -0.25 : -1);
            $('increaseStockQuantity').onclick = () => adjustStockQuantity(activeStockUnit === 'kg' ? 0.25 : 1);
            stockQuantity.value = stockButton.dataset.stock;
            stockPrice.value = stockButton.dataset.price;
            stockModal.classList.remove('hidden');
            stockModal.classList.add('flex');
            stockPrice.focus();
            return;
        }
    });
    document.addEventListener('click', e => {
        if (!e.target.closest('[data-product-action-container]')) closeProductActionMenus();
    });
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') closeProductActionMenus();
    });
    window.addEventListener('resize', closeProductActionMenus);
    window.addEventListener('scroll', closeProductActionMenus, true);
    if (stockModal) {
        $('closeStock').onclick = closeStockModal;
        stockModal.onclick = e => { if (e.target === stockModal) closeStockModal(); };
        document.addEventListener('keydown', e => {
            if (e.key === 'Escape' && !stockModal.classList.contains('hidden')) closeStockModal();
        });
    }
    if (modal) {
    function openModal(v) {
        modal.classList.toggle('hidden', !v);
        modal.classList.toggle('flex', v);
        if (v) {
            $('pn').focus();
        }
    }
    $('addForm').addEventListener('submit', event => {
        if (!categorySelect.value) {
            event.preventDefault();
            toast('Choose a category or add a new one.');
            categoryPickerButton.focus();
            return;
        }
    });
    $('productUnit').onchange = updateUnitFields;
    updateCategoryInput();
    updateBrandInput();
    updateCreateBrandOptions(false);
    updateUnitFields();
    $('productImage').addEventListener('change', event => {
        const file = event.target.files[0];
        const preview = $('imagePreview');
        if (!file) {
            preview.removeAttribute('src');
            preview.classList.add('hidden');
            return;
        }
        preview.src = URL.createObjectURL(file);
        preview.classList.remove('hidden');
    });
    $('openAdd').onclick = () => openModal(true);
    $('closeAdd').onclick = () => openModal(false);
    modal.onclick = e => { if (e.target === modal) openModal(false); };
    document.addEventListener('keydown', e => { if (e.key === 'Escape') openModal(false); });
    }
</script>
<?php $extraScript = ob_get_clean(); endif; ?>
<?php include resource_path('views/layouts/footer.php'); ?>
