<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Product extends Model
{
    /** @use HasFactory<\Database\Factories\ProductFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'product_category_id',
        'name',
        'sale_price',
        'estimated_cost',
        'description',
        'image_path',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'sale_price' => 'decimal:2',
            'estimated_cost' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    // ----- Relations -----

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'product_category_id');
    }

    /** Lignes de recette (accès direct au pivot enrichi). */
    public function recipeItems(): HasMany
    {
        return $this->hasMany(RecipeItem::class);
    }

    /** Ingrédients consommés, via la table pivot recipe_items. */
    public function stockItems(): BelongsToMany
    {
        return $this->belongsToMany(StockItem::class, 'recipe_items')
            ->withPivot('quantity_needed')
            ->withTimestamps();
    }

    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    // ----- Scopes -----

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    // ----- Image -----

    /**
     * URL de la photo du produit, ou une vignette par défaut selon la catégorie
     * si aucune photo n'a été uploadée.
     */
    public function imageUrl(): string
    {
        if ($this->image_path !== null && Storage::disk('public')->exists($this->image_path)) {
            // Servie par une route Laravel : ne dépend pas du lien symbolique storage:link.
            return route('products.image', $this);
        }

        $slug = Str::slug((string) optional($this->category)->name);
        $available = ['plats', 'snacks', 'boissons', 'desserts'];
        $file = in_array($slug, $available, true) ? $slug : 'default';

        return asset("images/placeholders/{$file}.svg");
    }
}
