<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Relevé de l'argent réellement disponible, saisi à la main (une fois par semaine).
        // C'est la seule donnée fiable pour la position nette : le site ne connaît pas
        // les soldes Bankily / Masrivi / Sedad.
        Schema::create('balance_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->date('recorded_on')->unique();
            $table->decimal('cash', 12, 2)->default(0);
            $table->decimal('bankily', 12, 2)->default(0);
            $table->decimal('masrivi', 12, 2)->default(0);
            $table->decimal('sedad', 12, 2)->default(0);
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('balance_snapshots');
    }
};
