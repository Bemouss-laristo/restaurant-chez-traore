<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Fournisseurs à compte (boulanger, viande hachée…) : on prend la marchandise
        // tout le mois et on paie la facture à la fin.
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('phone')->nullable();
            $table->text('note')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('supplier_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('cash_session_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('payment_method');
            $table->timestamp('paid_at')->index();
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_payments');
        Schema::dropIfExists('suppliers');
    }
};
