<?php

use App\Models\Category;
use App\Models\Brand;
use App\Models\PriceRecord;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;

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

test('inventory search data includes category, product name, and brand', function () {
    $category = Category::create(['name' => 'Grains']);
    $brand = Brand::create(['name' => 'Sunmade']);
    Product::create([
        'category_id' => $category->id,
        'brand_id' => $brand->id,
        'name' => 'Brown Rice',
        'price' => 30,
        'stock' => 89,
    ]);

    $this->get(route('products'))
        ->assertOk()
        ->assertSee('id="search"', false)
        ->assertSee('data-search="Grains Brown Rice Sunmade"', false);
});

test('new category can be entered in the category picker field', function () {
    $this->get(route('products'))
        ->assertOk()
        ->assertSeeInOrder([
            'id="categoryPicker"',
            'id="categoryPickerButton"',
            'id="newCategory" name="new_category"',
            'id="categoryPickerMenu"',
        ], false)
        ->assertSee('id="chooseExistingCategory"', false)
        ->assertDontSee('id="newCategoryWrap"', false)
        ->assertDontSee('for="newCategory">New category', false);
});

test('products can be added and stocked in kilograms', function () {
    $category = Category::create(['name' => 'Pet Food']);
    $brand = Brand::create(['name' => 'Bulk Brand']);

    $this->post(route('products.store'), [
        'name' => 'Bulk Dog Food',
        'category' => $category->name,
        'brand' => $brand->name,
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

test('products sold by sack use whole-number stock and sale quantities', function () {
    $category = Category::create(['name' => 'Pet Food']);
    $brand = Brand::create(['name' => 'Sack Brand']);

    $this->post(route('products.store'), [
        'name' => 'Dog Food Sack',
        'category' => $category->name,
        'brand' => $brand->name,
        'unit' => 'sack',
        'price' => 1250,
        'stock' => 4,
    ])->assertRedirect(route('products'))
        ->assertSessionHasNoErrors();

    $product = Product::where('name', 'Dog Food Sack')->firstOrFail();
    expect($product->unit)->toBe('sack')
        ->and($product->brand_id)->toBe($brand->id);

    $this->post(route('sales.store'), [
        'payment_method' => 'Cash',
        'items' => [
            ['product_id' => $product->id, 'quantity' => '2'],
        ],
    ])->assertRedirect(route('sales.create'))
        ->assertSessionHasNoErrors();

    expect($product->fresh()->stock)->toBe(2.0)
        ->and(TransactionItem::firstOrFail()->unit)->toBe('sack');

    $this->from(route('sales.create'))->post(route('sales.store'), [
        'payment_method' => 'Cash',
        'items' => [
            ['product_id' => $product->id, 'quantity' => '0.5'],
        ],
    ])->assertRedirect(route('sales.create'))
        ->assertSessionHasErrors('items.0.quantity');
});

test('new brands and product images are saved and shown in inventory', function () {
    $category = Category::create(['name' => 'Pet Food']);
    $image = UploadedFile::fake()->image('dog-food.jpg');

    $this->get(route('products'))
        ->assertOk()
        ->assertSee('id="newBrand" name="new_brand"', false)
        ->assertSee('id="chooseNewBrand"', false)
        ->assertDontSee('for="newBrand">New brand', false);

    $this->post(route('products.store'), [
        'name' => 'Premium Dog Food',
        'category' => $category->name,
        'brand' => '__new_brand__',
        'new_brand' => 'Happy Paws',
        'unit' => 'pcs',
        'price' => 80,
        'stock' => 10,
        'image' => $image,
    ])->assertRedirect(route('products'))
        ->assertSessionHasNoErrors();

    $product = Product::where('name', 'Premium Dog Food')->firstOrFail();
    $this->assertDatabaseHas('brands', ['id' => $product->brand_id, 'name' => 'Happy Paws']);
    expect($product->image_path)->not->toBeNull();

    try {
        $this->get(route('products'))
            ->assertOk()
            ->assertSee('Happy Paws')
            ->assertSee(asset($product->image_path));
        $this->get(route('catalog'))
            ->assertOk()
            ->assertSee('Happy Paws')
            ->assertSee(asset($product->image_path));
        $this->get(route('sales.create'))
            ->assertOk()
            ->assertSee('Happy Paws')
            ->assertSee(basename($product->image_path));

        $oldImagePath = $product->image_path;
        $this->post(route('products.stock', $product), [
            'category_id' => $category->id,
            'brand_id' => $product->brand_id,
            'price' => 80,
            'quantity' => 10,
            'image' => UploadedFile::fake()->image('replacement.jpg'),
        ])->assertRedirect(route('products'))
            ->assertSessionHas('stock_status', "Stock updated for Premium Dog Food: 10 remaining. Product image updated.");

        $product->refresh();
        expect($product->image_path)->not->toBe($oldImagePath)
            ->and(File::exists(public_path($product->image_path)))->toBeTrue()
            ->and(File::exists(public_path($oldImagePath)))->toBeFalse();
    } finally {
        File::delete(public_path($product->fresh()->image_path));
    }
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
        ->assertSee('name="category_name"', false)
        ->assertSee('name="category_id"', false)
        ->assertDontSee('stockCategoryMenu', false)
        ->assertDontSee('stockCategoryOptions', false)
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

test('admin can rename a category by typing a new name while editing a product', function () {
    $category = Category::create(['name' => 'Pet Food']);
    $firstProduct = Product::create([
        'category_id' => $category->id,
        'name' => 'Cat Food',
        'price' => 100,
        'stock' => 10,
    ]);
    $secondProduct = Product::create([
        'category_id' => $category->id,
        'name' => 'Dog Food',
        'price' => 120,
        'stock' => 5,
    ]);
    $priceRecord = PriceRecord::create([
        'product_id' => $firstProduct->id,
        'product_name' => $firstProduct->name,
        'category' => $category->name,
        'old_price' => 90,
        'new_price' => 100,
        'changed_by' => 'Admin',
        'changed_at' => today(),
    ]);

    $this->get(route('products'))
        ->assertOk()
        ->assertSee('id="stockCategory" name="category_name" type="text"', false)
        ->assertDontSee('stockCategoryOptions', false);

    $this->post(route('products.stock', $firstProduct), [
        'category_id' => $category->id,
        'category_name' => 'Pet Supplies',
        'quantity' => 10,
    ])->assertRedirect(route('products'))
        ->assertSessionHas('stock_status', 'Stock updated for Cat Food: 10 remaining. Category renamed to Pet Supplies for all products.');

    expect($category->fresh()->name)->toBe('Pet Supplies')
        ->and($firstProduct->fresh()->category_id)->toBe($category->id)
        ->and($secondProduct->fresh()->category_id)->toBe($category->id)
        ->and($priceRecord->fresh()->category)->toBe('Pet Supplies');
});

test('category rename rejects names already used by another category', function () {
    $sourceCategory = Category::create(['name' => 'Pet Food']);
    $targetCategory = Category::create(['name' => 'Cat Supplies']);
    $product = Product::create([
        'category_id' => $sourceCategory->id,
        'name' => 'Cat Food',
        'price' => 100,
        'stock' => 10,
    ]);

    $this->from(route('products'))->post(route('products.stock', $product), [
        'category_id' => $sourceCategory->id,
        'category_name' => $targetCategory->name,
        'quantity' => 10,
    ])->assertRedirect(route('products'))
        ->assertSessionHasErrors('category_name');

    expect($product->fresh()->category_id)->toBe($sourceCategory->id)
        ->and($sourceCategory->fresh()->name)->toBe('Pet Food');
});

test('admin can rename a product while editing it', function () {
    $category = Category::create(['name' => 'Pet Food']);
    $product = Product::create([
        'category_id' => $category->id,
        'name' => 'Old Cat Food',
        'price' => 100,
        'stock' => 10,
    ]);

    $this->get(route('products'))
        ->assertOk()
        ->assertSee('id="stockProductName" name="name"', false);

    $this->post(route('products.stock', $product), [
        'name' => 'New Cat Food',
        'price' => 100,
        'quantity' => 10,
    ])->assertRedirect(route('products'))
        ->assertSessionHas('stock_status', 'Stock updated for New Cat Food: 10 remaining. Product renamed.');

    expect($product->fresh()->name)->toBe('New Cat Food');
});

test('admin can change a product brand while editing it', function () {
    $category = Category::create(['name' => 'Pet Food']);
    $oldBrand = Brand::create(['name' => 'Old Brand']);
    $product = Product::create([
        'category_id' => $category->id,
        'brand_id' => $oldBrand->id,
        'name' => 'Cat Food',
        'price' => 100,
        'stock' => 10,
    ]);

    $this->get(route('products'))
        ->assertOk()
        ->assertSee('id="stockBrand" name="brand_name"', false)
        ->assertSee('data-brand-name="'.$oldBrand->name.'"', false)
        ->assertSee('id="stockBrandMenu"', false)
        ->assertSee('id="stockBrandToggle"', false)
        ->assertSee('id="stockCategory" name="category_name" type="text"', false)
        ->assertDontSee('<datalist', false);

    $this->post(route('products.stock', $product), [
        'brand_name' => 'New Brand',
        'price' => 100,
        'quantity' => 10,
    ])->assertRedirect(route('products'))
        ->assertSessionHas('stock_status', 'Stock updated for Cat Food: 10 remaining. Brand updated.');

    $newBrand = Brand::where('name', 'New Brand')->firstOrFail();
    expect($product->fresh()->brand_id)->toBe($newBrand->id);
});

test('brand suggestions are grouped by product category', function () {
    $dogFood = Category::create(['name' => 'Dog Food']);
    $grains = Category::create(['name' => 'Grains']);
    $dogFoodBrand = Brand::create(['name' => 'Dog Food Brand']);
    $grainsBrand = Brand::create(['name' => 'Grains Brand']);
    Product::create([
        'category_id' => $dogFood->id,
        'brand_id' => $dogFoodBrand->id,
        'name' => 'Dog Kibble',
        'price' => 100,
        'stock' => 10,
    ]);
    Product::create([
        'category_id' => $grains->id,
        'brand_id' => $grainsBrand->id,
        'name' => 'Brown Rice',
        'price' => 50,
        'stock' => 10,
    ]);

    $this->get(route('products'))
        ->assertOk()
        ->assertSee('"Dog Food":["Dog Food Brand"]', false)
        ->assertSee('"Grains":["Grains Brand"]', false);
});

test('product renames are rejected when the name already exists in its category', function () {
    $category = Category::create(['name' => 'Pet Food']);
    $product = Product::create([
        'category_id' => $category->id,
        'name' => 'Old Cat Food',
        'price' => 100,
        'stock' => 10,
    ]);
    Product::create([
        'category_id' => $category->id,
        'name' => 'Existing Cat Food',
        'price' => 90,
        'stock' => 5,
    ]);

    $this->from(route('products'))->post(route('products.stock', $product), [
        'name' => 'Existing Cat Food',
        'price' => 100,
        'quantity' => 10,
    ])->assertRedirect(route('products'))
        ->assertSessionHasErrors('name');

    expect($product->fresh()->name)->toBe('Old Cat Food');
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
