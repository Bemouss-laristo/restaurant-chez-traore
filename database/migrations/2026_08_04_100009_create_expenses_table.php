<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            // Rattachée à la session de caisse si payée en espèces depuis le tiroir.
            $table->foreignId('cash_session_id')->nullable()->constrained()->nullOnDelete();
            $table->string('expense_category'); // App\Enums\ExpenseCategory
            $table->decimal('amount', 12, 2);
            $table->text('description')->nullable();
            $table->timestamp('spent_at')->index();
            // Le mode de paiement détermine si la dépense touche le tiroir-caisse.
            $table->string('payment_method')->default('especes'); // App\Enums\PaymentMethod
            $table->timestamps();

            $table->index('expense_category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
