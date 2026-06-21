<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_categories(): void
    {
        Category::create(['name' => 'Software', 'slug' => 'software', 'is_active' => true]);
        Category::create(['name' => 'Hidden', 'slug' => 'hidden', 'is_active' => false]);

        $response = $this->getJson('/api/categories');
        $response->assertOk();

        $names = collect($response->json())->pluck('name')->toArray();
        $this->assertContains('Software', $names);
        $this->assertNotContains('Hidden', $names);
    }

    public function test_categories_include_children(): void
    {
        $parent = Category::create(['name' => 'Office', 'slug' => 'office', 'is_active' => true]);
        Category::create(['name' => 'Word', 'slug' => 'word', 'is_active' => true, 'parent_id' => $parent->id]);

        $response = $this->getJson('/api/categories');
        $response->assertOk();

        $category = collect($response->json())->firstWhere('slug', 'office');
        $this->assertCount(1, $category['children']);
    }

    public function test_can_show_category_by_slug(): void
    {
        Category::create(['name' => 'Software', 'slug' => 'software', 'is_active' => true]);

        $this->getJson('/api/categories/software')
             ->assertOk()
             ->assertJsonFragment(['name' => 'Software']);
    }

    public function test_admin_can_create_category(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin, 'sanctum')
             ->postJson('/api/admin/categories', [
                 'name'      => 'New Category',
                 'is_active' => true,
             ]);

        $response->assertStatus(201)
                 ->assertJsonFragment(['name' => 'New Category']);

        $this->assertDatabaseHas('categories', ['slug' => 'new-category']);
    }

    public function test_category_slug_auto_generated(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin, 'sanctum')
             ->postJson('/api/admin/categories', ['name' => 'New Software Category', 'is_active' => true]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('categories', ['slug' => 'new-software-category']);
    }

    public function test_admin_can_update_category(): void
    {
        $admin    = User::factory()->create(['role' => 'admin']);
        $category = Category::create(['name' => 'Old Name', 'slug' => 'old-name', 'is_active' => true]);

        $this->actingAs($admin, 'sanctum')
             ->putJson("/api/admin/categories/{$category->id}", ['name' => 'New Name'])
             ->assertOk()
             ->assertJsonFragment(['name' => 'New Name']);
    }

    public function test_admin_can_delete_category(): void
    {
        $admin    = User::factory()->create(['role' => 'admin']);
        $category = Category::create(['name' => 'Delete Me', 'slug' => 'delete-me', 'is_active' => true]);

        $this->actingAs($admin, 'sanctum')
             ->deleteJson("/api/admin/categories/{$category->id}")
             ->assertStatus(204);

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_non_admin_cannot_create_category(): void
    {
        $user = User::factory()->create(['role' => 'customer']);

        $this->actingAs($user, 'sanctum')
             ->postJson('/api/admin/categories', ['name' => 'Category'])
             ->assertStatus(403);
    }

    public function test_category_can_have_parent(): void
    {
        $admin  = User::factory()->create(['role' => 'admin']);
        $parent = Category::create(['name' => 'Parent', 'slug' => 'parent', 'is_active' => true]);

        $response = $this->actingAs($admin, 'sanctum')
             ->postJson('/api/admin/categories', [
                 'name'      => 'Child',
                 'parent_id' => $parent->id,
                 'is_active' => true,
             ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('categories', ['slug' => 'child', 'parent_id' => $parent->id]);
    }
}
