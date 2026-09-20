<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_items', function (Blueprint $table) {
            // Conditionnement d'achat : « Carton (6 sachets × 18) » = 108 pièces.
            $table->string('pack_label')->nullable()->after('unit');
            $table->decimal('pack_quantity', 12, 3)->nullable()->after('pack_label');
            // Article clé : compté physiquement chaque soir à la clôture.
            $table->boolean('is_key')->default(false)->after('alert_threshold');
        });
    }

    public function down(): void
    {
        Schema::table('stock_items', function (Blueprint $table) {
            $table->dropColumn(['pack_label', 'pack_quantity', 'is_key']);
        });
    }
};
