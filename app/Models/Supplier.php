<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Fournisseur à compte : on prend la marchandise pendant le mois (à crédit),
 * il envoie sa facture à la fin, on la paie.
 */
class Supplier extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'phone', 'note', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SupplierPayment::class);
    }

    /** Articles de stock dont il est le fournisseur habituel (pain arabe, viande hachée…). */
    public function stockItems(): HasMany
    {
        return $this->hasMany(StockItem::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /** Total des livraisons prises à crédit (non réglées sur le moment). */
    public function creditTotal(?string $from = null, ?string $to = null): float
    {
        return (float) $this->deliveries()
            ->where('payment_method', PaymentMethod::Credit->value)
            ->when($from, fn ($q) => $q->whereDate('spent_at', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('spent_at', '<=', $to))
            ->sum('amount');
    }

    public function paidTotal(?string $from = null, ?string $to = null): float
    {
        return (float) $this->payments()
            ->when($from, fn ($q) => $q->whereDate('paid_at', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('paid_at', '<=', $to))
            ->sum('amount');
    }

    /** Ce qu'on lui doit aujourd'hui : tout ce qui a été pris à crédit, moins tout ce qui a été payé. */
    public function balance(): float
    {
        return round($this->creditTotal() - $this->paidTotal(), 2);
    }

    /**
     * Avance versée : quand on a payé d'avance (abonnement mensuel), le solde
     * devient négatif. C'est de l'argent déjà sorti, que le fournisseur nous doit
     * encore en marchandise.
     */
    public function advance(): float
    {
        return max(0, -$this->balance());
    }

    public function hasAdvance(): bool
    {
        return $this->balance() < 0;
    }
}
