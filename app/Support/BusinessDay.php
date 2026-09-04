<?php

declare(strict_types=1);

namespace App\Support;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * Gère la notion de « journée commerciale » d'un restaurant de nuit.
 *
 * La journée ne se réinitialise pas à minuit mais à l'heure configurée
 * (config restaurant.day_start_hour, par défaut 5h). Ainsi une soirée
 * de 19h à 2h du matin est comptée comme UNE seule journée.
 */
final class BusinessDay
{
    public static function startHour(): int
    {
        return (int) config('restaurant.day_start_hour', 5);
    }

    /** Date commerciale (Y-m-d) à laquelle appartient un instant donné. */
    public static function dateFor(CarbonInterface $moment): string
    {
        return $moment->copy()->subHours(self::startHour())->toDateString();
    }

    /** Date commerciale en cours (celle de maintenant). */
    public static function today(): string
    {
        return self::dateFor(Carbon::now());
    }

    /**
     * Fenêtre horaire [début, fin) d'une journée commerciale.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    public static function window(string $date): array
    {
        $start = Carbon::parse($date)->startOfDay()->addHours(self::startHour());

        return [$start, $start->copy()->addDay()];
    }
}
