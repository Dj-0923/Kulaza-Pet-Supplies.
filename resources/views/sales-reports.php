<?php
/** @var bool $ok */
/** @var array $sales */
/** @var array $categories */
$cats = $categories ?? [];
$payments = ['Cash', 'GCash'];
foreach ($sales as $sale) {
    $payments[] = $sale['payment'];
}
$cats = array_values(array_unique($cats));
sort($cats, SORT_NATURAL | SORT_FLAG_CASE);
$payments = array_values(array_unique($payments));
sort($payments, SORT_NATURAL | SORT_FLAG_CASE);
$pageTitle = 'Sales Reports';
include resource_path('views/layouts/header.php');
?>
<?php if (!$ok): ?>
<div class="card">You do not have access to this page.</div>
<?php else: ?>
<form class="card flex flex-wrap gap-3 items-end mb-4" id="reportFilters" aria-label="Filter sales reports">
    <div><label class="lbl" for="rf">Date From</label><input id="rf" type="date" name="from" class="inp"></div>
    <div><label class="lbl" for="rt">Date To</label><input id="rt" type="date" name="to" class="inp"></div>
    <div><label class="lbl" for="rc">Category</label>
        <select id="rc" name="category" class="inp"><option value="">All Categories</option><?php foreach($cats as $c): ?><option value="<?= e($c) ?>"><?= e($c) ?></option><?php endforeach; ?></select></div>
    <div><label class="lbl" for="rp">Payment Method</label>
        <select id="rp" name="payment" class="inp"><option value="">All Payment Methods</option><?php foreach($payments as $method): ?><option value="<?= e($method) ?>"><?= e($method) ?></option><?php endforeach; ?></select></div>
    <button type="submit" id="apply" class="btn">Apply Filters</button>
    <button type="reset" id="reset" class="btn-dark">Reset</button>
</form>
<p id="reportSelection" class="text-xs text-gray-400 mb-4" role="status"></p>
<div id="reportPrompt" class="card text-sm text-gray-400 mb-4 hidden">No sales records yet.</div>
<div id="reportResults" class="hidden">
    <div class="grid md:grid-cols-3 gap-4 mb-4" id="rstats"></div>
    <div class="grid md:grid-cols-3 gap-4">
        <div class="card md:col-span-2"><p class="text-sm mb-2" id="ctitle">Last 7-days</p><div class="flex items-end gap-2 h-56" id="chart"></div></div>
        <div class="card text-sm space-y-3" id="summary"></div>
    </div>
</div>
<style>
    @keyframes report-bar-grow {
        from { height: 0; }
        to { height: var(--report-bar-height); }
    }
    .report-bar.is-animated {
        animation: report-bar-grow 700ms cubic-bezier(.2,.75,.25,1) both;
    }
    @media (prefers-reduced-motion: reduce) {
        .report-bar.is-animated { animation: none; }
    }
</style>
<?php endif; ?>
<?php if ($ok): ob_start(); ?><script>
    // Each sale: date (YYYY-MM-DD), payment, items[{name, category, price, qty, unit}].
    const sales = <?= json_encode($sales) ?>;
    const $ = id => document.getElementById(id);
    const ymd = d => d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
    const daysAgo = n => { const d = new Date(); d.setDate(d.getDate() - n); return ymd(d); };
    const escapeHtml = value => String(value).replace(/[&<>"']/g, char => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    })[char]);
    sales.forEach(s => { if (!s.date) s.date = daysAgo(s.days_ago); });
    const sum = a => a.reduce((t, s) => t + s.items.reduce((total, item) => total + item.price * item.qty, 0), 0);

    // Only the chosen category's items are counted, so the graph, totals and summary show that category only.
    function filtered() {
        const f = $('rf').value, t = $('rt').value, c = $('rc').value, p = $('rp').value;
        return sales
            .filter(s => (!f || s.date >= f) && (!t || s.date <= t) && (!p || s.payment === p))
            .map(s => { const items = !c ? s.items : s.items.filter(i => i.category === c);
                        return {...s, items, total: items.reduce((a, i) => a + i.price * i.qty, 0)}; })
            .filter(s => s.items.length);
    }
    function render() {
        const L = filtered();
        const c = $('rc').value, p = $('rp').value, f = $('rf').value, t = $('rt').value;
        $('reportSelection').textContent = `Showing ${f || 'any date'} to ${t || 'any date'} · ${c ? 'Category: ' + c : 'All categories'} · ${p ? 'Payment: ' + p : 'All payment methods'} · ${L.length} transactions`;
        $('reportPrompt').classList.add('hidden');
        $('reportResults').classList.remove('hidden');
        const T = daysAgo(0), all = !c;
        const box = (k, v) => `<div class="card"><p class="text-xs text-gray-400">${k}</p><p class="text-3xl font-bold">${peso(v)}</p></div>`;
        $('rstats').innerHTML = box('Today', sum(L.filter(s => s.date === T))) + box('This Week', sum(L.filter(s => s.date >= daysAgo(6)))) + box('This Month', sum(L.filter(s => s.date.slice(0, 7) === T.slice(0, 7))));
        const days = [6, 5, 4, 3, 2, 1, 0].map(n => { const d = daysAgo(n); return {
            label: new Date(d + 'T00:00').toLocaleDateString('en-US', {weekday: 'short'}), v: sum(L.filter(s => s.date === d))}; });
        const mx = Math.max(1, ...days.map(x => x.v));
        $('ctitle').textContent = 'Last 7-days' + (all ? '' : ' - ' + c + ' sales only');
        $('chart').innerHTML = days.map(x => {
            const barHeight = Math.max(2, x.v / mx * 85);
            return `<div class="flex-1 flex flex-col justify-end items-center h-full text-[10px] text-gray-400"><span>${peso(x.v)}</span><div class="report-bar${x.v > 0 ? ' is-animated' : ''} w-full bg-green-500 rounded-sm" style="--report-bar-height:${barHeight}%;height:${barHeight}%"></div><span class="mt-1">${x.label}</span></div>`;
        }).join('');
        const m = {}; L.forEach(s => s.items.forEach(i => {
            const key = i.name + ' (' + i.unit + ')';
            m[key] = (m[key] || 0) + i.qty;
        }));
        const top = Object.entries(m).sort((a, b) => b[1] - a[1])[0];
        $('summary').innerHTML = `<p>Report Summary${all ? '' : ' - ' + escapeHtml(c)}</p><p><b>Transactions:</b> ${L.length}</p><p><b>Average transaction:</b> ${peso(L.length ? sum(L) / L.length : 0)}</p><p><b>Top product:</b> ${top ? escapeHtml(top[0]) + ' (' + Number(top[1].toFixed(2)) + ' sold)' : 'None yet'}</p>`;
    }
    $('reportFilters').addEventListener('submit', event => { event.preventDefault(); render(); });
    $('reportFilters').addEventListener('reset', () => requestAnimationFrame(render));
    if (sales.length) render();
    else $('reportPrompt').classList.remove('hidden');
</script>
<?php $extraScript = ob_get_clean(); endif; ?>
<?php include resource_path('views/layouts/footer.php'); ?>
