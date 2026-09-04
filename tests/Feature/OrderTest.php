<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Product;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_customer_can_place_an_order(): void
    {
        $product = Product::factory()->create(['sale_price' => 200]);

        $response = $this->post('/commander', [
            'customer_name' => 'Client Test',
            'customer_phone' => '49625325',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 2],
            ],
        ]);

        $response->assertRedirect(route('order.thanks'));
        $this->assertDatabaseHas('orders', [
            'customer_name' => 'Client Test',
            'total' => 400.00,
            'status' => OrderStatus::Nouvelle->value,
        ]);
    }

    public function test_an_order_requires_a_phone_number(): void
    {
        $product = Product::factory()->create();

        $this->post('/commander', [
            'customer_name' => 'Client Test',
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ])->assertSessionHasErrors('customer_phone');
    }

    public function test_staff_checkout_turns_an_order_into_a_sale(): void
    {
        $user = User::factory()->caissier()->create();
        $product = Product::factory()->create(['sale_price' => 200]);

        $order = app(OrderService::class)->createFromCart(
            [['product_id' => $product->id, 'quantity' => 1]],
            'Client Test',
            '49625325',
            null,
        );

        $this->actingAs($user)
            ->post(route('orders.checkout', $order), ['payment_method' => 'especes'])
            ->assertRedirect();

        $order->refresh();
        $this->assertEquals(OrderStatus::Terminee, $order->status);
        $this->assertNotNull($order->sale_id);
        $this->assertDatabaseHas('sales', ['id' => $order->sale_id, 'total' => 200.00]);
    }
}
