<?php
/** @var bool $ok */
/** @var array $transactions */
$payments = array_values(array_unique(array_column($transactions, 'payment')));
sort($payments, SORT_NATURAL | SORT_FLAG_CASE);
$pageTitle = 'Transaction History';
include resource_path('views/layouts/header.php');
?>
<?php if (!$ok): ?>
<div class="card">You do not have access to this page.</div>
<?php else: ?>
<?php if (session('status')): ?><div class="card text-green-400 mb-4" role="status"><?= e(session('status')) ?></div><?php endif; ?>
<div class="card">
    <div class="flex flex-wrap gap-3 mb-4" role="search" aria-label="Filter transaction history">
        <label class="sr-only" for="transaction-search">Search transactions</label>
        <input id="transaction-search" type="search" name="search" placeholder="Search transaction no, customer, items..." class="inp flex-1 min-w-52" autocomplete="off">
        <label class="sr-only" for="transaction-date">Filter by date</label>
        <input id="transaction-date" type="date" name="date" class="inp !w-44">
        <label class="sr-only" for="transaction-payment">Filter by payment method</label>
        <select id="transaction-payment" name="payment" class="inp !w-48">
            <option value="">All Payment Methods</option>
            <?php foreach ($payments as $method): ?><option value="<?= e($method) ?>"><?= e($method) ?></option><?php endforeach; ?>
        </select>
        <button type="button" id="clear-transaction-filters" class="btn-dark">Clear</button>
    </div>
    <p id="transaction-filter-status" class="text-xs text-gray-400 mb-3" role="status" aria-live="polite">Showing <?= count($transactions) ?> of <?= count($transactions) ?> transactions</p>
    <div class="overflow-x-auto">
    <table class="tbl"><thead><tr><th>Transaction No.</th><th>Customer</th><th>Items</th><th>Payment Method</th><th>Total</th><th>Date</th><?php if (!$isStaff): ?><th style="text-align:right">Actions</th><?php endif; ?></tr></thead><tbody id="transaction-rows">
        <?php if (!$transactions): ?>
            <tr><td colspan="<?= $isStaff ? 6 : 7 ?>" class="text-gray-400 text-center" style="padding:3rem 0">No transactions yet.</td></tr>
        <?php endif; ?>
        <?php foreach ($transactions as $t): ?>
            <tr data-transaction-row data-transaction-no="<?= e($t['transaction_no']) ?>" data-customer="<?= e($t['customer']) ?>" data-payment-method="<?= e($t['payment']) ?>" data-reference-number="<?= e($t['reference_number'] ?? '') ?>" data-date="<?= e($t['date']) ?>" data-total="<?= e($t['total']) ?>" data-items="<?= e(json_encode($t['item_details'])) ?>" data-search="<?= e(mb_strtolower($t['invoice'].' '.$t['transaction_no'].' '.$t['customer'].' '.$t['items'].' '.$t['payment'].' '.($t['reference_number'] ?? ''))) ?>" data-payment="<?= e($t['payment']) ?>">
                <td class="font-semibold"><?= e($t['transaction_no']) ?></td>
                <td><?= e($t['customer']) ?></td>
                <td><button type="button" class="btn-dark !px-3 !py-1" data-view-transaction>See items</button></td>
                <td><?= e($t['payment']) ?></td>
                <td class="font-semibold">₱<?= e(number_format($t['total'], 2)) ?></td>
                <td><?= e($t['date']) ?></td>
                <?php if (!$isStaff): ?>
                    <td>
                        <div class="relative flex justify-end" data-transaction-action-container>
                            <button type="button" class="btn-dark !rounded-lg !p-2" data-transaction-action-toggle aria-label="Transaction actions for <?= e($t['transaction_no']) ?>" aria-haspopup="true" aria-controls="transaction-actions-<?= e($t['id']) ?>" aria-expanded="false">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                    <circle cx="12" cy="5" r="1.8"/><circle cx="12" cy="12" r="1.8"/><circle cx="12" cy="19" r="1.8"/>
                                </svg>
                            </button>
                            <div id="transaction-actions-<?= e($t['id']) ?>" class="hidden fixed z-[80] min-w-40 rounded-lg border border-[#1f271f] bg-[#141914] p-1 shadow-xl" data-transaction-action-menu>
                                <button type="button" class="w-full rounded-md px-3 py-2 text-left text-sm hover:bg-[#1f271f]" data-view-transaction>View items</button>
                                <form method="POST" action="<?= e(route('transactions.destroy', $t['id'])) ?>" onsubmit="return confirm('Delete this transaction? Product stock will not be changed.');">
                                    <?= csrf_field() ?>
                                    <?= method_field('DELETE') ?>
                                    <button type="submit" class="w-full rounded-md px-3 py-2 text-left text-sm text-red-300 hover:bg-[#3a1c20]" data-delete-transaction>Delete</button>
                                </form>
                            </div>
                        </div>
                    </td>
                <?php endif; ?>
            </tr>
        <?php endforeach; ?>
        <?php if ($transactions): ?><tr id="transaction-no-matches" hidden><td colspan="<?= $isStaff ? 6 : 7 ?>" class="text-gray-400 text-center" style="padding:3rem 0">No transactions match these filters.</td></tr><?php endif; ?>
    </tbody></table>
    </div>
</div>
<?php if ($transactions): ?>
<div id="transactionDetailsModal" class="hidden fixed inset-0 z-[70] items-center justify-center p-4 backdrop-blur-sm" style="background:rgba(4,7,4,.82)" role="dialog" aria-modal="true" aria-labelledby="transactionDetailsTitle">
    <div class="card w-full max-w-lg" style="background:#141914;padding:24px;box-shadow:0 24px 80px rgba(0,0,0,.55)">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h2 id="transactionDetailsTitle" class="text-xl font-bold">Transaction details</h2>
                <p id="transactionDetailsNumber" class="mt-1 text-xs text-gray-400"></p>
            </div>
            <button type="button" id="closeTransactionDetails" class="btn-dark !px-3 !py-1" aria-label="Close transaction details">Close</button>
        </div>
        <div class="mt-5 grid grid-cols-2 gap-3 rounded-lg border border-[#252d25] bg-[#0b0e0b] p-3 text-sm">
            <div><p class="text-xs text-gray-400">Customer Name</p><p id="transactionDetailsCustomer" class="mt-1 font-semibold"></p></div>
            <div><p class="text-xs text-gray-400">Payment Method</p><p id="transactionDetailsPayment" class="mt-1 font-semibold"></p></div>
            <div><p class="text-xs text-gray-400">Date</p><p id="transactionDetailsDate" class="mt-1 font-semibold"></p></div>
            <div id="transactionDetailsReferenceWrap" class="hidden"><p class="text-xs text-gray-400">Reference No.</p><p id="transactionDetailsReference" class="mt-1 break-all font-semibold"></p></div>
        </div>
        <h3 id="transactionDetailsItemsTitle" class="mt-4 mb-2 text-sm font-semibold">Items</h3>
        <div id="transactionDetailsItems" class="max-h-64 space-y-2 overflow-y-auto" aria-labelledby="transactionDetailsItemsTitle"></div>
        <div class="mt-4 flex justify-between border-t border-[#252d25] pt-3 font-bold">
            <span>Total</span><span id="transactionDetailsTotal"></span>
        </div>
    </div>
</div>
<?php endif; ?>
<?php if ($transactions): ob_start(); ?><script>
    (() => {
        const search = document.getElementById('transaction-search');
        const date = document.getElementById('transaction-date');
        const payment = document.getElementById('transaction-payment');
        const status = document.getElementById('transaction-filter-status');
        const rows = [...document.querySelectorAll('[data-transaction-row]')];
        const empty = document.getElementById('transaction-no-matches');
        const transactionRows = document.getElementById('transaction-rows');
        const detailsModal = document.getElementById('transactionDetailsModal');
        let activeDetailsButton = null;
        const closeDetails = () => {
            detailsModal.classList.add('hidden');
            detailsModal.classList.remove('flex');
            if (activeDetailsButton) activeDetailsButton.focus();
            activeDetailsButton = null;
        };
        const openDetails = button => {
            const row = button.closest('[data-transaction-row]');
            activeDetailsButton = button.closest('[data-transaction-action-container]')
                ? row.querySelector('td [data-view-transaction]')
                : button;
            document.getElementById('transactionDetailsNumber').textContent = `Transaction No.: ${row.dataset.transactionNo}`;
            document.getElementById('transactionDetailsCustomer').textContent = row.dataset.customer;
            document.getElementById('transactionDetailsPayment').textContent = row.dataset.paymentMethod;
            document.getElementById('transactionDetailsDate').textContent = row.dataset.date;
            document.getElementById('transactionDetailsTotal').textContent = `₱${Number(row.dataset.total).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
            const referenceWrap = document.getElementById('transactionDetailsReferenceWrap');
            referenceWrap.classList.toggle('hidden', !row.dataset.referenceNumber);
            document.getElementById('transactionDetailsReference').textContent = row.dataset.referenceNumber;
            const itemsContainer = document.getElementById('transactionDetailsItems');
            itemsContainer.replaceChildren();
            JSON.parse(row.dataset.items).forEach(item => {
                const itemRow = document.createElement('div');
                itemRow.className = 'flex items-start justify-between gap-4 border-b border-[#252d25] pb-2 text-sm';
                const itemName = document.createElement('span');
                itemName.className = 'min-w-0 flex-1';
                itemName.textContent = `${item.name} × ${item.quantity} ${item.unit}`;
                const itemPrice = document.createElement('span');
                itemPrice.className = 'shrink-0 font-semibold';
                itemPrice.textContent = `₱${Number(item.subtotal).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
                itemRow.append(itemName, itemPrice);
                itemsContainer.appendChild(itemRow);
            });
            detailsModal.classList.remove('hidden');
            detailsModal.classList.add('flex');
            document.getElementById('closeTransactionDetails').focus();
        };
        const closeActionMenus = () => {
            document.querySelectorAll('[data-transaction-action-menu]').forEach(menu => menu.classList.add('hidden'));
            document.querySelectorAll('[data-transaction-action-toggle]').forEach(button => button.setAttribute('aria-expanded', 'false'));
        };
        const positionActionMenu = (button, menu) => {
            const buttonRect = button.getBoundingClientRect();
            const menuRect = menu.getBoundingClientRect();
            let top = buttonRect.bottom + 8;
            if (top + menuRect.height > window.innerHeight - 8) top = Math.max(8, buttonRect.top - menuRect.height - 8);
            const left = Math.max(8, Math.min(window.innerWidth - menuRect.width - 8, buttonRect.right - menuRect.width));
            menu.style.top = `${top}px`;
            menu.style.left = `${left}px`;
        };
        transactionRows.addEventListener('click', event => {
            const toggle = event.target.closest('[data-transaction-action-toggle]');
            if (toggle) {
                const menu = toggle.closest('[data-transaction-action-container]').querySelector('[data-transaction-action-menu]');
                const willOpen = menu.classList.contains('hidden');
                closeActionMenus();
                menu.classList.toggle('hidden', !willOpen);
                if (willOpen) positionActionMenu(toggle, menu);
                toggle.setAttribute('aria-expanded', String(willOpen));
                return;
            }
            const detailsButton = event.target.closest('[data-view-transaction]');
            if (detailsButton) {
                closeActionMenus();
                openDetails(detailsButton);
                return;
            }
            if (event.target.closest('[data-delete-transaction]')) closeActionMenus();
        });
        document.addEventListener('click', event => {
            if (!event.target.closest('[data-transaction-action-container]')) closeActionMenus();
        });
        document.addEventListener('keydown', event => {
            if (event.key === 'Escape') {
                closeActionMenus();
                if (!detailsModal.classList.contains('hidden')) closeDetails();
            }
        });
        document.getElementById('closeTransactionDetails').addEventListener('click', closeDetails);
        detailsModal.addEventListener('click', event => {
            if (event.target === detailsModal) closeDetails();
        });
        window.addEventListener('resize', closeActionMenus);
        window.addEventListener('scroll', closeActionMenus, true);
        const render = () => {
            const query = search.value.trim().toLocaleLowerCase();
            let visible = 0;
            rows.forEach(row => {
                const match = (!query || row.dataset.search.includes(query))
                    && (!date.value || row.dataset.date === date.value)
                    && (!payment.value || row.dataset.payment === payment.value);
                row.hidden = !match;
                if (match) visible++;
            });
            empty.hidden = visible > 0;
            status.textContent = `Showing ${visible} of ${rows.length} transactions`;
        };
        search.addEventListener('input', render);
        date.addEventListener('change', render);
        payment.addEventListener('change', render);
        document.getElementById('clear-transaction-filters').addEventListener('click', () => {
            search.value = '';
            date.value = '';
            payment.value = '';
            render();
            search.focus();
        });
        render();
    })();
</script>
<?php $extraScript = ob_get_clean(); endif; ?>
<?php endif; ?>
<?php include resource_path('views/layouts/footer.php'); ?>
