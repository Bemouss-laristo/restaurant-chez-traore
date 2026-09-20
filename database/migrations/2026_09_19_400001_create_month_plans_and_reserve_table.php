<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Enveloppe du mois : l'argent de travail mis en route au début du mois.
        Schema::create('month_plans', function (Blueprint $table) {
            $table->id();
            $table->string('month', 7)->unique();   // AAAA-MM
            $table->decimal('opening_amount', 12, 2)->default(0);
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->text('note')->nullable();
            $table->timestamps();
        });

        // Mises de côté : de l'argent qui sort de l'exploitation pour être gardé
        // en réserve. Ce n'est PAS une dépense : la richesse ne disparaît pas.
        Schema::create('reserve_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('cash_session_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('amount', 12, 2);       // positif = mis de côté, négatif = repris
            $table->string('payment_method');
            $table->date('moved_on')->index();
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reserve_movements');
        Schema::dropIfExists('month_plans');
    }
};
