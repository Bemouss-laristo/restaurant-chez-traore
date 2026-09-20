<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            // Livraison rattachée à un compte fournisseur (payée ou à crédit).
            $table->foreignId('supplier_id')->nullable()->after('cash_session_id')->constrained()->nullOnDelete();
            // Paiement de salaire : à qui, et pour quel mois (AAAA-MM).
            $table->foreignId('staff_id')->nullable()->after('supplier_id')->constrained('staff')->nullOnDelete();
            $table->string('salary_month', 7)->nullable()->after('staff_id');
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('supplier_id');
            $table->dropConstrainedForeignId('staff_id');
            $table->dropColumn('salary_month');
        });
    }
};
