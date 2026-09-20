<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_items', function (Blueprint $table) {
            // Mode d'achat habituel : « à crédit » pour un fournisseur à compte.
            $table->string('default_payment_method')->nullable()->after('supplier_id');
            // Abonnement : livraison identique chaque jour (ex : 2 kg à 880 MRU).
            $table->decimal('daily_quantity', 12, 3)->nullable()->after('default_payment_method');
            $table->decimal('daily_price', 12, 2)->nullable()->after('daily_quantity');
        });
    }

    public function down(): void
    {
        Schema::table('stock_items', function (Blueprint $table) {
            $table->dropColumn(['default_payment_method', 'daily_quantity', 'daily_price']);
        });
    }
};
