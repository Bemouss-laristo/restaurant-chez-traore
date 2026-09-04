<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique(); // ex: C-20260902-0001
            $table->string('customer_name');
            $table->string('customer_phone');
            $table->text('note')->nullable();
            $table->string('status')->default('nouvelle')->index(); // App\Enums\OrderStatus
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            // Vente créée au moment de l'encaissement (nullable tant que non encaissée).
            $table->foreignId('sale_id')->nullable()->constrained()->nullOnDelete();
            // Membre du personnel qui a traité la commande.
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
