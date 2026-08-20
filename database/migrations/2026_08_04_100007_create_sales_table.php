<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->string('sale_number')->unique();  // numéro lisible, ex: V-20260804-0001
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            // Rattachée à la session de caisse ouverte au moment de la vente.
            $table->foreignId('cash_session_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('sold_at')->index();
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->string('payment_method'); // App\Enums\PaymentMethod
            $table->timestamps();

            $table->index('payment_method');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
