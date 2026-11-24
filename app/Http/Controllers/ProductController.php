<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\PriceRecord;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\ValidationException;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::with(['category', 'brand'])->orderBy('name')->get()
            ->map(fn (Product $product) => [
                'id' => $product->id,
                'name' => $product->name,
                'category' => $product->category->name,
                'category_id' => $product->category_id,
                'brand' => $product->brand?->name,
                'brand_id' => $product->brand_id,
                'price' => $product->price,
                'stock' => $product->stock,
                'unit' => $product->unit,
                'image_url' => $product->image_path ? asset($product->image_path) : null,
            ])->all();

        $categoryOptions = Category::orderBy('name')->get(['id', 'name']);
        $brandOptions = Brand::orderBy('name')->get(['id', 'name']);
        $brandOptionsByCategory = DB::table('products')
            ->join('categories', 'categories.id', '=', 'products.category_id')
            ->join('brands', 'brands.id', '=', 'products.brand_id')
            ->select('categories.name as category_name', 'brands.name as brand_name')
            ->distinct()
            ->orderBy('brands.name')
            ->get()
            ->groupBy('category_name')
            ->map(fn ($brands) => $brands->pluck('brand_name')->values())
            ->all();

        return view('products', compact('products', 'categoryOptions', 'brandOptions', 'brandOptionsByCategory'));
    }

    public function catalog()
    {
        $products = Product::with(['category', 'brand'])->orderBy('name')->get()
            ->map(fn (Product $product) => [
                'name' => $product->name,
                'category' => $product->category->name,
                'brand' => $product->brand?->name,
                'price' => $product->price,
                'stock' => $product->stock,
                'unit' => $product->unit,
                'image_url' => $product->image_path ? asset($product->image_path) : null,
            ])->all();

        return view('product-catalog', compact('products'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:255'],
            'new_category' => ['required_if:category,__new_category__', 'nullable', 'string', 'max:255'],
            'brand' => ['nullable', 'string', 'max:255'],
            'new_brand' => ['required_if:brand,__new_brand__', 'nullable', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'stock' => $request->input('unit') === 'kg'
                ? ['required', 'numeric', 'min:0', 'max:99999999.99', 'decimal:0,2']
                : ['required', 'integer', 'min:0'],
            'unit' => ['sometimes', 'in:pcs,kg,sack'],
            'image' => ['nullable', 'image', 'max:5120'],
        ]);

        $product = DB::transaction(function () use ($validated) {
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

            $brand = null;
            if (($validated['brand'] ?? '') === '__new_brand__') {
                $brand = Brand::firstOrCreate(['name' => trim($validated['new_brand'])]);
            } elseif (! empty($validated['brand'])) {
                $brand = Brand::where('name', $validated['brand'])->first();
                if (! $brand) {
                    throw ValidationException::withMessages([
                        'brand' => 'Choose an existing brand or add a new one.',
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
                'brand_id' => $brand?->id,
                'name' => trim($validated['name']),
                'price' => $validated['price'],
                'stock' => $validated['stock'],
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

            return $product;
        });

        if ($request->hasFile('image')) {
            $imageDirectory = public_path('uploads/products');
            File::ensureDirectoryExists($imageDirectory);
            $imageName = $request->file('image')->hashName();
            $request->file('image')->move($imageDirectory, $imageName);
            $product->update(['image_path' => 'uploads/products/'.$imageName]);
        }

        return redirect()->route('products')->with('status', 'Product added successfully.');
    }

    public function addStock(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'price' => ['sometimes', 'required', 'numeric', 'min:0', 'max:99999999.99', 'decimal:0,2'],
            'quantity' => $product->unit === 'kg'
                ? ['required', 'numeric', 'min:0', 'max:99999999.99', 'decimal:0,2']
                : ['required', 'integer', 'min:0', 'max:4294967295'],
            'category_id' => ['sometimes', 'required', 'integer', 'exists:categories,id'],
            'category_name' => ['sometimes', 'required', 'string', 'max:255'],
            'brand_id' => ['sometimes', 'nullable', 'integer', 'exists:brands,id'],
            'brand_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'image' => ['sometimes', 'nullable', 'image', 'max:5120'],
        ]);

        $update = DB::transaction(function () use ($request, $product, $validated) {
            $lockedProduct = Product::whereKey($product->id)->lockForUpdate()->firstOrFail();
            $oldImagePath = $lockedProduct->image_path;
            $newImagePath = $oldImagePath;
            $categoryId = (int) ($validated['category_id'] ?? $lockedProduct->category_id);
            $category = Category::whereKey($categoryId)->lockForUpdate()->firstOrFail();
            $categoryRenamed = false;
            if (array_key_exists('category_name', $validated)) {
                $requestedCategoryName = trim($validated['category_name']);
                if ($requestedCategoryName !== $category->name) {
                    if (Category::where('name', $requestedCategoryName)->where('id', '!=', $category->id)->exists()) {
                        throw ValidationException::withMessages([
                            'category_name' => 'A category with this name already exists.',
                        ]);
                    }

                    $oldCategoryName = $category->name;
                    $category->update(['name' => $requestedCategoryName]);
                    PriceRecord::where('category', $oldCategoryName)->update(['category' => $requestedCategoryName]);
                    $categoryRenamed = true;
                }
            }
            if (array_key_exists('brand_name', $validated)) {
                $brandName = trim((string) $validated['brand_name']);
                $brandId = $brandName === ''
                    ? null
                    : Brand::firstOrCreate(['name' => $brandName])->id;
            } else {
                $brandId = array_key_exists('brand_id', $validated)
                    ? ($validated['brand_id'] ? (int) $validated['brand_id'] : null)
                    : $lockedProduct->brand_id;
            }
            $brandChanged = (int) $lockedProduct->brand_id !== (int) $brandId;
            $productName = trim($validated['name'] ?? $lockedProduct->name);
            $nameChanged = $lockedProduct->name !== $productName;

            if (Product::where('category_id', $categoryId)
                ->where('name', $productName)
                ->where('id', '!=', $lockedProduct->id)
                ->exists()) {
                $errorField = $nameChanged ? 'name' : 'category_id';
                throw ValidationException::withMessages([
                    $errorField => 'A product with this name already exists in the selected category.',
                ]);
            }

            if ($request->hasFile('image')) {
                $imageDirectory = public_path('uploads/products');
                File::ensureDirectoryExists($imageDirectory);
                $imageName = $request->file('image')->hashName();
                $request->file('image')->move($imageDirectory, $imageName);
                $newImagePath = 'uploads/products/'.$imageName;
            }

            $quantity = (float) $validated['quantity'];
            $oldPrice = (float) $lockedProduct->price;
            $newPrice = array_key_exists('price', $validated) ? (float) $validated['price'] : $oldPrice;
            $priceChanged = round($oldPrice, 2) !== round($newPrice, 2);
            $categoryChanged = (int) $lockedProduct->category_id !== $categoryId;

            $lockedProduct->update([
                'category_id' => $categoryId,
                'brand_id' => $brandId,
                'name' => $productName,
                'stock' => $quantity,
                'price' => $newPrice,
                'image_path' => $newImagePath,
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
                'category_renamed' => $categoryRenamed,
                'category_name' => $category->name,
                'brand_changed' => $brandChanged,
                'name_changed' => $nameChanged,
                'product_name' => $productName,
                'image_changed' => $oldImagePath !== $newImagePath,
                'old_image_path' => $oldImagePath,
            ];
        });

        if ($update['image_changed'] && $update['old_image_path']) {
            File::delete(public_path($update['old_image_path']));
        }

        $quantity = $product->unit === 'kg'
            ? rtrim(rtrim(number_format((float) $validated['quantity'], 2, '.', ''), '0'), '.')
            : (string) (int) $validated['quantity'];

        $unitSuffix = $product->unit === 'kg' ? ' kg' : ($product->unit === 'sack' ? ' sacks' : '');

        $message = "Stock updated for {$update['product_name']}: {$quantity}{$unitSuffix} remaining.";
        if ($update['category_changed']) {
            $message .= " Product moved to {$update['category_name']}.";
        }
        if ($update['category_renamed']) {
            $message .= " Category renamed to {$update['category_name']} for all products.";
        }
        if ($update['brand_changed']) {
            $message .= ' Brand updated.';
        }
        if ($update['name_changed']) {
            $message .= ' Product renamed.';
        }
        if ($update['image_changed']) {
            $message .= ' Product image updated.';
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
        if ($product->image_path) {
            File::delete(public_path($product->image_path));
        }

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

    public function destroyBrand(Brand $brand)
    {
        $deleted = DB::transaction(function () use ($brand) {
            $brand = Brand::whereKey($brand->id)->lockForUpdate()->firstOrFail();

            if ($brand->products()->exists()) {
                return false;
            }

            $brand->delete();

            return true;
        });

        if (! $deleted) {
            return redirect()->route('products')->with(
                'brand_error',
                "Cannot delete {$brand->name} because it still has products. Change or remove the brand from those products first."
            );
        }

        return redirect()->route('products')->with('status', 'Brand deleted successfully.');
    }
}
