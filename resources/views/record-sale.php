<?php
/** @var bool $ok */
/** @var array $products */
/** @var \Illuminate\Support\ViewErrorBag $errors */
$pageTitle = 'Record Sale';
include resource_path('views/layouts/header.php');
?>
<?php if (!$ok): ?>
<div class="card">You do not have access to this page.</div>
<?php else: ?>
<?php if (session('transaction_status')): ?><div id="transactionToast" class="fixed bottom-6 right-6 z-[60] max-w-sm rounded-lg border border-[#1f271f] bg-[#141914] px-4 py-3 text-sm text-green-400 shadow-lg" role="status" aria-live="polite"><?= e(session('transaction_status')) ?></div><?php endif; ?>
<?php if ($errors->any()): ?>
<div class="card border-red-800 text-red-300 mb-4">
    <?php foreach ($errors->all() as $error): ?><p><?= e($error) ?></p><?php endforeach; ?>
</div>
<?php endif; ?>
<form method="POST" action="<?= e(route('sales.store')) ?>" id="saleForm" class="grid lg:grid-cols-3 gap-4 items-start">
    <?= csrf_field() ?>
    <div class="card lg:col-span-2">
        <input type="text" id="ss" placeholder="Search product..." class="inp mb-4">
        <div class="flex flex-wrap gap-2 mb-4" id="scats" style="display:<?= $products ? 'flex' : 'none' ?>"></div>
        <div class="grid grid-cols-1 min-[400px]:grid-cols-2 md:grid-cols-3 gap-3" id="sgrid"></div>
    </div>

    <div class="card lg:sticky lg:top-6">
        <h2 class="font-semibold mb-3">Order summary</h2>
        <div class="space-y-3 mb-4">
            <div><label class="lbl" for="cust">Customer Name</label><input id="cust" name="customer_name" value="Walk-in Customer" class="inp"></div>
            <div><label class="lbl" for="pay">Payment Method</label>
                <select id="pay" name="payment_method" class="inp">
                    <option value="Cash" <?= old('payment_method', 'Cash') === 'Cash' ? 'selected' : '' ?>>Cash</option>
                    <option value="GCash" <?= old('payment_method') === 'GCash' ? 'selected' : '' ?>>GCash</option>
                </select>
            </div>
            <div id="gcashReferenceWrap" class="<?= old('payment_method') === 'GCash' ? '' : 'hidden' ?>">
                <label class="lbl" for="reference_number">GCash Reference Number</label>
                <input id="reference_number" name="reference_number" type="text" value="<?= e(old('reference_number')) ?>" class="inp" maxlength="100" autocomplete="off" placeholder="Enter GCash reference number" <?= old('payment_method') === 'GCash' ? 'required' : '' ?>>
            </div>
        </div>
        <div id="rows" class="mb-4"></div>
        <div id="hidden"></div>
        <div class="flex justify-between gap-3 bg-[#1a2a1a] rounded-lg px-3 py-2 mb-4"><span>Total Sale</span><span id="tot" class="min-w-0 break-all text-right font-bold">₱0.00</span></div>
        <button type="submit" class="btn block w-full">Save Transaction</button>
    </div>
</form>
<?php endif; ?>
<?php if ($ok): ob_start(); ?><script>
    const products = <?= json_encode($products) ?>;
    let cart = [], scat = 'All';
    const $ = id => document.getElementById(id);
    const transactionToast = $('transactionToast');
    if (transactionToast) setTimeout(() => transactionToast.remove(), 3000);
    const paymentMethod = $('pay');
    const gcashReferenceWrap = $('gcashReferenceWrap');
    const gcashReferenceInput = $('reference_number');
    function updateGcashReferenceField() {
        const isGcash = paymentMethod.value === 'GCash';
        gcashReferenceWrap.classList.toggle('hidden', !isGcash);
        gcashReferenceInput.required = isGcash;
        if (!isGcash) gcashReferenceInput.value = '';
    }
    paymentMethod.addEventListener('change', updateGcashReferenceField);
    updateGcashReferenceField();
    const quantityInCart = productIndex => cart.find(item => item.p === productIndex)?.q || 0;
    const availableStock = productIndex => products[productIndex].stock - quantityInCart(productIndex);
    const formatQuantity = quantity => Number(quantity.toFixed(2)).toString();
    const quantityStep = product => product.unit === 'kg' ? 0.25 : 1;

    function renderProducts() {
        const q = $('ss').value.toLowerCase();
        const list = products.map((p, i) => ({p, i})).filter(({p}) => (scat === 'All' || p.category === scat) && `${p.name} ${p.brand || ''}`.toLowerCase().includes(q));
        $('sgrid').innerHTML = list.map(({p, i}) => {
            const stock = availableStock(i);
            const outOfStock = stock < (p.unit === 'kg' ? 0.01 : 1);
            return `<button type="button" ${outOfStock ? 'disabled' : ''} onclick="addToCart(${i})" class="text-left rounded-xl p-3 border border-[#1f271f] bg-[#0b0e0b] hover:border-green-600 ${outOfStock ? 'opacity-40' : ''}">
            ${p.image_url ? `<img src="${p.image_url}" alt="" class="mb-3 h-28 w-full rounded-lg object-cover">` : `<div class="mb-3 flex h-28 items-center justify-center rounded-lg border border-[#1f271f] text-xs text-gray-500">No image</div>`}
            <p class="font-semibold">${p.name}</p><p class="text-xs text-gray-400">${p.brand || 'No brand'} · ${p.category}</p>
            <p class="mt-2 text-green-400 font-semibold">${peso(p.price)} /${p.unit}</p><p class="text-xs text-gray-500">${outOfStock ? 'Out of stock' : formatQuantity(stock) + ' ' + (p.unit === 'sack' ? 'sacks' : p.unit) + ' in stock'}</p></button>`;
        }).join('')
            || `<p class="col-span-full text-center text-gray-400 py-12">${products.length ? 'No products match. Try another category or search.' : 'No products have been added yet.'}</p>`;
    }
    function renderCart() {
        $('rows').innerHTML = cart.map((r, i) => { const p = products[r.p]; return `<div class="py-2 border-t border-[#1f271f]">
            <div class="flex justify-between text-sm"><span class="font-semibold">${p.name}</span><span id="lt${i}">${peso(p.price * r.q)}</span></div>
            <div class="flex items-center gap-2 mt-1 text-xs text-gray-400"><span>${peso(p.price)} /${p.unit}</span><span class="flex-1"></span>
            <button type="button" class="btn-dark !px-2 !py-0" onclick="chg(${i},-1)" aria-label="Decrease">-</button>
            <input type="number" inputmode="${p.unit === 'kg' ? 'decimal' : 'numeric'}" min="${p.unit === 'kg' ? '0.01' : '1'}" max="${p.stock}" step="${p.unit === 'kg' ? '0.01' : '1'}" value="${formatQuantity(r.q)}" aria-label="Quantity in ${p.unit}" class="inp !w-20 !py-1 text-center" onfocus="this.select()" oninput="typeQty(${i},this)" onchange="renderCart();renderProducts()">
            <button type="button" class="btn-dark !px-2 !py-0" onclick="chg(${i},1)" aria-label="Increase">+</button>
            <button type="button" class="btn-red !py-0" onclick="removeFromCart(${i})" aria-label="Remove">X</button></div></div>`; }).join('')
            || '<p class="text-sm text-gray-400 py-4 text-center border-t border-[#1f271f]">No items yet. Click a product to add it.</p>';
        $('hidden').innerHTML = cart.map((r, i) => `<input type="hidden" name="items[${i}][product_id]" value="${products[r.p].id}"><input type="hidden" name="items[${i}][quantity]" value="${r.q}">`).join('');
        updTot();
    }
    function updTot() { $('tot').textContent = peso(cart.reduce((t, r) => t + products[r.p].price * r.q, 0)); }
    function typeQty(i, el) {
        const r = cart[i], p = products[r.p], input = Number(el.value);
        if (!Number.isFinite(input) || input <= 0) return;
        if (p.unit !== 'kg' && !Number.isInteger(input)) {
            toast('Quantity for ' + p.name + ' must be a whole number of ' + p.unit + '.');
            el.value = r.q;
            return;
        }
        const quantity = p.unit === 'kg' ? Math.round(input * 100) / 100 : input;
        const max = p.stock;
        if (quantity > max) {
            toast('Only ' + formatQuantity(max) + ' ' + p.unit + ' in stock for ' + p.name + '.');
            el.value = formatQuantity(max);
        }
        r.q = Math.min(quantity, max);
        $('lt' + i).textContent = peso(p.price * r.q);
        updTot();
        $('hidden').querySelectorAll('input')[i * 2 + 1].value = r.q;
        renderProducts();
    }
    function addToCart(i) {
        const p = products[i], step = quantityStep(p), r = cart.find(x => x.p === i);
        if (availableStock(i) < (p.unit === 'kg' ? 0.01 : 1)) return toast('No more stock for ' + p.name + '.');
        if (r) r.q = Math.min(p.stock, Math.round((r.q + step) * 100) / 100);
        else cart.push({p: i, q: Math.min(1, p.stock)});
        renderCart();
        renderProducts();
    }
    function chg(i, d) {
        const r = cart[i], p = products[r.p], step = quantityStep(p);
        if (d > 0 && r.q >= p.stock) return toast('No more stock for ' + p.name + '.');
        r.q = Math.min(p.stock, Math.round((r.q + d * step) * 100) / 100);
        if (r.q < (p.unit === 'kg' ? 0.01 : 1)) cart.splice(i, 1);
        renderCart(); renderProducts();
    }
    function removeFromCart(i) {
        cart.splice(i, 1);
        renderCart();
        renderProducts();
    }
    function renderSTabs() {
        const box = $('scats');
        const categories = [...new Set(products.map(product => product.category))];
        box.style.display = categories.length ? 'flex' : 'none';
        if (!categories.includes(scat)) scat = 'All';
        makeTabs(box, scat, c => { scat = c; renderSTabs(); renderProducts(); }, categories);
    }
    renderSTabs();
    $('ss').oninput = renderProducts;
    $('saleForm').onsubmit = e => {
        if (!cart.length) { e.preventDefault(); return toast('Add at least one product.'); }
    };
    renderProducts(); renderCart();
</script>
<?php $extraScript = ob_get_clean(); endif; ?>
<?php include resource_path('views/layouts/footer.php'); ?>
