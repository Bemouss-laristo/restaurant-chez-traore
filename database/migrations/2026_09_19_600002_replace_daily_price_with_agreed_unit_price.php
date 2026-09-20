<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Le prix convenu avec le fournisseur porte sur l'UNITÉ (40 MRU le paquet de pain,
     * 220 MRU le kilo de viande), pas sur une livraison entière. C'est ce qui permet de
     * ne saisir chaque jour QUE la quantité prise : le montant se calcule tout seul.
     */
    public function up(): void
    {
        Schema::table('stock_items', function (Blueprint $table) {
            $table->decimal('agreed_unit_price', 12, 2)->nullable()->after('daily_quantity');
        });

        if (Schema::hasColumn('stock_items', 'daily_price')) {
            Schema::table('stock_items', function (Blueprint $table) {
                $table->dropColumn('daily_price');
            });
        }
    }

    public function down(): void
    {
        Schema::table('stock_items', function (Blueprint $table) {
            $table->decimal('daily_price', 12, 2)->nullable()->after('daily_quantity');
            $table->dropColumn('agreed_unit_price');
        });
    }
};
