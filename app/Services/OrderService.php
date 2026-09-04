<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

final class OrderService
{
    /**
     * Crée une commande client à partir d'un panier.
     *
     * @param  list<array{product_id:int, quantity:int}>  $items
     */
    public function createFromCart(array $items, string $name, string $phone, ?string $note): Order
    {
        return DB::transaction(function () use ($items, $name, $phone, $note) {
            $products = Product::active()
                ->whereIn('id', array_column($items, 'product_id'))
                ->get()
                ->keyBy('id');

            $order = Order::create([
                'order_number' => $this->nextNumber(),
                'customer_name' => $name,
                'customer_phone' => $phone,
                'note' => $note,
                'status' => OrderStatus::Nouvelle,
                'subtotal' => 0,
                'total' => 0,
            ]);

            $total = 0.0;

            foreach ($items as $row) {
                $product = $products[$row['product_id']] ?? null;
                if ($product === null) {
                    continue;
                }

                $quantity = (int) $row['quantity'];
                $unitPrice = (float) $product->sale_price;
                $lineTotal = $unitPrice * $quantity;
                $total += $lineTotal;

                $order->items()->create([
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'line_total' => $lineTotal,
                ]);
            }

            $order->update(['subtotal' => $total, 'total' => $total]);

            return $order;
        });
    }

    /** Numéro lisible du type C-20260902-0001, réinitialisé chaque jour. */
    private function nextNumber(): string
    {
        $today = now();
        $count = Order::whereDate('created_at', $today->toDateString())->count();

        return sprintf('C-%s-%04d', $today->format('Ymd'), $count + 1);
    }
}
