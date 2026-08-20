<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_category_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->restrictOnDelete(); // on ne supprime pas une catégorie qui a des produits
            $table->string('name');
            $table->decimal('sale_price', 12, 2)->default(0);      // prix de vente (MRU)
            $table->decimal('estimated_cost', 12, 2)->default(0);  // coût estimé (MRU)
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->softDeletes(); // on désactive/archive, on ne casse pas l'historique des ventes
            $table->timestamps();

            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
