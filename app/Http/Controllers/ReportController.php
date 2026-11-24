<?php

namespace App\Http\Controllers;

use App\Models\PriceRecord;
use App\Models\Transaction;
use App\Models\TransactionItem;

class ReportController extends Controller
{
    public function sales()
    {
        return view('sales-reports', ['sales' => $this->saleRows()]);
    }

    public function priceRecords()
    {
        $priceHistory = PriceRecord::orderByDesc('changed_at')->orderByDesc('id')->get()
            ->map(fn (PriceRecord $record) => [
                'id' => $record->id,
                'product_id' => $record->product_id,
                'date' => $record->changed_at->format('Y-m-d'),
                'product' => $record->product_name,
                'category' => $record->category,
                'old_price' => $record->old_price,
                'new_price' => $record->new_price,
                'changed_by' => $record->changed_by,
            ])->all();
        $records = array_values(array_filter($priceHistory, fn (array $record) =>
            $record['old_price'] !== $record['new_price']
        ));

        return view('price-records', compact('records', 'priceHistory'));
    }

    public function destroyPriceRecord(PriceRecord $priceRecord)
    {
        $priceRecord->delete();

        return redirect()->route('price-records')->with('status', 'Price record deleted.');
    }

    private function saleRows(): array
    {
        return Transaction::with('items')->orderByDesc('sold_at')->orderByDesc('id')->get()
            ->map(fn (Transaction $transaction) => [
                'date' => $transaction->sold_at->format('Y-m-d'),
                'payment' => $transaction->payment_method,
                'items' => $transaction->items->map(fn (TransactionItem $item) => [
                    'name' => $item->name,
                    'category' => $item->category,
                    'price' => $item->price,
                    'qty' => $item->qty,
                    'unit' => $item->unit,
                ])->all(),
            ])->all();
    }
}
