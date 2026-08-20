<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Nomenclature / recette : relie un produit vendable aux articles de stock
        // qu'il consomme. C'est ce qui permet le décrément automatique du stock à la vente.
        Schema::create('recipe_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('stock_item_id')->constrained()->restrictOnDelete();
            $table->decimal('quantity_needed', 12, 3); // quantité consommée par 1 unité de produit
            $table->timestamps();

            // Un même ingrédient ne peut apparaître qu'une fois par produit.
            $table->unique(['product_id', 'stock_item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recipe_items');
    }
};
