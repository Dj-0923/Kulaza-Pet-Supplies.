<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('unit')->default('pcs');
            $table->decimal('stock', 10, 2)->default(0)->change();
        });

        Schema::table('transaction_items', function (Blueprint $table) {
            $table->string('unit')->default('pcs');
            $table->decimal('qty', 10, 2)->change();
        });
    }

    public function down(): void
    {
        Schema::table('transaction_items', function (Blueprint $table) {
            $table->dropColumn('unit');
            $table->unsignedInteger('qty')->change();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('unit');
            $table->unsignedInteger('stock')->default(0)->change();
        });
    }
};
