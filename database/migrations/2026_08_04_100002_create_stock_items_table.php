<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_items', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('unit'); // App\Enums\Unit
            // Quantités en 3 décimales pour gérer grammes et millilitres.
            $table->decimal('quantity', 12, 3)->default(0);
            $table->decimal('alert_threshold', 12, 3)->default(0);
            $table->decimal('unit_cost', 12, 2)->default(0); // coût d'achat unitaire (MRU)
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_items');
    }
};
