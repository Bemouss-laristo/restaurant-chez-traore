<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\ExpenseCategory;
use App\Enums\PaymentMethod;
use App\Http\Requests\StorePurchaseRequest;
use App\Models\Expense;
use App\Models\StockItem;
use App\Models\Supplier;
use App\Services\CashSessionService;
use App\Services\PurchaseService;
use App\Support\BusinessDay;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Saisie des achats de marchandises : stock + dépense en une seule fois.
 */
class PurchaseController extends Controller
{
    public function __construct(
        private readonly PurchaseService $purchases,
        private readonly CashSessionService $cash,
    ) {
    }

    public function create(Request $request): View
    {
        return view('purchases.create', [
            'items' => StockItem::with('supplier')->orderBy('name')->get(),
            'suppliers' => Supplier::active()->orderBy('name')->get(),
            'paymentMethods' => PaymentMethod::options(),
            'today' => BusinessDay::today(),
            'configurable' => StockItem::orderBy('name')->get(),
            // Articles à prix convenu : on ne saisit que la quantité prise.
            'agreed' => StockItem::with('supplier')
                ->whereNotNull('supplier_id')
                ->where('agreed_unit_price', '>', 0)
                ->orderBy('name')
                ->get(),
            // Paramétrage à moitié fait : l'article n'apparaîtra pas dans la prise
            // du jour, et personne ne saurait pourquoi. On le dit ici, avec le lien
            // qui permet de corriger.
            'incomplete' => StockItem::with('supplier')
                ->where(fn ($q) => $q
                    ->where(fn ($sub) => $sub->whereNotNull('supplier_id')->where(fn ($p) => $p->whereNull('agreed_unit_price')->orWhere('agreed_unit_price', '<=', 0)))
                    ->orWhere(fn ($sub) => $sub->whereNull('supplier_id')->where('agreed_unit_price', '>', 0)))
                ->orderBy('name')
                ->get(),
            'recent' => Expense::with(['user', 'supplier', 'stockMovements.stockItem'])
                ->where('expense_category', ExpenseCategory::AchatMarchandises->value)
                ->latest('spent_at')
                ->latest('id')
                ->limit(10)
                ->get(),
        ]);
    }

    public function store(StorePurchaseRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $user = $request->user();
        $method = PaymentMethod::from($data['payment_method']);

        $expense = $this->purchases->record(
            $user,
            $data['lines'],
            $method,
            isset($data['supplier_id']) ? Supplier::find($data['supplier_id']) : null,
            $data['spent_at'],
            $this->cash->currentFor($user),
        );

        return redirect()
            ->route('purchases.create')
            ->with('status', 'Achat enregistré : '.number_format((float) $expense->amount, 0, ',', ' ').' MRU ajoutés au stock et aux dépenses.');
    }

    /**
     * Prise du jour chez un fournisseur à prix convenu.
     *
     * On ne saisit QUE la quantité prise (30 paquets de pain). Le prix unitaire est
     * celui convenu avec le fournisseur, donc le montant se calcule tout seul et vient
     * s'ajouter à ce qu'on lui devra en fin de mois. C'est la saisie de tous les jours :
     * si elle prend plus de dix secondes, elle ne sera pas faite.
     */
    public function quick(Request $request, StockItem $stockItem): RedirectResponse
    {
        if (! $stockItem->hasAgreedPrice()) {
            return back()->with('error', "Cet article n'a pas de prix convenu avec un fournisseur.");
        }

        $data = $request->validate([
            'quantity' => ['required', 'numeric', 'gt:0', 'max:100000'],
        ], [
            'quantity.required' => 'Indique la quantité prise.',
            'quantity.gt' => 'La quantité doit être supérieure à zéro.',
        ]);

        $quantity = (float) $data['quantity'];
        $price = round($quantity * (float) $stockItem->agreed_unit_price, 2);
        $user = $request->user();

        $this->purchases->record(
            $user,
            [[
                'stock_item_id' => $stockItem->id,
                'quantity' => $quantity,
                'total_price' => $price,
                'pack' => false,
            ]],
            $stockItem->default_payment_method ?? PaymentMethod::Credit,
            $stockItem->supplier,
            BusinessDay::today(),
            $this->cash->currentFor($user),
        );

        $method = $stockItem->default_payment_method ?? PaymentMethod::Credit;
        $suffix = $method === PaymentMethod::Credit
            ? ' — à payer à '.$stockItem->supplier->name.' en fin de mois.'
            : '.';

        return back()->with('status', sprintf(
            'Pris aujourd\'hui : %s %s de %s = %s MRU%s',
            rtrim(rtrim(number_format($quantity, 3, ',', ' '), '0'), ','),
            $stockItem->unit->value,
            $stockItem->name,
            number_format($price, 0, ',', ' '),
            $suffix,
        ));
    }

    /**
     * Corriger la quantité d'une saisie.
     *
     * On annule puis on ré-enregistre : c'est plus sûr que de rattraper à la main
     * le stock, la dépense et la dette, qui doivent rester cohérents entre eux.
     * Réservé aux saisies d'un seul article — au-delà, mieux vaut supprimer et refaire.
     */
    public function updateQuantity(Request $request, Expense $expense): RedirectResponse
    {
        if ($expense->expense_category !== ExpenseCategory::AchatMarchandises) {
            return back()->with('error', "Cette dépense n'est pas un achat de marchandises.");
        }

        $movements = $expense->stockMovements()->with('stockItem')->get();

        if ($movements->count() !== 1) {
            return back()->with('error', 'Cette saisie contient plusieurs articles : supprime-la et refais-la.');
        }

        $data = $request->validate([
            'quantity' => ['required', 'numeric', 'gt:0', 'max:100000'],
        ], ['quantity.gt' => 'La quantité doit être supérieure à zéro.']);

        $movement = $movements->first();
        $item = $movement->stockItem;

        if ($item === null) {
            return back()->with('error', "L'article de cette saisie n'existe plus.");
        }

        $quantity = (float) $data['quantity'];
        // Le prix unitaire ne change pas : c'est la quantité qui était fausse.
        $unitPrice = (float) $item->agreed_unit_price > 0
            ? (float) $item->agreed_unit_price
            : (float) ($movement->unit_cost ?? 0);

        $user = $request->user();
        $supplier = $expense->supplier;
        $method = $expense->payment_method;
        $spentAt = $expense->spent_at->toDateString();

        $this->purchases->revert($expense);

        $this->purchases->record(
            $user,
            [[
                'stock_item_id' => $item->id,
                'quantity' => $quantity,
                'total_price' => round($quantity * $unitPrice, 2),
                'pack' => false,
            ]],
            $method,
            $supplier,
            $spentAt,
            $this->cash->currentFor($user),
        );

        return back()->with('status', 'Saisie corrigée : '.$item->name.' — '
            .rtrim(rtrim(number_format($quantity, 3, ',', ' '), '0'), ',').' '.$item->unit->value.'.');
    }

    /** Supprimer une saisie faite par erreur : le stock et la dette reviennent en arrière. */
    public function destroy(Expense $expense): RedirectResponse
    {
        if ($expense->expense_category !== ExpenseCategory::AchatMarchandises) {
            return back()->with('error', "Cette dépense n'est pas un achat de marchandises.");
        }

        $label = number_format((float) $expense->amount, 0, ',', ' ').' MRU';
        $this->purchases->revert($expense);

        return back()->with('status', 'Saisie supprimée ('.$label.') : le stock et le compte du fournisseur ont été remis comme avant.');
    }

    /**
     * Ajouter un article (et au besoin son fournisseur) à la prise du jour,
     * sans quitter la page. Sinon il faut passer par Fournisseurs puis par Stock,
     * et une configuration en deux écrans ne se fait jamais.
     */
    public function configure(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'stock_item_id' => ['required', 'integer', 'exists:stock_items,id'],
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'new_supplier' => ['nullable', 'string', 'max:120'],
            'new_supplier_phone' => ['nullable', 'string', 'max:30'],
            'agreed_unit_price' => ['required', 'numeric', 'gt:0'],
            'daily_quantity' => ['nullable', 'numeric', 'min:0'],
            'payment_method' => ['required', Rule::enum(PaymentMethod::class)],
        ], [
            'agreed_unit_price.required' => 'Indique le prix convenu par unité.',
        ]);

        $name = trim((string) ($data['new_supplier'] ?? ''));

        if ($name === '' && empty($data['supplier_id'])) {
            return back()->with('error', 'Choisis un fournisseur existant ou donne le nom d\'un nouveau.');
        }

        $supplier = $name !== ''
            ? Supplier::firstOrCreate(['name' => $name], [
                'phone' => $data['new_supplier_phone'] ?? null,
                'is_active' => true,
            ])
            : Supplier::findOrFail($data['supplier_id']);

        $item = StockItem::findOrFail($data['stock_item_id']);
        $item->update([
            'supplier_id' => $supplier->id,
            'agreed_unit_price' => $data['agreed_unit_price'],
            'daily_quantity' => ($data['daily_quantity'] ?? 0) > 0 ? $data['daily_quantity'] : null,
            'default_payment_method' => PaymentMethod::from($data['payment_method']),
        ]);

        return back()->with('status', $item->name.' est maintenant rattaché à '.$supplier->name
            .' et apparaît dans la prise du jour.');
    }
}
