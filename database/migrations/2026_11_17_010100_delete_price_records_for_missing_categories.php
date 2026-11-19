<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('price_records')
            ->whereNotExists(function ($query) {
                $query->selectRaw('1')
                    ->from('categories')
                    ->whereColumn('categories.name', 'price_records.category');
            })
            ->delete();
    }

    public function down(): void
    {
    }
};
