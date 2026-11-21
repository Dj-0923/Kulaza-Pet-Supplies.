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
<?php if (session('stock_status')): ?><div id="stockUpdateToast" class="fixed bottom-6 right-6 z-[60] max-w-sm rounded-lg border border-[#1f271f] bg-[#141914] px-4 py-3 text-sm text-green-400 shadow-lg" role="status" aria-live="polite"><?= e(session('stock_status')) ?></div><?php endif; ?>
<?php if ($errors->any()): ?><div class="card text-red-300 mb-4"><?php foreach ($errors->all() as $error): ?><p><?= e($error) ?></p><?php endforeach; ?></div><?php endif; ?>
<div class="flex flex-wrap gap-2 mb-4" id="cats" style="display:<?= $products ? 'flex' : 'none' ?>"></div>
<div class="card" style="min-height:26rem">
    <div class="flex gap-3 mb-4">
        <input type="text" id="search" placeholder="Search product..." class="inp">
        <?php if(!$isStaff): ?>
            <button type="button" id="openAdd" class="btn whitespace-nowrap">+ Add Product</button>
        <?php endif; ?>
    </div>
    <div class="overflow-x-auto">
        <table class="tbl" style="table-layout:fixed;min-width:480px">
            <colgroup><col style="width:23%"><col style="width:30%"><col style="width:17%"><?php if(!$isStaff): ?><col style="width:15%"><col style="width:15%"><?php else: ?><col style="width:30%"><?php endif; ?></colgroup>
            <tr><th>Category</th><th>Product</th><th>Price</th><th>Stock</th><?php if(!$isStaff): ?><th style="text-align:right">Actions</th><?php endif; ?></tr>
            <tbody id="prod">
                <?php foreach($products as $p): ?>
                    <tr data-id="<?= e($p['id']) ?>" data-cat="<?= e($p['category']) ?>" data-name="<?= e(strtolower($p['name'])) ?>">
                        <td><?= e($p['category']) ?></td><td><span class="font-semibold"><?= e($p['name']) ?></span><?php if (!empty($p['weight'])): ?><span class="block text-xs text-gray-400"><?= e($p['weight']) ?> kg/package</span><?php endif; ?></td>
                        <td>₱<?= e(number_format($p['price'], 2)) ?> /<?= e($p['unit']) ?></td>
                        <td><?= e($p['unit'] === 'kg' ? rtrim(rtrim(number_format($p['stock'], 2, '.', ''), '0'), '.') : number_format($p['stock'])) ?> <?= e($p['unit']) ?></td>
                        <?php if(!$isStaff): ?>
                            <td>
                                <div class="relative flex justify-end" data-product-action-container>
                                    <button type="button" class="btn-dark !rounded-lg !p-2" data-product-action-toggle aria-label="Product actions for <?= e($p['name']) ?>" aria-haspopup="true" aria-controls="product-actions-<?= e($p['id']) ?>" aria-expanded="false">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                            <circle cx="12" cy="5" r="1.8"/><circle cx="12" cy="12" r="1.8"/><circle cx="12" cy="19" r="1.8"/>
                                        </svg>
                                    </button>
                                    <div id="product-actions-<?= e($p['id']) ?>" class="hidden fixed z-[80] min-w-32 rounded-lg border border-[#1f271f] bg-[#141914] p-1 shadow-xl" data-product-action-menu>
                                        <button type="button" class="w-full rounded-md px-3 py-2 text-left text-sm hover:bg-[#1f271f]" data-edit-stock="<?= e($p['id']) ?>" data-stock-url="<?= e(route('products.stock', $p['id'])) ?>" data-product-name="<?= e($p['name']) ?>" data-category-id="<?= e($p['category_id']) ?>" data-stock="<?= e($p['stock']) ?>" data-price="<?= e($p['price']) ?>" data-unit="<?= e($p['unit']) ?>">Edit</button>
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
    <form method="POST" action="<?= e(route('products.store')) ?>" id="addForm" class="card w-full max-w-md" style="background:#171c15;padding:24px">
        <?= csrf_field() ?>
        <h2 class="text-lg font-bold mb-4">Add Product</h2>
        <div class="space-y-4">
            <div><label class="lbl" for="pn">Product name</label><input id="pn" name="name" value="<?= e(old('name')) ?>" class="inp" placeholder="Enter product name" autocomplete="off" required></div>
            <div>
                <label class="lbl" for="categoryPickerButton">Category</label>
                <?php $selectedCategory = old('category', ''); ?>
                <?php $selectedCategoryName = collect($categoryOptions)->firstWhere('name', $selectedCategory)['name'] ?? ($selectedCategory === '__new_category__' ? '+ Add new category' : 'Choose a category'); ?>
                <input id="pc" name="category" type="hidden" value="<?= e($selectedCategory) ?>">
                <div id="categoryPicker" class="relative">
                    <button type="button" id="categoryPickerButton" class="inp flex items-center justify-between text-left" aria-haspopup="listbox" aria-expanded="false" aria-controls="categoryPickerMenu">
                        <span id="categoryPickerLabel"><?= e($selectedCategoryName) ?></span>
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
            <div id="newCategoryWrap" class="hidden">
                <label class="lbl" for="newCategory">New category</label>
                <input id="newCategory" name="new_category" value="<?= e(old('new_category')) ?>" class="inp" placeholder="Enter new category" autocomplete="off" disabled>
            </div>
            <div>
                <label class="lbl" for="productUnit">Unit sold by</label>
                <select id="productUnit" name="unit" class="inp" required>
                    <option value="pcs" <?= old('unit', 'pcs') === 'pcs' ? 'selected' : '' ?>>Pieces (pcs)</option>
                    <option value="kg" <?= old('unit') === 'kg' ? 'selected' : '' ?>>Kilograms (kg)</option>
                </select>
                <p class="text-xs text-gray-400 mt-1">Choose kg for loose feeds and pet food sold by weight. Price and stock will be per kilogram.</p>
            </div>
            <div>
                <label class="flex items-center gap-2 text-sm text-gray-300">
                    <input id="hasPackageWeight" type="checkbox" class="accent-green-600" <?= old('weight') ? 'checked' : '' ?>>
                    Show the package's stated weight
                </label>
            </div>
            <div id="weightWrap" class="hidden">
                <label class="lbl" for="productWeight">Package weight (kg)</label>
                <input id="productWeight" name="weight" type="number" min="0.01" step="0.01" value="<?= e(old('weight')) ?>" class="inp" placeholder="Weight of one package" disabled>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div><label id="priceLabel" class="lbl" for="pp">Price per piece</label><input id="pp" name="price" type="number" min="0" step="0.01" value="<?= e(old('price')) ?>" class="inp" placeholder="Enter price per piece" required></div>
                <div><label id="stockLabel" class="lbl" for="pk">Stock (pcs)</label><input id="pk" name="stock" type="number" min="0" step="1" value="<?= e(old('stock')) ?>" class="inp" placeholder="Enter pieces in stock" required></div>
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
<?php endif; ?>
<?php if(!$isStaff && $ok): ?>
<div id="stockModal" class="hidden fixed inset-0 z-50 items-center justify-center p-4" style="background:rgba(0,0,0,.7)" role="dialog" aria-modal="true" aria-labelledby="stockModalTitle">
    <form method="POST" id="stockForm" class="card w-full max-w-md" style="background:#171c15;padding:24px">
        <?= csrf_field() ?>
        <h2 id="stockModalTitle" class="text-lg font-bold mb-2">Edit Product</h2>
        <p id="stockProductName" class="text-sm text-gray-400 mb-4"></p>
        <label class="lbl" for="stockCategory">Category</label>
        <select id="stockCategory" name="category_id" class="inp mb-4" required>
            <?php foreach ($categoryOptions as $category): ?>
                <option value="<?= e($category->id) ?>"><?= e($category->name) ?></option>
            <?php endforeach; ?>
        </select>
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
    const stockUpdateToast = $('stockUpdateToast');
    if (stockUpdateToast) setTimeout(() => stockUpdateToast.remove(), 3000);
    const categoryPickerButton = $('categoryPickerButton');
    const categoryPickerMenu = $('categoryPickerMenu');
    const categoryPickerLabel = $('categoryPickerLabel');
    const categorySelect = $('pc');
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
            categorySelect.value = option.dataset.selectCategory;
            categoryPickerLabel.textContent = option.dataset.selectCategory;
            updateCategoryInput();
            closeCategoryPicker();
        });
        $('chooseNewCategory').onclick = () => {
            categorySelect.value = '__new_category__';
            categoryPickerLabel.textContent = '+ Add new category';
            updateCategoryInput();
            closeCategoryPicker();
        };
        document.addEventListener('click', event => {
            if (!event.target.closest('#categoryPicker')) closeCategoryPicker();
        });
    }
    function filter() {
        const q = $('search').value.toLowerCase(); let n = 0;
        const rows = document.querySelectorAll('#prod tr[data-cat]');
        rows.forEach(r => {
            const show = (cat === 'All' || r.dataset.cat === cat) && r.dataset.name.includes(q);
            r.classList.toggle('hidden', !show); if (show) n++;
        });
        $('cats').hidden = rows.length === 0;
        $('none').querySelector('td').textContent = rows.length === 0
            ? 'No products have been added yet.'
            : 'No products match. Try another category or search.';
        $('none').classList.toggle('hidden', n > 0);
    }
    const modal = $('modal');
    const newCategoryWrap = $('newCategoryWrap');
    const newCategoryInput = $('newCategory');
    const weightWrap = $('weightWrap');
    const weightInput = $('productWeight');
    function updateCategoryInput() {
        const isNew = categorySelect.value === '__new_category__';
        newCategoryWrap.classList.toggle('hidden', !isNew);
        newCategoryInput.disabled = !isNew;
        newCategoryInput.required = isNew;
        if (!isNew) newCategoryInput.value = '';
    }
    function updateWeightInput() {
        const hasPackageWeight = $('hasPackageWeight').checked;
        weightWrap.classList.toggle('hidden', !hasPackageWeight);
        weightInput.disabled = !hasPackageWeight;
    }
    function updateUnitFields() {
        const isKg = $('productUnit').value === 'kg';
        $('priceLabel').textContent = isKg ? 'Price per kg' : 'Price per piece';
        $('pp').placeholder = isKg ? 'Enter price per kilogram' : 'Enter price per piece';
        $('stockLabel').textContent = isKg ? 'Stock (kg)' : 'Stock (pcs)';
        $('pk').step = isKg ? '0.01' : '1';
        $('pk').placeholder = isKg ? 'Enter kilograms in stock' : 'Enter pieces in stock';
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
            $('stockProductName').textContent = stockButton.dataset.productName;
            $('stockCategory').value = stockButton.dataset.categoryId;
            activeStockUnit = stockButton.dataset.unit;
            $('stockPriceLabel').textContent = activeStockUnit === 'kg' ? 'Price per kg' : 'Price per piece';
            $('stockQuantityLabel').textContent = activeStockUnit === 'kg' ? 'Quantity (kg)' : 'Quantity (pcs)';
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
        }
    });
    $('hasPackageWeight').onchange = updateWeightInput;
    $('productUnit').onchange = updateUnitFields;
    updateCategoryInput();
    updateWeightInput();
    updateUnitFields();
    $('openAdd').onclick = () => openModal(true);
    $('closeAdd').onclick = () => openModal(false);
    modal.onclick = e => { if (e.target === modal) openModal(false); };
    document.addEventListener('keydown', e => { if (e.key === 'Escape') openModal(false); });
    }
</script>
<?php $extraScript = ob_get_clean(); endif; ?>
<?php include resource_path('views/layouts/footer.php'); ?>
