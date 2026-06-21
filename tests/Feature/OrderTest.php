<?php

namespace Tests\Feature;

use App\Models\CartItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductLicense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    private function createUserWithCart(): array
    {
        $user    = User::factory()->create();
        $product = Product::create(['name' => 'Test', 'slug' => 'test', 'status' => 'active']);
        $license = ProductLicense::create([
            'product_id' => $product->id,
            'name'       => 'Standard',
            'price'      => 1000.00,
            'sort_order' => 0,
        ]);
        CartItem::create([
            'user_id'            => $user->id,
            'product_id'         => $product->id,
            'product_license_id' => $license->id,
            'quantity'           => 2,
        ]);

        return [$user, $product, $license];
    }

    public function test_authenticated_user_can_create_order(): void
    {
        [$user] = $this->createUserWithCart();

        $response = $this->actingAs($user, 'sanctum')
             ->postJson('/api/orders');

        $response->assertStatus(201)
                 ->assertJsonStructure(['id', 'order_number', 'total', 'items']);

        $this->assertDatabaseHas('orders', ['user_id' => $user->id]);
        $this->assertDatabaseMissing('cart_items', ['user_id' => $user->id]);
    }

    public function test_order_total_is_calculated_correctly(): void
    {
        [$user, , $license] = $this->createUserWithCart();

        $response = $this->actingAs($user, 'sanctum')
             ->postJson('/api/orders');

        $response->assertStatus(201);
        $this->assertEquals(2000.00, $response->json('total'));
    }

    public function test_cannot_create_order_with_empty_cart(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
             ->postJson('/api/orders')
             ->assertStatus(422);
    }

    public function test_guest_cannot_create_order(): void
    {
        $this->postJson('/api/orders')
             ->assertStatus(401);
    }

    public function test_user_can_list_own_orders(): void
    {
        $user  = User::factory()->create();
        $other = User::factory()->create();

        Order::create([
            'order_number'   => 'ORD-2026-000001',
            'user_id'        => $user->id,
            'status'         => 'pending',
            'customer_name'  => $user->name,
            'customer_email' => $user->email,
            'subtotal'       => 1000,
            'discount'       => 0,
            'total'          => 1000,
        ]);
        Order::create([
            'order_number'   => 'ORD-2026-000002',
            'user_id'        => $other->id,
            'status'         => 'pending',
            'customer_name'  => $other->name,
            'customer_email' => $other->email,
            'subtotal'       => 500,
            'discount'       => 0,
            'total'          => 500,
        ]);

        $response = $this->actingAs($user, 'sanctum')
             ->getJson('/api/orders');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    }

    public function test_admin_can_update_order_status(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $order = Order::create([
            'order_number'   => 'ORD-2026-000001',
            'user_id'        => User::factory()->create()->id,
            'status'         => 'pending',
            'customer_name'  => 'Test',
            'customer_email' => 'test@test.com',
            'subtotal'       => 1000,
            'discount'       => 0,
            'total'          => 1000,
        ]);

        $this->actingAs($admin, 'sanctum')
             ->patchJson("/api/admin/orders/{$order->id}/status", ['status' => 'completed'])
             ->assertOk()
             ->assertJsonFragment(['status' => 'completed']);
    }

    public function test_admin_update_status_validates_value(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $order = Order::create([
            'order_number'   => 'ORD-2026-000001',
            'user_id'        => User::factory()->create()->id,
            'status'         => 'pending',
            'customer_name'  => 'Test',
            'customer_email' => 'test@test.com',
            'subtotal'       => 1000,
            'discount'       => 0,
            'total'          => 1000,
        ]);

        $this->actingAs($admin, 'sanctum')
             ->patchJson("/api/admin/orders/{$order->id}/status", ['status' => 'invalid-status'])
             ->assertStatus(422);
    }

    public function test_non_admin_cannot_update_order_status(): void
    {
        $user  = User::factory()->create(['role' => 'customer']);
        $order = Order::create([
            'order_number'   => 'ORD-2026-000001',
            'user_id'        => $user->id,
            'status'         => 'pending',
            'customer_name'  => 'Test',
            'customer_email' => 'test@test.com',
            'subtotal'       => 1000,
            'discount'       => 0,
            'total'          => 1000,
        ]);

        $this->actingAs($user, 'sanctum')
             ->patchJson("/api/admin/orders/{$order->id}/status", ['status' => 'completed'])
             ->assertStatus(403);
    }
}
