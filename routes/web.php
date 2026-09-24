<?php

use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\BalanceController;
use App\Http\Controllers\CashSessionController;
use App\Http\Controllers\CashierExpenseController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\EnvelopeController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\PublicOrderController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\StockItemController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\StockReconciliationController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    // L'application installée s'ouvre ici : l'équipe connectée va droit à son espace.
    if (request('source') === 'app' && auth()->check()) {
        return redirect()->route('dashboard');
    }

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

    // Cahier de dépenses du caissier (petites dépenses payées depuis la caisse).
    Route::get('caisse/depenses', [CashierExpenseController::class, 'index'])->name('cashier-expenses.index');
    Route::post('caisse/depenses', [CashierExpenseController::class, 'store'])->name('cashier-expenses.store');

    Route::get('ventes', [SaleController::class, 'create'])->name('sales.create');
    Route::get('ventes/historique', [SaleController::class, 'index'])->name('sales.index');
    Route::post('ventes', [SaleController::class, 'store'])->name('sales.store');
    Route::post('ventes/{sale}/annuler', [SaleController::class, 'cancel'])->name('sales.cancel');
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

    // Achats : stock + dépense en une seule saisie.
    Route::get('achats', [PurchaseController::class, 'create'])->name('purchases.create');
    Route::post('achats', [PurchaseController::class, 'store'])->name('purchases.store');
    Route::post('achats/prise-du-jour/{stockItem}', [PurchaseController::class, 'quick'])->name('purchases.quick');
    Route::post('achats/configurer', [PurchaseController::class, 'configure'])->name('purchases.configure');
    Route::put('achats/{expense}', [PurchaseController::class, 'updateQuantity'])->name('purchases.update');
    Route::delete('achats/{expense}', [PurchaseController::class, 'destroy'])->name('purchases.destroy');

    // Fournisseurs à compte : livraisons à crédit et règlements.
    Route::get('fournisseurs', [SupplierController::class, 'index'])->name('suppliers.index');
    Route::post('fournisseurs', [SupplierController::class, 'store'])->name('suppliers.store');
    Route::get('fournisseurs/{supplier}', [SupplierController::class, 'show'])->name('suppliers.show');
    Route::put('fournisseurs/{supplier}', [SupplierController::class, 'update'])->name('suppliers.update');
    Route::delete('fournisseurs/{supplier}', [SupplierController::class, 'destroy'])->name('suppliers.destroy');
    Route::post('fournisseurs/{supplier}/paiement', [SupplierController::class, 'pay'])->name('suppliers.pay');
    Route::delete('fournisseurs/{supplier}/paiement/{payment}', [SupplierController::class, 'deletePayment'])->name('suppliers.payments.destroy');

    // Employés et salaires.
    Route::get('employes', [StaffController::class, 'index'])->name('staff.index');
    Route::post('employes', [StaffController::class, 'store'])->name('staff.store');
    Route::patch('employes/{staff}', [StaffController::class, 'update'])->name('staff.update');
    Route::post('employes/{staff}/paiement', [StaffController::class, 'pay'])->name('staff.pay');

    // Relevés d'argent disponible (caisse + comptes mobiles).
    // Enveloppe du mois et réserve.
    Route::get('enveloppe', [EnvelopeController::class, 'index'])->name('envelope.index');
    Route::post('enveloppe', [EnvelopeController::class, 'store'])->name('envelope.store');
    Route::post('enveloppe/reserve', [EnvelopeController::class, 'reserve'])->name('envelope.reserve');

    Route::get('soldes', [BalanceController::class, 'index'])->name('balances.index');
    Route::post('soldes', [BalanceController::class, 'store'])->name('balances.store');

    Route::get('rapports/achats', [ReportController::class, 'purchases'])->name('reports.purchases');
    Route::get('rapports/tresorerie', [ReportController::class, 'treasury'])->name('reports.treasury');
    Route::post('rapports/tresorerie/budgets', [ReportController::class, 'storeBudgets'])->name('reports.budgets');
    Route::get('rapports/materiel', [ReportController::class, 'material'])->name('reports.material');
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
