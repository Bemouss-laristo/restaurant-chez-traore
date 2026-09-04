<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\PaymentMethod;
use App\Models\Sale;
use App\Models\User;
use App\Services\DashboardService;
use App\Support\BusinessDay;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class BusinessDayTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['restaurant.day_start_hour' => 5]);
    }

    public function test_business_date_shifts_by_start_hour(): void
    {
        // Soirée de travail
        $this->assertEquals('2026-09-01', BusinessDay::dateFor(Carbon::parse('2026-09-01 19:00')));
        // Après minuit mais avant 5h → même journée commerciale
        $this->assertEquals('2026-09-01', BusinessDay::dateFor(Carbon::parse('2026-09-02 01:30')));
        // Après 5h → nouvelle journée
        $this->assertEquals('2026-09-02', BusinessDay::dateFor(Carbon::parse('2026-09-02 05:30')));
    }

    public function test_dashboard_groups_the_whole_night_in_one_day(): void
    {
        // « Maintenant » = 2h du matin → la journée commerciale est celle de la veille (2026-09-01).
        Carbon::setTestNow('2026-09-02 02:00');

        $user = User::factory()->gerant()->create();

        Sale::factory()->create(['sold_at' => '2026-09-01 20:00', 'total' => 100, 'payment_method' => PaymentMethod::Especes]);
        Sale::factory()->create(['sold_at' => '2026-09-02 01:00', 'total' => 50, 'payment_method' => PaymentMethod::Especes]);
        // Celle-ci est APRÈS 5h → journée suivante, ne doit PAS compter.
        Sale::factory()->create(['sold_at' => '2026-09-02 06:00', 'total' => 999, 'payment_method' => PaymentMethod::Especes]);

        $stats = app(DashboardService::class)->todayStats($user);

        $this->assertEquals(150.0, (float) $stats['sales']);
        $this->assertEquals(2, $stats['orders']);

        Carbon::setTestNow();
    }
}
