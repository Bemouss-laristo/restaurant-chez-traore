<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_items', function (Blueprint $table) {
            // Fournisseur habituel de l'article : pré-sélectionné à la saisie d'un achat.
            $table->foreignId('supplier_id')->nullable()->after('name')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('stock_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('supplier_id');
        });
    }
};
