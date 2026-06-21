<?php

namespace Tests\Feature;

use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductLicense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartTest extends TestCase
{
    use RefreshDatabase;

    private function createProductWithLicense(): array
    {
        $product = Product::create(['name' => 'Office', 'slug' => 'office', 'status' => 'active']);
        $license = ProductLicense::create([
            'product_id' => $product->id,
            'name'       => 'Home',
            'price'      => 5000.00,
            'sort_order' => 0,
        ]);
        return [$product, $license];
    }

    public function test_authenticated_user_can_add_to_cart_with_user_id(): void
    {
        $user = User::factory()->create();
        [$product, $license] = $this->createProductWithLicense();

        $token = $user->createToken('test')->plainTextToken;

        $this->withHeaders(['Authorization' => 'Bearer ' . $token])
             ->postJson('/api/cart', ['product_license_id' => $license->id, 'quantity' => 1])
             ->assertStatus(201);

        $this->assertDatabaseHas('cart_items', [
            'user_id'            => $user->id,
            'product_license_id' => $license->id,
        ]);
    }

    public function test_cart_user_id_is_not_null_for_authenticated_user(): void
    {
        $user = User::factory()->create();
        [$product, $license] = $this->createProductWithLicense();

        $token = $user->createToken('test')->plainTextToken;

        $this->withHeaders(['Authorization' => 'Bearer ' . $token])
             ->postJson('/api/cart', ['product_license_id' => $license->id]);

        $item = CartItem::where('product_license_id', $license->id)->first();
        $this->assertNotNull($item->user_id, 'user_id must not be null for authenticated users');
        $this->assertEquals($user->id, $item->user_id);
    }

    public function test_authenticated_user_sees_own_cart(): void
    {
        $user = User::factory()->create();
        [$product, $license] = $this->createProductWithLicense();

        CartItem::create([
            'user_id'            => $user->id,
            'product_id'         => $product->id,
            'product_license_id' => $license->id,
            'quantity'           => 2,
        ]);

        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
             ->getJson('/api/cart');

        $response->assertOk();
        $this->assertCount(1, $response->json('items'));
        $this->assertEquals(10000, $response->json('total'));
    }

    public function test_guest_cart_uses_session_id(): void
    {
        [$product, $license] = $this->createProductWithLicense();

        $this->postJson('/api/cart', ['product_license_id' => $license->id])
             ->assertStatus(201);

        $item = CartItem::where('product_license_id', $license->id)->first();
        $this->assertNull($item->user_id);
        $this->assertNotNull($item->session_id);
    }

    public function test_different_users_have_separate_carts(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        [$product, $license] = $this->createProductWithLicense();

        CartItem::create(['user_id' => $user1->id, 'product_id' => $product->id, 'product_license_id' => $license->id, 'quantity' => 3]);

        $token2 = $user2->createToken('test')->plainTextToken;
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token2])
             ->getJson('/api/cart');

        $response->assertOk();
        $this->assertCount(0, $response->json('items'));
    }
}
