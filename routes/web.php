<?php

use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\CashSessionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicOrderController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\StockItemController;
use App\Http\Controllers\StockReconciliationController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $featured = \App\Models\Product::active()->whereNotNull('image_path')->inRandomOrder()->limit(6)->get();
    if ($featured->isEmpty()) {
        $featured = \App\Models\Product::active()->inRandomOrder()->limit(6)->get();
    }

    return view('welcome', ['featured' => $featured]);
});

// Commande en ligne (public, sans connexion).
Route::get('/commander', [PublicOrderController::class, 'create'])->name('order.create');
Route::post('/commander', [PublicOrderController::class, 'store'])->name('order.store')->middleware('throttle:orders');
Route::get('/commander/merci', [PublicOrderController::class, 'thanks'])->name('order.thanks');

// Photo d'un produit : publique (c'est un menu montré aux clients).
Route::get('products/{product}/image', [ProductController::class, 'image'])->name('products.image');

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Caisse & ventes : accessibles à l'admin, au gérant et au caissier.
Route::middleware(['auth', 'role:admin,gerant,caissier'])->group(function () {
    Route::get('caisse', [CashSessionController::class, 'index'])->name('caisse.index');
    Route::post('caisse/open', [CashSessionController::class, 'open'])->name('caisse.open');
    Route::post('caisse/close', [CashSessionController::class, 'close'])->name('caisse.close');

    Route::get('ventes', [SaleController::class, 'create'])->name('sales.create');
    Route::get('ventes/historique', [SaleController::class, 'index'])->name('sales.index');
    Route::post('ventes', [SaleController::class, 'store'])->name('sales.store');
    Route::get('ventes/{sale}/ticket', [SaleController::class, 'receipt'])->name('sales.receipt');
    Route::get('ventes/{sale}', [SaleController::class, 'show'])->name('sales.show');

    // Commandes en ligne reçues des clients.
    Route::get('commandes', [OrderController::class, 'index'])->name('orders.index');
    Route::get('commandes/count', [OrderController::class, 'count'])->name('orders.count');
    Route::post('commandes/{order}/confirmer', [OrderController::class, 'confirm'])->name('orders.confirm');
    Route::post('commandes/{order}/annuler', [OrderController::class, 'reject'])->name('orders.reject');
    Route::post('commandes/{order}/encaisser', [OrderController::class, 'checkout'])->name('orders.checkout');
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
    Route::get('rapports/stock', [ReportController::class, 'stock'])->name('reports.stock');
    Route::get('rapports/reconciliation', [StockReconciliationController::class, 'index'])->name('reconciliation.index');
    Route::post('rapports/reconciliation', [StockReconciliationController::class, 'store'])->name('reconciliation.store');

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
