<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AccessControlTest extends TestCase
{
    use RefreshDatabase;

    public static function managementRoutes(): array
    {
        return [
            ['/products'],
            ['/stock-items'],
            ['/depenses'],
            ['/rapports/journalier'],
            ['/admin/users'],
        ];
    }

    #[DataProvider('managementRoutes')]
    public function test_cashier_is_forbidden_from_management_routes(string $url): void
    {
        $cashier = User::factory()->caissier()->create();

        $this->actingAs($cashier)->get($url)->assertForbidden();
    }

    public function test_cashier_can_reach_sales_and_cash(): void
    {
        $cashier = User::factory()->caissier()->create();

        $this->actingAs($cashier)->get('/ventes')->assertOk();
        $this->actingAs($cashier)->get('/caisse')->assertOk();
    }

    public function test_manager_can_manage_but_not_users(): void
    {
        $manager = User::factory()->gerant()->create();

        $this->actingAs($manager)->get('/products')->assertOk();
        $this->actingAs($manager)->get('/rapports/journalier')->assertOk();
        $this->actingAs($manager)->get('/admin/users')->assertForbidden();
    }

    public function test_admin_can_reach_everything(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/admin/users')->assertOk();
        $this->actingAs($admin)->get('/products')->assertOk();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/products')->assertRedirect('/login');
    }
}
