<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Registre (ledger) immuable de tous les mouvements de stock.
        // C'est la vérité auditable ; stock_items.quantity n'est qu'un cache rapide.
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_item_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type');   // App\Enums\StockMovementType : in | out | adjustment
            $table->string('reason'); // App\Enums\StockMovementReason : purchase | sale | waste | manual
            $table->decimal('quantity', 12, 3); // toujours positif ; le sens vient de "type"
            // Source polymorphe : d'où vient ce mouvement (une vente, un achat...).
            $table->nullableMorphs('source'); // source_type + source_id
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['stock_item_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
