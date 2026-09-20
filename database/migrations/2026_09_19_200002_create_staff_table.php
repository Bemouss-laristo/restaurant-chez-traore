<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Employés du restaurant (cuisiniers, serveurs…), qu'ils aient un compte ou non.
        Schema::create('staff', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('job_title');
            $table->decimal('monthly_salary', 12, 2)->default(0);
            $table->string('phone')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('budgets', function (Blueprint $table) {
            $table->id();
            $table->string('month', 7);            // AAAA-MM
            $table->string('expense_category');    // App\Enums\ExpenseCategory
            $table->decimal('amount', 12, 2);
            $table->timestamps();

            $table->unique(['month', 'expense_category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budgets');
        Schema::dropIfExists('staff');
    }
};
