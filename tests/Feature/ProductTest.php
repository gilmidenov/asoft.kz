<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    private function createProduct(array $attrs = []): Product
    {
        return Product::create(array_merge([
            'name'   => 'Test Product',
            'slug'   => 'test-product',
            'status' => 'active',
        ], $attrs));
    }

    public function test_can_list_products(): void
    {
        $this->createProduct();
        $this->createProduct(['name' => 'Second', 'slug' => 'second']);

        $this->getJson('/api/products')
             ->assertOk()
             ->assertJsonStructure(['data', 'total', 'current_page']);
    }

    public function test_search_works_with_like(): void
    {
        $this->createProduct(['name' => 'Microsoft Office', 'slug' => 'microsoft-office']);
        $this->createProduct(['name' => 'Adobe Photoshop', 'slug' => 'adobe-photoshop']);

        $response = $this->getJson('/api/products?search=microsoft');

        $response->assertOk();
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('Microsoft Office', $data[0]['name']);
    }

    public function test_search_is_case_insensitive(): void
    {
        $this->createProduct(['name' => 'Microsoft Office', 'slug' => 'microsoft-office']);
        $this->createProduct(['name' => 'Adobe', 'slug' => 'adobe']);

        $response = $this->getJson('/api/products?search=MICROSOFT');
        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    }

    public function test_search_by_description(): void
    {
        $this->createProduct([
            'name'              => 'SomeProduct',
            'slug'              => 'some-product',
            'short_description' => 'Professional spreadsheet software',
        ]);

        $response = $this->getJson('/api/products?search=spreadsheet');
        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    }

    public function test_filter_by_is_hit(): void
    {
        $this->createProduct(['name' => 'Hit', 'slug' => 'hit', 'is_hit' => true]);
        $this->createProduct(['name' => 'Normal', 'slug' => 'normal', 'is_hit' => false]);

        $response = $this->getJson('/api/products?is_hit=1');
        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('Hit', $response->json('data.0.name'));
    }

    public function test_inactive_products_not_shown(): void
    {
        $this->createProduct(['name' => 'Active', 'slug' => 'active', 'status' => 'active']);
        $this->createProduct(['name' => 'Inactive', 'slug' => 'inactive', 'status' => 'inactive']);

        $response = $this->getJson('/api/products');
        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    }

    public function test_can_view_single_product(): void
    {
        $product = $this->createProduct();

        $this->getJson("/api/products/{$product->slug}")
             ->assertOk()
             ->assertJsonFragment(['name' => 'Test Product']);
    }

    public function test_shows_404_for_nonexistent_product(): void
    {
        $this->getJson('/api/products/nonexistent-slug')
             ->assertStatus(404);
    }

    public function test_admin_can_create_product(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin, 'sanctum')
             ->postJson('/api/admin/products', [
                 'name'   => 'New Product',
                 'status' => 'active',
             ])
             ->assertStatus(201)
             ->assertJsonFragment(['name' => 'New Product']);

        $this->assertDatabaseHas('products', ['slug' => 'new-product']);
    }

    public function test_non_admin_cannot_create_product(): void
    {
        $user = User::factory()->create(['role' => 'customer']);

        $this->actingAs($user, 'sanctum')
             ->postJson('/api/admin/products', ['name' => 'Product'])
             ->assertStatus(403);
    }

    public function test_admin_can_update_product(): void
    {
        $admin   = User::factory()->create(['role' => 'admin']);
        $product = $this->createProduct();

        $this->actingAs($admin, 'sanctum')
             ->putJson("/api/admin/products/{$product->id}", ['status' => 'inactive'])
             ->assertOk()
             ->assertJsonFragment(['status' => 'inactive']);
    }

    public function test_admin_can_delete_product(): void
    {
        $admin   = User::factory()->create(['role' => 'admin']);
        $product = $this->createProduct();

        $this->actingAs($admin, 'sanctum')
             ->deleteJson("/api/admin/products/{$product->id}")
             ->assertStatus(204);

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    public function test_filter_by_category_slug(): void
    {
        $category = Category::create(['name' => 'OS', 'slug' => 'os', 'is_active' => true]);
        $this->createProduct(['name' => 'Windows', 'slug' => 'windows', 'category_id' => $category->id]);
        $this->createProduct(['name' => 'Office', 'slug' => 'office']);

        $response = $this->getJson('/api/products?category=os');
        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('Windows', $response->json('data.0.name'));
    }
}
