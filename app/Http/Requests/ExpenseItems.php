<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Support\Collection;

/**
 * Outils partagés par les formulaires de dépense (gérant, admin et caissier) :
 * une dépense est une LISTE D'ARTICLES avec leur prix, et le total est calculé
 * par le serveur — jamais saisi à la main.
 */
final class ExpenseItems
{
    /** @return list<array{label:string, price:mixed}> */
    public static function clean(mixed $items): array
    {
        return collect((array) $items)
            ->filter(fn ($row) => is_array($row) && (trim((string) ($row['label'] ?? '')) !== '' || trim((string) ($row['price'] ?? '')) !== ''))
            ->map(fn ($row) => ['label' => trim((string) ($row['label'] ?? '')), 'price' => $row['price'] ?? null])
            ->values()
            ->all();
    }

    /** @return array<string, string> */
    public static function messages(): array
    {
        return [
            'items.required' => 'Ajoute au moins un article avec son prix.',
            'items.min' => 'Ajoute au moins un article avec son prix.',
            'items.*.label.required' => "Écris le nom de l'article.",
            'items.*.price.required' => "Indique le prix de l'article.",
            'items.*.price.gt' => 'Le prix doit être supérieur à 0.',
        ];
    }

    /** Total en MRU d'une liste de lignes. */
    public static function total(array $items): float
    {
        return round((float) collect($items)->sum(fn ($row) => (float) $row['price']), 2);
    }

    /** Description « cahier » : une ligne par article. */
    public static function describe(array $items): string
    {
        return collect($items)
            ->map(fn ($row) => '• '.$row['label'].' — '.number_format((float) $row['price'], 0, ',', ' ').' MRU')
            ->implode("\n");
    }

    /**
     * Relit une description en lignes d'articles (pour rouvrir une dépense en modification).
     *
     * @return Collection<int, array{label:string, price:float}>
     */
    public static function parse(?string $description): Collection
    {
        return collect(preg_split('/\r?\n/', (string) $description))
            ->map(fn (string $line) => trim($line))
            ->filter()
            ->map(function (string $line) {
                if (preg_match('/^•\s*(.+?)\s+—\s+([\d\s ]+)\s*MRU$/u', $line, $m) === 1) {
                    return ['label' => $m[1], 'price' => (float) preg_replace('/[^\d]/', '', $m[2])];
                }

                return ['label' => ltrim($line, '• '), 'price' => 0.0];
            })
            ->values();
    }
}
