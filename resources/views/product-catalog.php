<?php
/** @var bool $ok */
/** @var array $products */
$pageTitle = 'Products';
include resource_path('views/layouts/header.php');
?>
<?php if (!$ok): ?>
<div class="card">You do not have access to this page.</div>
<?php else: ?>
<div class="mb-5 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
    <div id="catalogCategories" class="flex flex-wrap gap-2" role="group" aria-label="Filter products by category"></div>
    <label class="sr-only" for="catalogSearch">Search products</label>
    <input id="catalogSearch" type="search" class="inp md:max-w-sm" placeholder="Search products or categories">
</div>
<div id="catalogProducts" class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">
    <?php foreach ($products as $product):
        $stock = $product['unit'] === 'kg'
            ? rtrim(rtrim(number_format((float) $product['stock'], 2, '.', ''), '0'), '.')
            : number_format((int) $product['stock']);
    ?>
        <article class="card !p-3" data-catalog-product data-category="<?= e($product['category']) ?>" data-search="<?= e(mb_strtolower($product['name'].' '.$product['category'])) ?>">
            <h2 class="truncate font-semibold"><?= e($product['name']) ?></h2>
            <p class="truncate text-xs text-gray-400"><?= e($product['category']) ?></p>
            <p class="mt-3 font-semibold text-green-300">₱<?= e(number_format((float) $product['price'], 2)) ?> /<?= e($product['unit']) ?></p>
            <p class="mt-1 text-xs text-gray-400"><?= e($stock) ?> <?= e($product['unit']) ?> in stock</p>
        </article>
    <?php endforeach; ?>
</div>
<div id="catalogEmpty" class="card hidden mt-4 text-center text-sm text-gray-400" role="status"></div>
<?php
$extraScript = <<<'HTML'
<script>
    (() => {
        const cards = [...document.querySelectorAll('[data-catalog-product]')];
        const categoryFilter = document.getElementById('catalogCategories');
        const search = document.getElementById('catalogSearch');
        const empty = document.getElementById('catalogEmpty');
        const categories = [...new Set(cards.map(card => card.dataset.category))]
            .sort((a, b) => a.localeCompare(b));
        let selectedCategory = 'All';

        categoryFilter.style.display = cards.length ? 'flex' : 'none';

        function filterProducts() {
            const query = search.value.trim().toLocaleLowerCase();
            let visible = 0;

            cards.forEach(card => {
                const matches = (selectedCategory === 'All' || card.dataset.category === selectedCategory)
                    && card.dataset.search.includes(query);
                card.classList.toggle('hidden', !matches);
                if (matches) visible++;
            });

            empty.textContent = cards.length
                ? 'No products match this category or search.'
                : 'No products have been added yet.';
            empty.classList.toggle('hidden', visible > 0);
        }

        makeTabs(categoryFilter, selectedCategory, category => {
            selectedCategory = category;
            filterProducts();
        }, categories);
        search.addEventListener('input', filterProducts);
        filterProducts();
    })();
</script>
HTML;
?>
<?php endif; ?>
<?php include resource_path('views/layouts/footer.php'); ?>
