<?php

use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\CashSessionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\StockItemController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Photo d'un produit servie par l'application (accessible à tout utilisateur connecté).
    Route::get('products/{product}/image', [ProductController::class, 'image'])->name('products.image');
});

// Caisse & ventes : accessibles à l'admin, au gérant et au caissier.
Route::middleware(['auth', 'role:admin,gerant,caissier'])->group(function () {
    Route::get('caisse', [CashSessionController::class, 'index'])->name('caisse.index');
    Route::post('caisse/open', [CashSessionController::class, 'open'])->name('caisse.open');
    Route::post('caisse/close', [CashSessionController::class, 'close'])->name('caisse.close');

    Route::get('ventes', [SaleController::class, 'create'])->name('sales.create');
    Route::get('ventes/historique', [SaleController::class, 'index'])->name('sales.index');
    Route::post('ventes', [SaleController::class, 'store'])->name('sales.store');
});

// Gestion opérationnelle : réservée à l'admin et au gérant.
Route::middleware(['auth', 'role:admin,gerant'])->group(function () {
    Route::resource('depenses', ExpenseController::class)
        ->parameters(['depenses' => 'expense'])
        ->names('expenses')
        ->except(['show']);

    Route::get('rapports/journalier', [ReportController::class, 'daily'])->name('reports.daily');
    Route::get('rapports/hebdomadaire', [ReportController::class, 'weekly'])->name('reports.weekly');
    Route::get('rapports/mensuel', [ReportController::class, 'monthly'])->name('reports.monthly');

    Route::put('products/{product}/recipe', [ProductController::class, 'updateRecipe'])->name('products.recipe');
    Route::resource('products', ProductController::class)->except(['show']);

    Route::post('stock-items/{stockItem}/movements', [StockItemController::class, 'movement'])
        ->name('stock-items.movement');

    Route::resource('stock-items', StockItemController::class)
        ->parameters(['stock-items' => 'stockItem'])
        ->except(['show']);
});

// Espace d'administration : réservé au rôle admin.
Route::middleware(['auth', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::patch('users/{user}/toggle', [UserController::class, 'toggle'])->name('users.toggle');
        Route::resource('users', UserController::class)->except(['show']);
    });

require __DIR__.'/auth.php';
