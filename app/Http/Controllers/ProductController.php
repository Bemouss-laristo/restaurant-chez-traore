<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Requests\UpdateRecipeRequest;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\StockItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Illuminate\View\View;

class ProductController extends Controller
{
    /** Toute la carte part dans la page : la recherche filtre alors à la frappe. */
    public function index(Request $request): View
    {
        return view('products.index', [
            'products' => Product::query()->with('category')->orderBy('name')->get(),
            'categories' => ProductCategory::orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('products.create', [
            'categories' => ProductCategory::orderBy('name')->get(),
        ]);
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('products', 'public');
        }
        unset($data['image']);

        Product::create($data);

        return redirect()
            ->route('products.index')
            ->with('status', 'Produit créé.');
    }

    public function edit(Product $product): View
    {
        $product->load('stockItems');

        return view('products.edit', [
            'product' => $product,
            'categories' => ProductCategory::orderBy('name')->get(),
            'stockItems' => StockItem::orderBy('name')->get(),
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('image')) {
            // Remplace l'ancienne photo si elle existe.
            if ($product->image_path && Storage::disk('public')->exists($product->image_path)) {
                Storage::disk('public')->delete($product->image_path);
            }
            $data['image_path'] = $request->file('image')->store('products', 'public');
        }
        unset($data['image']);

        $product->update($data);

        return redirect()
            ->route('products.index')
            ->with('status', 'Produit mis à jour.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        // Soft delete : le produit disparaît des listes mais l'historique des ventes reste intact.
        $name = $product->name;
        $product->delete();

        // Surtout pas back() : on reviendrait sur la fiche d'un produit archivé,
        // qui n'existe plus pour l'application (erreur 404).
        return redirect()
            ->route('products.index')
            ->with('status', '« '.$name.' » archivé : il disparaît de la carte et de la caisse, ses ventes passées restent dans les rapports.');
    }

    /**
     * Enregistre la recette (nomenclature) du produit et recalcule son coût estimé
     * à partir du coût des ingrédients consommés.
     */
    public function updateRecipe(UpdateRecipeRequest $request, Product $product): RedirectResponse
    {
        $items = $request->validated()['items'] ?? [];

        $stockItems = StockItem::whereIn('id', array_column($items, 'stock_item_id'))
            ->get()
            ->keyBy('id');

        $sync = [];
        $estimatedCost = 0.0;

        foreach ($items as $row) {
            $stockItemId = (int) $row['stock_item_id'];
            $quantity = (float) $row['quantity_needed'];

            $sync[$stockItemId] = ['quantity_needed' => $quantity];
            $estimatedCost += $quantity * (float) ($stockItems[$stockItemId]->unit_cost ?? 0);
        }

        $product->stockItems()->sync($sync);

        // On ne recalcule le coût que si une recette est définie, pour ne pas
        // écraser un coût saisi à la main quand on vide la recette.
        if ($items !== []) {
            $product->update(['estimated_cost' => round($estimatedCost, 2)]);
        }

        return back()->with('status', 'Recette enregistrée. Coût estimé mis à jour.');
    }

    /** Sert la photo du produit depuis le stockage privé (pas besoin de storage:link). */
    public function image(Product $product): BinaryFileResponse
    {
        abort_unless(
            $product->image_path && Storage::disk('public')->exists($product->image_path),
            404,
        );

        return response()->file(Storage::disk('public')->path($product->image_path));
    }
}
