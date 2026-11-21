<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\PriceRecord;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::with('category')->orderBy('name')->get()
            ->map(fn (Product $product) => [
                'id' => $product->id,
                'name' => $product->name,
                'category' => $product->category->name,
                'category_id' => $product->category_id,
                'price' => $product->price,
                'stock' => $product->stock,
                'weight' => $product->weight,
                'unit' => $product->unit,
            ])->all();

        $categoryOptions = Category::orderBy('name')->get(['id', 'name']);

        return view('products', compact('products', 'categoryOptions'));
    }

    public function catalog()
    {
        $products = Product::with('category')->orderBy('name')->get()
            ->map(fn (Product $product) => [
                'name' => $product->name,
                'category' => $product->category->name,
                'price' => $product->price,
                'stock' => $product->stock,
                'weight' => $product->weight,
                'unit' => $product->unit,
            ])->all();

        return view('product-catalog', compact('products'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:255'],
            'new_category' => ['required_if:category,__new_category__', 'nullable', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'stock' => $request->input('unit') === 'kg'
                ? ['required', 'numeric', 'min:0', 'max:99999999.99', 'decimal:0,2']
                : ['required', 'integer', 'min:0'],
            'weight' => ['nullable', 'numeric', 'gt:0'],
            'unit' => ['sometimes', 'in:pcs,kg'],
        ]);

        DB::transaction(function () use ($validated) {
            if ($validated['category'] === '__new_category__') {
                $category = Category::firstOrCreate([
                    'name' => trim($validated['new_category']),
                ]);
            } else {
                $category = Category::where('name', $validated['category'])->first();

                if (! $category) {
                    throw ValidationException::withMessages([
                        'category' => 'Choose an existing category or add a new one.',
                    ]);
                }
            }

            if (Product::where('category_id', $category->id)->where('name', $validated['name'])->exists()) {
                throw ValidationException::withMessages([
                    'name' => 'This product already exists in the selected category.',
                ]);
            }

            $product = Product::create([
                'category_id' => $category->id,
                'name' => trim($validated['name']),
                'price' => $validated['price'],
                'stock' => $validated['stock'],
                'weight' => $validated['weight'] ?? null,
                'unit' => $validated['unit'] ?? 'pcs',
            ]);

            PriceRecord::create([
                'product_id' => $product->id,
                'product_name' => $product->name,
                'category' => $category->name,
                'old_price' => $product->price,
                'new_price' => $product->price,
                'changed_by' => 'Admin',
                'changed_at' => now()->toDateString(),
            ]);
        });

        return redirect()->route('products')->with('status', 'Product added successfully.');
    }

    public function addStock(Request $request, Product $product)
    {
        $validated = $request->validate([
            'price' => ['sometimes', 'required', 'numeric', 'min:0', 'max:99999999.99', 'decimal:0,2'],
            'quantity' => $product->unit === 'kg'
                ? ['required', 'numeric', 'min:0', 'max:99999999.99', 'decimal:0,2']
                : ['required', 'integer', 'min:0', 'max:4294967295'],
            'category_id' => ['sometimes', 'required', 'integer', 'exists:categories,id'],
        ]);

        $update = DB::transaction(function () use ($request, $product, $validated) {
            $lockedProduct = Product::whereKey($product->id)->lockForUpdate()->firstOrFail();
            $categoryId = (int) ($validated['category_id'] ?? $lockedProduct->category_id);
            $category = Category::findOrFail($categoryId);

            if (Product::where('category_id', $categoryId)
                ->where('name', $lockedProduct->name)
                ->where('id', '!=', $lockedProduct->id)
                ->exists()) {
                throw ValidationException::withMessages([
                    'category_id' => 'A product with this name already exists in the selected category.',
                ]);
            }

            $quantity = (float) $validated['quantity'];
            $oldPrice = (float) $lockedProduct->price;
            $newPrice = array_key_exists('price', $validated) ? (float) $validated['price'] : $oldPrice;
            $priceChanged = round($oldPrice, 2) !== round($newPrice, 2);
            $categoryChanged = (int) $lockedProduct->category_id !== $categoryId;

            $lockedProduct->update([
                'category_id' => $categoryId,
                'stock' => $quantity,
                'price' => $newPrice,
            ]);

            if ($priceChanged) {
                PriceRecord::create([
                    'product_id' => $lockedProduct->id,
                    'product_name' => $lockedProduct->name,
                    'category' => $category->name,
                    'old_price' => $oldPrice,
                    'new_price' => $newPrice,
                    'changed_by' => $request->user()->name,
                    'changed_at' => now()->toDateString(),
                ]);
            }

            return [
                'price_changed' => $priceChanged,
                'category_changed' => $categoryChanged,
                'category_name' => $category->name,
            ];
        });

        $quantity = $product->unit === 'kg'
            ? rtrim(rtrim(number_format((float) $validated['quantity'], 2, '.', ''), '0'), '.')
            : (string) (int) $validated['quantity'];

        $unitSuffix = $product->unit === 'kg' ? ' kg' : '';

        $message = "Stock updated for {$product->name}: {$quantity}{$unitSuffix} remaining.";
        if ($update['category_changed']) {
            $message .= " Product moved to {$update['category_name']}.";
        }
        if ($update['price_changed']) {
            $message .= ' Price updated and recorded in Price records.';
        }

        return redirect()->route('products')->with('stock_status', $message);
    }

    public function destroy(Product $product)
    {
        DB::transaction(function () use ($product) {
            PriceRecord::where('product_id', $product->id)->delete();
            $product->delete();
        });

        return redirect()->route('products')->with('status', 'Product deleted successfully.');
    }

    public function destroyCategory(Category $category)
    {
        $deleted = DB::transaction(function () use ($category) {
            $category = Category::whereKey($category->id)->lockForUpdate()->firstOrFail();

            if ($category->products()->exists()) {
                return false;
            }

            PriceRecord::where('category', $category->name)->delete();
            $category->delete();

            return true;
        });

        if (! $deleted) {
            return redirect()->route('products')->with(
                'category_error',
                "Cannot delete {$category->name} because it still has products. Delete or move those products first."
            );
        }

        return redirect()->route('products')->with('status', 'Category deleted successfully.');
    }
}
