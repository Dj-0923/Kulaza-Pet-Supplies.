<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SalesController extends Controller
{
    public function create()
    {
        $products = Product::with(['category', 'brand'])->orderBy('name')->get()
            ->map(fn (Product $product) => [
                'id' => $product->id,
                'name' => $product->name,
                'category' => $product->category->name,
                'brand' => $product->brand?->name,
                'price' => $product->price,
                'stock' => $product->stock,
                'unit' => $product->unit,
                'image_url' => $product->image_path ? asset($product->image_path) : null,
            ])->all();

        return view('record-sale', compact('products'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_name' => ['nullable', 'string', 'max:255'],
            'payment_method' => ['required', 'in:Cash,GCash'],
            'reference_number' => ['nullable', 'required_if:payment_method,GCash', 'string', 'max:100'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'distinct', 'exists:products,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01', 'max:99999999.99', 'decimal:0,2'],
        ]);

        // Keep inventory deductions and transaction rows atomic if any item is invalid.
        $transaction = DB::transaction(function () use ($validated) {
            $items = [];
            $total = 0;

            foreach ($validated['items'] as $index => $item) {
                $product = Product::with('category')->lockForUpdate()->find($item['product_id']);

                if (! $product) {
                    throw ValidationException::withMessages([
                        "items.{$index}.product_id" => 'The selected product is no longer available.',
                    ]);
                }

                $quantity = (float) $item['quantity'];
                if ($product->unit !== 'kg' && floor($quantity) !== $quantity) {
                    throw ValidationException::withMessages([
                        "items.{$index}.quantity" => "Quantity for {$product->name} must be a whole number of {$product->unit}.",
                    ]);
                }

                if ($product->stock < $quantity) {
                    throw ValidationException::withMessages([
                        "items.{$index}.quantity" => "Only {$product->stock} {$product->unit} in stock for {$product->name}.",
                    ]);
                }

                $product->decrement('stock', $quantity);
                // Store sale-time item details so future catalog edits do not alter old receipts.
                $items[] = [
                    'product_id' => $product->id,
                    'name' => $product->name,
                    'category' => $product->category->name,
                    'price' => $product->price,
                    'qty' => $quantity,
                    'unit' => $product->unit,
                ];
                $total += $product->price * $quantity;
            }

            $transaction = Transaction::create([
                'invoice' => Str::upper(substr((string) Str::ulid(), -12)),
                'customer_name' => ($validated['customer_name'] ?? null) ?: 'Walk-in Customer',
                'payment_method' => $validated['payment_method'],
                'reference_number' => $validated['payment_method'] === 'GCash' ? $validated['reference_number'] : null,
                'total' => $total,
                'sold_at' => now()->toDateString(),
            ]);
            $transaction->items()->createMany($items);

            return $transaction;
        });

        return redirect()->route('sales.create')
            ->with('transaction_status', "Transaction saved successfully. Transaction No.: {$transaction->invoice}");
    }

    public function transactions()
    {
        $transactions = Transaction::with('items')->orderByDesc('sold_at')->orderByDesc('id')->get()
            ->map(fn (Transaction $transaction) => [
                'id' => $transaction->id,
                'invoice' => $transaction->invoice,
                'transaction_no' => substr($transaction->invoice, -12),
                'customer' => $transaction->customer_name,
                'items' => $transaction->items->map(fn (TransactionItem $item) => $item->name.' x'.$this->formatQuantity($item->qty).' '.$item->unit)->implode(', '),
                'item_details' => $transaction->items->map(fn (TransactionItem $item) => [
                    'name' => $item->name,
                    'quantity' => $this->formatQuantity($item->qty),
                    'unit' => $item->unit,
                    'subtotal' => $item->price * $item->qty,
                ])->all(),
                'payment' => $transaction->payment_method,
                'reference_number' => $transaction->reference_number,
                'total' => $transaction->total,
                'date' => $transaction->sold_at->format('Y-m-d'),
            ])->all();

        return view('transactions', compact('transactions'));
    }

    public function destroyTransaction(Transaction $transaction)
    {
        $invoice = $transaction->invoice;
        $transaction->delete();

        return redirect()->route('transactions')
            ->with('status', "Transaction {$invoice} deleted. Product stock was not changed.");
    }

    private function formatQuantity(float $quantity): string
    {
        return rtrim(rtrim(number_format($quantity, 2, '.', ''), '0'), '.');
    }
}
