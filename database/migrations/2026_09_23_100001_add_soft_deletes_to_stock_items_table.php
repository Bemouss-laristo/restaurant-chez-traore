<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Un article qui a déjà servi (achats, ventes, comptages) ne peut pas être
     * effacé sans fausser l'historique des rapports. On l'archive : il disparaît
     * des listes et des formulaires, mais ses mouvements passés restent lisibles.
     */
    public function up(): void
    {
        Schema::table('stock_items', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('stock_items', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
