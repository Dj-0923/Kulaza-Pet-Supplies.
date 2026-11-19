<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('price_records')
            ->whereNull('product_id')
            ->whereNotExists(function ($query) {
                $query->selectRaw('1')
                    ->from('products')
                    ->join('categories', 'categories.id', '=', 'products.category_id')
                    ->whereColumn('products.name', 'price_records.product_name')
                    ->whereColumn('categories.name', 'price_records.category');
            })
            ->delete();
    }

    public function down(): void
    {
    }
};
