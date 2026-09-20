<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            // Coût unitaire réel du mouvement (rempli pour les achats) : permet de
            // valoriser en MRU ce qui est entré et ce qui est sorti.
            $table->decimal('unit_cost', 12, 2)->nullable()->after('quantity');
        });
    }

    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropColumn('unit_cost');
        });
    }
};
