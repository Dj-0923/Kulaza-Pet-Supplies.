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

test('products can be added and stocked in kilograms', function () {
    $category = Category::create(['name' => 'Pet Food']);

    $this->post(route('products.store'), [
        'name' => 'Bulk Dog Food',
        'category' => $category->name,
        'unit' => 'kg',
        'price' => 85.50,
        'stock' => '12.75',
    ])->assertRedirect(route('products'))
        ->assertSessionHasNoErrors();

    $product = Product::where('name', 'Bulk Dog Food')->firstOrFail();
    expect($product->unit)->toBe('kg')
        ->and($product->stock)->toBe(12.75);

    $this->post(route('products.stock', $product), ['quantity' => '8.25'])
        ->assertRedirect(route('products'))
        ->assertSessionHasNoErrors();

    expect($product->fresh()->stock)->toBe(8.25);
});

test('admin can move a product to another category while editing it', function () {
    $sourceCategory = Category::create(['name' => 'Pet Food']);
    $targetCategory = Category::create(['name' => 'Cat Supplies']);
    $product = Product::create([
        'category_id' => $sourceCategory->id,
        'name' => 'Bulk Cat Food',
        'price' => 100,
        'stock' => 10,
        'unit' => 'kg',
    ]);

    $this->get(route('products'))
        ->assertOk()
        ->assertSee('name="category_id"', false)
        ->assertSee('data-category-id="'.$sourceCategory->id.'"', false);

    $this->post(route('products.stock', $product), [
        'category_id' => $targetCategory->id,
        'price' => '105',
        'quantity' => '8.5',
    ])->assertRedirect(route('products'))
        ->assertSessionHas('stock_status', 'Stock updated for Bulk Cat Food: 8.5 kg remaining. Product moved to Cat Supplies. Price updated and recorded in Price records.');

    expect($product->fresh()->category_id)->toBe($targetCategory->id)
        ->and($product->fresh()->stock)->toBe(8.5);

    $this->assertDatabaseHas('price_records', [
        'product_id' => $product->id,
        'category' => 'Cat Supplies',
        'old_price' => 100,
        'new_price' => 105,
    ]);
});

test('moving a product is rejected if its name already exists in the destination category', function () {
    $sourceCategory = Category::create(['name' => 'Pet Food']);
    $targetCategory = Category::create(['name' => 'Cat Supplies']);
    $product = Product::create([
        'category_id' => $sourceCategory->id,
        'name' => 'Cat Food',
        'price' => 100,
        'stock' => 10,
    ]);
    Product::create([
        'category_id' => $targetCategory->id,
        'name' => 'Cat Food',
        'price' => 90,
        'stock' => 5,
    ]);

    $this->from(route('products'))->post(route('products.stock', $product), [
        'category_id' => $targetCategory->id,
        'price' => 100,
        'quantity' => 10,
    ])->assertRedirect(route('products'))
        ->assertSessionHasErrors('category_id');

    expect($product->fresh()->category_id)->toBe($sourceCategory->id);
});

test('deleting a product removes its price history and preserves its sales history', function () {
    $category = Category::create(['name' => 'Pet Food']);
    $product = Product::create([
        'category_id' => $category->id,
        'name' => 'Bulk Dog Food',
        'price' => 85.50,
        'stock' => 12,
        'unit' => 'kg',
    ]);
    $transaction = Transaction::create([
        'invoice' => 'INV-DELETE-00001',
        'payment_method' => 'Cash',
        'total' => 85.50,
        'sold_at' => today(),
    ]);
    $transactionItem = TransactionItem::create([
        'transaction_id' => $transaction->id,
        'product_id' => $product->id,
        'name' => $product->name,
        'category' => $category->name,
        'price' => $product->price,
        'qty' => 1,
        'unit' => 'kg',
    ]);
    $priceRecord = PriceRecord::create([
        'product_id' => $product->id,
        'product_name' => $product->name,
        'category' => $category->name,
        'old_price' => $product->price,
        'new_price' => $product->price,
        'changed_by' => 'Admin',
        'changed_at' => today(),
    ]);

    $this->delete(route('products.destroy', $product))
        ->assertRedirect(route('products'))
        ->assertSessionHas('status', 'Product deleted successfully.');

    $this->assertDatabaseMissing('products', ['id' => $product->id]);
    $this->assertDatabaseHas('transaction_items', [
        'id' => $transactionItem->id,
        'product_id' => null,
        'name' => 'Bulk Dog Food',
    ]);
    $this->assertDatabaseMissing('price_records', ['id' => $priceRecord->id]);

    $this->get(route('products'))->assertDontSee('Bulk Dog Food');
    $this->get(route('price-records'))->assertDontSee('Bulk Dog Food');
});

test('sales accept fractional kilograms and deduct the correct stock', function () {
    $category = Category::create(['name' => 'Pet Food']);
    $product = Product::create([
        'category_id' => $category->id,
        'name' => 'Bulk Cat Food',
        'price' => 100,
        'stock' => 10.5,
        'unit' => 'kg',
    ]);

    $this->post(route('sales.store'), [
        'payment_method' => 'Cash',
        'items' => [
            ['product_id' => $product->id, 'quantity' => '0.75'],
        ],
    ])->assertRedirect(route('sales.create'))
        ->assertSessionHasNoErrors();

    expect($product->fresh()->stock)->toBe(9.75)
        ->and(TransactionItem::firstOrFail()->qty)->toBe(0.75)
        ->and(TransactionItem::firstOrFail()->unit)->toBe('kg');
});

test('piece products reject fractional sales without changing inventory', function () {
    $category = Category::create(['name' => 'Pet Accessories']);
    $product = Product::create([
        'category_id' => $category->id,
        'name' => 'Food Bowl',
        'price' => 50,
        'stock' => 5,
    ]);

    $this->from(route('sales.create'))->post(route('sales.store'), [
        'payment_method' => 'Cash',
        'items' => [
            ['product_id' => $product->id, 'quantity' => '0.5'],
        ],
    ])->assertRedirect(route('sales.create'))
        ->assertSessionHasErrors('items.0.quantity');

    expect($product->fresh()->stock)->toBe(5.0)
        ->and(TransactionItem::count())->toBe(0);
});
