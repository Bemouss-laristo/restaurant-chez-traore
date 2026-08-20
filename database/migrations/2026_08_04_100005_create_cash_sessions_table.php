<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Session de caisse : le caissier ouvre avec un fond, encaisse, puis clôture
        // en comptant l'argent réel. L'écart = counted_cash - expected_cash.
        Schema::create('cash_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->timestamp('opened_at');
            $table->timestamp('closed_at')->nullable();
            $table->decimal('opening_float', 12, 2)->default(0);  // fond de caisse
            $table->decimal('expected_cash', 12, 2)->nullable();  // caisse théorique (calculée à la clôture)
            $table->decimal('counted_cash', 12, 2)->nullable();   // caisse réelle (comptée)
            $table->decimal('difference', 12, 2)->nullable();     // écart = réel - théorique
            $table->string('status')->default('open')->index();   // open | closed
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_sessions');
    }
};
