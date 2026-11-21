<?php

use App\Models\Category;
use App\Models\PriceRecord;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::create([
        'name' => 'Test Admin',
        'email' => 'test-admin@kulaza.local',
        'username' => 'test_admin',
        'role' => 'admin',
        'password' => 'secure-pass-123',
    ]));
});

function createSaleProduct(int $stock = 5): Product
{
    $category = Category::create(['name' => 'Pet Food']);

    return Product::create([
        'category_id' => $category->id,
        'name' => 'Dog Food',
        'price' => 12.50,
        'stock' => $stock,
    ]);
}

test('saved sales update stock and persist transaction details', function () {
    $product = createSaleProduct();

    foreach ([2, 1] as $quantity) {
        $this->post(route('sales.store'), [
            'customer_name' => 'Walk-in Customer',
            'payment_method' => 'Cash',
            'items' => [
                ['product_id' => $product->id, 'quantity' => $quantity],
            ],
        ])->assertRedirect(route('sales.create'));
    }

    $this->get(route('sales.create'))
        ->assertOk()
        ->assertSee('Transaction saved successfully. Transaction No.: ', false);

    $product->refresh();
    expect(preg_match('/^[A-Z0-9]{12}$/', Transaction::firstOrFail()->invoice))->toBe(1);
    expect($product->stock)->toBe(2.0);
    expect(Transaction::count())->toBe(2);
    expect(Transaction::first()->reference_number)->toBeNull();
});

test('gcash sales require and save a reference number', function () {
    $product = createSaleProduct();

    $this->get(route('sales.create'))
        ->assertOk()
        ->assertSee('id="gcashReferenceWrap" class="hidden"', false)
        ->assertSee('name="reference_number"', false)
        ->assertSee('updateGcashReferenceField()', false);

    $this->from(route('sales.create'))->post(route('sales.store'), [
        'payment_method' => 'GCash',
        'items' => [['product_id' => $product->id, 'quantity' => 1]],
    ])->assertRedirect(route('sales.create'))
        ->assertSessionHasErrors('reference_number');

    expect(Transaction::count())->toBe(0)
        ->and($product->fresh()->stock)->toBe(5.0);

    $this->post(route('sales.store'), [
        'payment_method' => 'GCash',
        'reference_number' => '1234567890123',
        'items' => [['product_id' => $product->id, 'quantity' => 1]],
    ])->assertRedirect(route('sales.create'));

    $transaction = Transaction::firstOrFail();
    expect($transaction->reference_number)->toBe('1234567890123');

    $this->get(route('transactions'))
        ->assertOk()
        ->assertSee('Transaction No.')
        ->assertSee('Payment Method')
        ->assertSee('See items')
        ->assertDontSee('View Ref. No.')
        ->assertSee('data-transaction-action-toggle', false)
        ->assertSee('data-reference-number="1234567890123"', false)
        ->assertSee('id="transactionDetailsItemsTitle"', false)
        ->assertSee('id="transactionDetailsItems"', false)
        ->assertSee('Reference No.');
});

test('an invalid stock quantity does not create a sale', function () {
    $product = createSaleProduct(1);

    $this->from(route('sales.create'))->post(route('sales.store'), [
        'payment_method' => 'Cash',
        'items' => [
            ['product_id' => $product->id, 'quantity' => 2],
        ],
    ])->assertRedirect(route('sales.create'))
        ->assertSessionHasErrors('items.0.quantity');

    expect(Transaction::count())->toBe(0);
    expect($product->fresh()->stock)->toBe(1.0);
});

test('new transaction numbers are 12 characters and long existing numbers are compact on the dashboard', function () {
    $product = createSaleProduct();

    $this->post(route('sales.store'), [
        'payment_method' => 'Cash',
        'items' => [
            ['product_id' => $product->id, 'quantity' => 1],
        ],
    ])->assertRedirect(route('sales.create'));

    $invoice = Transaction::firstOrFail()->invoice;
    expect(preg_match('/^[A-Z0-9]{12}$/', $invoice))->toBe(1);

    Transaction::create([
        'invoice' => 'INV-01M6JYCZ8M659H28X2JDH67252',
        'customer_name' => 'Walk-in Customer',
        'payment_method' => 'Cash',
        'total' => 12.50,
        'sold_at' => today(),
    ]);

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSee(substr('INV-01M6JYCZ8M659H28X2JDH67252', -12))
        ->assertSee('Transaction No.')
        ->assertSee('title="INV-01M6JYCZ8M659H28X2JDH67252"', false);
});

test('card is not offered or accepted as a payment method', function () {
    $product = createSaleProduct();

    $this->get(route('sales.create'))
        ->assertOk()
        ->assertDontSee('<option>Card</option>', false);

    $this->from(route('sales.create'))->post(route('sales.store'), [
        'payment_method' => 'Card',
        'items' => [
            ['product_id' => $product->id, 'quantity' => 1],
        ],
    ])->assertRedirect(route('sales.create'))
        ->assertSessionHasErrors('payment_method');

    expect(Transaction::count())->toBe(0)
        ->and($product->fresh()->stock)->toBe(5.0);

    $this->get(route('reports'))
        ->assertOk()
        ->assertDontSee('<option>Card</option>', false);
});

test('category filters are hidden without products and shown when products exist', function () {
    Category::create(['name' => 'Feeds']);
    Category::create(['name' => 'Toy']);

    $this->get(route('products'))
        ->assertOk()
        ->assertSee('id="cats" style="display:none"', false);

    $this->get(route('sales.create'))
        ->assertOk()
        ->assertSee('id="scats" style="display:none"', false);

    createSaleProduct();

    $this->get(route('products'))
        ->assertOk()
        ->assertSee('id="cats" style="display:flex"></div>', false);

    $this->get(route('sales.create'))
        ->assertOk()
        ->assertSee('id="scats" style="display:flex"></div>', false)
        ->assertSee('const categories = [...new Set(products.map(product => product.category))]', false)
        ->assertSee('makeTabs(box, scat, c => { scat = c; renderSTabs(); renderProducts(); }, categories)', false);
});

test('products catalog shows read-only product cards with category and search filters', function () {
    createSaleProduct();

    $this->get(route('catalog'))
        ->assertOk()
        ->assertSee('data-page="products"', false)
        ->assertSee('id="catalogCategories"', false)
        ->assertSee('id="catalogSearch"', false)
        ->assertSee('data-catalog-product', false)
        ->assertSee('>Inventory</span>', false)
        ->assertSee('>Products</span>', false)
        ->assertSee('Dog Food')
        ->assertSee('Pet Food')
        ->assertSee('₱12.50 /pcs')
        ->assertSee('5 pcs in stock')
        ->assertDontSee('data-product-action-toggle', false)
        ->assertDontSee('+ Add Product');
});

test('sales report exposes responsive filters and reports matching category and payment data', function () {
    $dogFood = createSaleProduct();
    $catCategory = Category::create(['name' => 'Cat Supplies']);
    $catFood = Product::create([
        'category_id' => $catCategory->id,
        'name' => 'Cat Food',
        'price' => 30,
        'stock' => 5,
    ]);

    $this->post(route('sales.store'), [
        'customer_name' => 'Cash Customer',
        'payment_method' => 'Cash',
        'items' => [['product_id' => $dogFood->id, 'quantity' => 2]],
    ])->assertRedirect(route('sales.create'));
    $this->post(route('sales.store'), [
        'customer_name' => 'GCash Customer',
        'payment_method' => 'GCash',
        'reference_number' => 'GCASH-654321',
        'items' => [['product_id' => $catFood->id, 'quantity' => 1]],
    ])->assertRedirect(route('sales.create'));

    $this->get(route('reports'))
        ->assertOk()
        ->assertSee('id="reportFilters"', false)
        ->assertDontSee('onchange="render()"', false)
        ->assertSee("addEventListener('submit'", false)
        ->assertSee('value="Pet Food"', false)
        ->assertSee('value="Cat Supplies"', false)
        ->assertSee('value="Cash"', false)
        ->assertSee('value="GCash"', false)
        ->assertSee('id="reportSelection"', false)
        ->assertSee('addEventListener(\'reset\'', false)
        ->assertSee('Dog Food', false)
        ->assertSee('Cat Food', false);
});

test('transaction history search date and payment controls filter existing rows', function () {
    $dogFood = createSaleProduct();
    $this->post(route('sales.store'), [
        'customer_name' => 'Cash Customer',
        'payment_method' => 'Cash',
        'items' => [['product_id' => $dogFood->id, 'quantity' => 1]],
    ])->assertRedirect(route('sales.create'));

    $this->get(route('transactions'))
        ->assertOk()
        ->assertSee('id="transaction-search"', false)
        ->assertSee('id="transaction-date"', false)
        ->assertSee('id="transaction-payment"', false)
        ->assertSee('id="clear-transaction-filters"', false)
        ->assertSee('data-search="', false)
        ->assertSee('Cash Customer')
        ->assertSee('Showing 1 of 1 transactions')
        ->assertSee('row.hidden = !match', false);
});

test('admin can add a product that appears in product and sale pages', function () {
    $category = Category::create(['name' => 'Pet Accessories']);

    $this->post(route('products.store'), [
        'name' => 'Travel Carrier',
        'category' => $category->name,
        'price' => '49.95',
        'stock' => '8',
    ])->assertRedirect(route('products'))
        ->assertSessionHas('status', 'Product added successfully.');

    $product = Product::where('name', 'Travel Carrier')->firstOrFail();
    expect($product->price)->toBe(49.95)
        ->and($product->stock)->toBe(8.0)
        ->and($product->weight)->toBeNull();

    $this->get(route('products'))
        ->assertOk()
        ->assertSee('Travel Carrier')
        ->assertSee('Pet Accessories');

    $this->get(route('sales.create'))
        ->assertOk()
        ->assertSee('"name":"Travel Carrier"', false)
        ->assertSee('"category":"Pet Accessories"', false);

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Products')
        ->assertSee('1');
});

test('admin can add a product and category together with optional weight', function () {
    $this->post(route('products.store'), [
        'name' => 'Rabbit Hay',
        'category' => '__new_category__',
        'new_category' => 'Small Pets',
        'price' => '120',
        'stock' => '4',
        'weight' => '2.5',
    ])->assertRedirect(route('products'));

    $product = Product::with('category')->where('name', 'Rabbit Hay')->firstOrFail();
    expect($product->category->name)->toBe('Small Pets')
        ->and($product->weight)->toBe(2.5);

    $this->get(route('products'))
        ->assertOk()
        ->assertSee('Small Pets')
        ->assertSee('2.5 kg');
});

test('adding a product saves its initial price but does not show it as a price change', function () {
    $this->post(route('products.store'), [
        'name' => 'Freshwater Fish Food',
        'category' => '__new_category__',
        'new_category' => 'Aquarium',
        'price' => '87.50',
        'stock' => '6',
    ])->assertRedirect(route('products'));

    $this->assertDatabaseHas('price_records', [
        'product_name' => 'Freshwater Fish Food',
        'category' => 'Aquarium',
        'old_price' => 87.50,
        'new_price' => 87.50,
        'changed_by' => 'Admin',
        'changed_at' => now()->toDateString(),
    ]);

    $this->get(route('price-records'))
        ->assertOk()
        ->assertDontSee('Freshwater Fish Food')
        ->assertDontSee('Initial price')
        ->assertDontSee('87.50');
});

test('price records show changed prices but omit initial and unchanged prices', function () {
    $product = createSaleProduct();

    foreach ([
        ['Initial Only', 10, 10],
        ['Unchanged Price', 12.50, 12.50],
        ['Changed Price', 12.50, 15],
    ] as [$name, $oldPrice, $newPrice]) {
        PriceRecord::create([
            'product_id' => $product->id,
            'product_name' => $name,
            'category' => 'Pet Food',
            'old_price' => $oldPrice,
            'new_price' => $newPrice,
            'changed_by' => 'Admin',
            'changed_at' => now()->toDateString(),
        ]);
    }

    $this->get(route('price-records'))
        ->assertOk()
        ->assertSee('Changed Price')
        ->assertSee('15.00')
        ->assertDontSee('Initial Only')
        ->assertDontSee('Unchanged Price')
        ->assertDontSee('Initial price')
        ->assertDontSee('Today')
        ->assertDontSee('This Week')
        ->assertDontSee('This Month');
});

test('invalid product data is rejected without creating product or category', function () {
    $this->from(route('products'))->post(route('products.store'), [
        'name' => 'Broken Product',
        'category' => '__new_category__',
        'new_category' => 'Never Saved',
        'price' => '-1',
        'stock' => 'not-a-number',
    ])->assertRedirect(route('products'))
        ->assertSessionHasErrors(['price', 'stock']);

    expect(Product::count())->toBe(0);
    expect(Category::where('name', 'Never Saved')->exists())->toBeFalse();
});

test('admin can delete an unused category from the product category picker', function () {
    $category = Category::create(['name' => 'Unused Category']);
    $history = PriceRecord::create([
        'product_id' => null,
        'product_name' => 'Old Unused Product',
        'category' => $category->name,
        'old_price' => 10,
        'new_price' => 12,
        'changed_by' => 'Admin',
        'changed_at' => now()->toDateString(),
    ]);

    $this->get(route('products'))
        ->assertOk()
        ->assertSee('data-delete-category', false)
        ->assertSee('aria-label="Delete category Unused Category"', false);

    $this->delete(route('categories.destroy', $category))
        ->assertRedirect(route('products'))
        ->assertSessionHas('status', 'Category deleted successfully.');

    expect(Category::whereKey($category->id)->exists())->toBeFalse();
    $this->assertDatabaseMissing('price_records', ['id' => $history->id]);
    $this->get(route('price-records'))
        ->assertOk()
        ->assertDontSee('Unused Category')
        ->assertDontSee('Old Unused Product');
});

test('category deletion is blocked while products still use it', function () {
    $product = createSaleProduct();
    $category = $product->category;
    $history = PriceRecord::create([
        'product_id' => $product->id,
        'product_name' => $product->name,
        'category' => $category->name,
        'old_price' => 10,
        'new_price' => 12,
        'changed_by' => 'Admin',
        'changed_at' => now()->toDateString(),
    ]);

    $this->delete(route('categories.destroy', $category))
        ->assertRedirect(route('products'))
        ->assertSessionHas('category_error', 'Cannot delete Pet Food because it still has products. Delete or move those products first.');

    expect(Category::whereKey($category->id)->exists())->toBeTrue()
        ->and(Product::whereKey($product->id)->exists())->toBeTrue()
        ->and(PriceRecord::whereKey($history->id)->exists())->toBeTrue();
});

test('deleted category is hidden from report filters while past sales remain', function () {
    $category = Category::create(['name' => 'Feeds']);
    $product = Product::create([
        'category_id' => $category->id,
        'name' => 'Old Feed Mix',
        'price' => 20,
        'stock' => 2,
    ]);

    $this->post(route('sales.store'), [
        'payment_method' => 'Cash',
        'items' => [
            ['product_id' => $product->id, 'quantity' => 1],
        ],
    ])->assertRedirect(route('sales.create'));

    $transactionItem = TransactionItem::firstOrFail();
    $this->delete(route('products.destroy', $product))
        ->assertRedirect(route('products'));
    $this->delete(route('categories.destroy', $category))
        ->assertRedirect(route('products'));

    $this->get(route('reports'))
        ->assertOk()
        ->assertDontSee('<option value="Feeds">Feeds</option>', false);

    $this->assertDatabaseHas('transaction_items', [
        'id' => $transactionItem->id,
        'product_id' => null,
        'category' => 'Feeds',
    ]);
});

test('admin can edit stock for an individual product', function () {
    $product = createSaleProduct(5);
    PriceRecord::create([
        'product_id' => $product->id,
        'product_name' => $product->name,
        'category' => 'Pet Food',
        'old_price' => 12.50,
        'new_price' => 12.50,
        'changed_by' => 'Admin',
        'changed_at' => now()->toDateString(),
    ]);

    $this->get(route('products'))
        ->assertOk()
        ->assertSee('>Edit</button>', false)
        ->assertSee('<th style="text-align:right">Actions</th>', false)
        ->assertSee('data-product-action-toggle', false)
        ->assertSee('Quantity')
        ->assertSee('name="price"', false)
        ->assertDontSee('Stock action')
        ->assertSee(route('products.stock', $product), false);

    $this->get(route('products'))
        ->assertSee('data-stock="5"', false);

    $this->post(route('products.stock', $product), ['quantity' => 7])
        ->assertRedirect(route('products'))
        ->assertSessionHas('stock_status', 'Stock updated for Dog Food: 7 remaining.');

    expect($product->fresh()->stock)->toBe(7.0);
    $this->post(route('products.stock', $product), ['quantity' => 3])
        ->assertRedirect(route('products'))
        ->assertSessionHas('stock_status', 'Stock updated for Dog Food: 3 remaining.');

    expect($product->fresh()->stock)->toBe(3.0);
    $this->post(route('products.stock', $product), ['quantity' => 0])
        ->assertRedirect(route('products'))
        ->assertSessionHas('stock_status', 'Stock updated for Dog Food: 0 remaining.');

    expect($product->fresh()->stock)->toBe(0.0);
    expect(PriceRecord::count())->toBe(1);
});

test('editing a product price records the change in price records', function () {
    $product = createSaleProduct(5);

    $this->post(route('products.stock', $product), [
        'quantity' => 7,
        'price' => '15.75',
    ])->assertRedirect(route('products'))
        ->assertSessionHas('stock_status', 'Stock updated for Dog Food: 7 remaining. Price updated and recorded in Price records.');

    expect($product->fresh()->stock)->toBe(7.0)
        ->and($product->fresh()->price)->toBe(15.75);

    $this->assertDatabaseHas('price_records', [
        'product_id' => $product->id,
        'product_name' => 'Dog Food',
        'category' => 'Pet Food',
        'old_price' => 12.50,
        'new_price' => 15.75,
        'changed_by' => 'Test Admin',
        'changed_at' => now()->toDateString(),
    ]);

    $this->get(route('price-records'))
        ->assertOk()
        ->assertSee('Dog Food')
        ->assertSee('12.50')
        ->assertSee('15.75')
        ->assertSee('Test Admin');

    $this->post(route('products.stock', $product), [
        'quantity' => 8,
        'price' => '15.75',
    ])->assertRedirect(route('products'));

    expect(PriceRecord::count())->toBe(1);
});

test('stock edits reject negative, invalid, or overflowing quantities', function () {
    $product = createSaleProduct(5);

    $this->from(route('products'))->post(route('products.stock', $product), ['quantity' => 'not-a-number'])
        ->assertRedirect(route('products'))
        ->assertSessionHasErrors('quantity');

    $this->from(route('products'))->post(route('products.stock', $product), ['quantity' => -1])
        ->assertRedirect(route('products'))
        ->assertSessionHasErrors('quantity');

    $this->from(route('products'))->post(route('products.stock', $product), ['quantity' => 4294967296])
        ->assertRedirect(route('products'))
        ->assertSessionHasErrors('quantity');

    $this->from(route('products'))->post(route('products.stock', $product), [
        'quantity' => 5,
        'price' => 'not-a-price',
    ])->assertRedirect(route('products'))
        ->assertSessionHasErrors('price');

    expect($product->fresh()->stock)->toBe(5.0);
    expect($product->fresh()->price)->toBe(12.50);
});

test('backend-backed pages render successfully', function () {
    foreach (['dashboard', 'products', 'catalog', 'sales.create', 'transactions', 'reports', 'price-records'] as $route) {
        $this->get(route($route))
            ->assertOk()
            ->assertSee('data-theme-toggle', false)
            ->assertSee('css/theme.css')
            ->assertSee('js/theme.js');
    }

    $this->get(route('products'))->assertSee('data-page="inventory"', false);
    $this->get(route('catalog'))->assertSee('data-page="products"', false);
    $this->get(route('sales.create'))->assertSee('data-page="record-sale"', false);
});

test('price records page renders records and chart data', function () {
    $product = createSaleProduct();
    $firstVisibleMonth = now()->startOfYear()->format('F Y');
    $currentMonth = now()->format('F Y');

    PriceRecord::create([
        'product_id' => $product->id,
        'product_name' => $product->name,
        'category' => 'Pet Food',
        'old_price' => 10,
        'new_price' => 12.5,
        'changed_by' => 'Admin',
        'changed_at' => now()->toDateString(),
    ]);

    $this->get(route('price-records'))
        ->assertOk()
        ->assertSee('Dog Food')
        ->assertSee('Pet Food')
        ->assertSee('role="listbox"', false)
        ->assertSee('max-height:288px;overflow-y:auto', false)
        ->assertSee('Admin')
        ->assertSee('Average price per category over time')
        ->assertSee('"product":"Dog Food"', false)
        ->assertSee('class="pr-line"', false)
        ->assertSee('prefers-reduced-motion: reduce');

    $this->get(route('price-records', ['month' => 'all']))
        ->assertOk()
        ->assertSee('"start":"'.now()->startOfYear()->toDateString().'"', false)
        ->assertSee('"end":"'.now()->toDateString().'"', false)
        ->assertSee('"allTime":true', false)
        ->assertSee("{month: 'short', timeZone: 'UTC'}", false)
        ->assertSee($firstVisibleMonth)
        ->assertSee($currentMonth);
});

test('price chart animates its line on load and respects reduced motion', function () {
    $product = createSaleProduct();
    PriceRecord::create([
        'product_id' => $product->id,
        'product_name' => $product->name,
        'category' => 'Pet Food',
        'old_price' => 12.50,
        'new_price' => 15,
        'changed_by' => 'Admin',
        'changed_at' => now()->toDateString(),
    ]);

    $this->get(route('price-records'))
        ->assertOk()
        ->assertSee('class="pr-line"', false)
        ->assertSee('line.animate(', false)
        ->assertSee('duration: 1400', false)
        ->assertSee("matchMedia('(prefers-reduced-motion: reduce)')", false);
});

test('price chart follows selected category and product and marks increases and decreases', function () {
    $dogFood = createSaleProduct();
    $catCategory = Category::create(['name' => 'Cat Supplies']);
    $catFood = Product::create([
        'category_id' => $catCategory->id,
        'name' => 'Cat Food',
        'price' => 20,
        'stock' => 5,
    ]);

    foreach ([
        [$dogFood, 'Pet Food', 10, 12, now()->subDays(3)],
        [$dogFood, 'Pet Food', 12, 9, now()->subDays(2)],
        [$catFood, 'Cat Supplies', 18, 20, now()->subDays(1)],
    ] as [$product, $category, $oldPrice, $newPrice, $date]) {
        PriceRecord::create([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'category' => $category,
            'old_price' => $oldPrice,
            'new_price' => $newPrice,
            'changed_by' => 'Admin',
            'changed_at' => $date->toDateString(),
        ]);
    }

    $this->get(route('price-records', ['product' => 'product-'.$dogFood->id]))
        ->assertOk()
        ->assertSee('Dog Food price over time')
        ->assertSee('value="product-'.$dogFood->id.'" selected', false)
        ->assertSee('"product":"Dog Food"', false)
        ->assertDontSee('"product":"Cat Food"', false)
        ->assertSee('Price increase')
        ->assertSee('Price decrease')
        ->assertSee('class="pr-up">▲ Price increase', false)
        ->assertSee('class="pr-down">▼ Price decrease', false)
        ->assertSee('<polygon points="${points}"', false)
        ->assertSee("point.d > 0 ? '#e5675a' : '#3fb14c'", false);

    $this->get(route('price-records', ['category' => 'Cat Supplies']))
        ->assertOk()
        ->assertSee('"product":"Cat Food"', false)
        ->assertDontSee('"product":"Dog Food","subs"', false)
        ->assertSee('Products in Cat Supplies · price over time');
});

test('admin can delete one price record without affecting other records or product price', function () {
    $product = createSaleProduct();
    $deletedRecord = PriceRecord::create([
        'product_id' => $product->id,
        'product_name' => $product->name,
        'category' => 'Pet Food',
        'old_price' => 12.50,
        'new_price' => 15,
        'changed_by' => 'Admin',
        'changed_at' => now()->toDateString(),
    ]);
    $otherRecord = PriceRecord::create([
        'product_id' => $product->id,
        'product_name' => $product->name,
        'category' => 'Pet Food',
        'old_price' => 15,
        'new_price' => 15,
        'changed_by' => 'Admin',
        'changed_at' => now()->toDateString(),
    ]);

    $this->get(route('price-records'))
        ->assertOk()
        ->assertSee(route('price-records.destroy', $deletedRecord), false);

    $this->delete(route('price-records.destroy', $deletedRecord))
        ->assertRedirect(route('price-records'))
        ->assertSessionHas('status', 'Price record deleted.');

    $this->assertDatabaseMissing('price_records', ['id' => $deletedRecord->id]);
    $this->assertDatabaseHas('price_records', ['id' => $otherRecord->id]);
    expect($product->fresh()->price)->toBe(12.50);
});

test('admin can delete one sales transaction without changing stock or other sales', function () {
    $product = createSaleProduct(3);
    $deletedTransaction = Transaction::create([
        'invoice' => 'INV-DELETE-00001',
        'customer_name' => 'Customer One',
        'payment_method' => 'Cash',
        'total' => 25,
        'sold_at' => today(),
    ]);
    $deletedTransaction->items()->create([
        'product_id' => $product->id,
        'name' => $product->name,
        'category' => 'Pet Food',
        'price' => 12.50,
        'qty' => 2,
        'unit' => 'pcs',
    ]);
    $otherTransaction = Transaction::create([
        'invoice' => 'INV-KEEP-00001',
        'customer_name' => 'Customer Two',
        'payment_method' => 'Cash',
        'total' => 12.50,
        'sold_at' => today(),
    ]);

    $this->get(route('transactions'))
        ->assertOk()
        ->assertSee(route('transactions.destroy', $deletedTransaction), false);

    $this->delete(route('transactions.destroy', $deletedTransaction))
        ->assertRedirect(route('transactions'))
        ->assertSessionHas('status', 'Transaction INV-DELETE-00001 deleted. Product stock was not changed.');

    $this->assertDatabaseMissing('transactions', ['id' => $deletedTransaction->id]);
    $this->assertDatabaseMissing('transaction_items', ['transaction_id' => $deletedTransaction->id]);
    $this->assertDatabaseHas('transactions', ['id' => $otherTransaction->id]);
    expect($product->fresh()->stock)->toBe(3.0);
});

test('staff cannot delete price records or sales transactions', function () {
    $staff = User::create([
        'name' => 'Test Staff',
        'email' => 'test-staff@kulaza.local',
        'username' => 'test_staff',
        'role' => 'staff',
        'password' => 'secure-pass-123',
    ]);
    $priceRecord = PriceRecord::create([
        'product_name' => 'Dog Food',
        'category' => 'Pet Food',
        'old_price' => 12.50,
        'new_price' => 15,
        'changed_by' => 'Admin',
        'changed_at' => now()->toDateString(),
    ]);
    $transaction = Transaction::create([
        'invoice' => 'INV-STAFF-00001',
        'customer_name' => 'Customer',
        'payment_method' => 'Cash',
        'total' => 12.50,
        'sold_at' => today(),
    ]);

    auth()->logout();
    $this->actingAs($staff);

    $this->get(route('transactions'))
        ->assertOk()
        ->assertSee(substr('INV-STAFF-00001', -12))
        ->assertDontSee('Delete');
    $this->get(route('price-records'))->assertForbidden();
    $this->delete(route('price-records.destroy', $priceRecord))->assertForbidden();
    $this->delete(route('transactions.destroy', $transaction))->assertForbidden();

    $this->assertDatabaseHas('price_records', ['id' => $priceRecord->id]);
    $this->assertDatabaseHas('transactions', ['id' => $transaction->id]);
});
