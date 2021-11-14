<?php

namespace Tests\Feature;

use App\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CategoryControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Sanctum::actingAs(
            factory(\App\User::class)->create()
        );
    }

    public function test_index()
    {
        factory(Category::class, 5)->create();

        $response = $this->getJson('/api/categories');

        $response->assertSuccessful();
        $response->assertHeader('content-type', 'application/json');
        $response->assertJsonCount(6, 'data');
    }

    public function test_create_new_category()
    {
        $data = [
            'name' => 'Hola',
        ];
        $response = $this->postJson('/api/categories', $data);

        $response->assertSuccessful();
        // $response->assertHeader('content-type', 'application/json');
        $this->assertDatabaseHas('categories', $data);
    }

    public function test_update_category()
    {
        /** @var Category $category */
        $category = factory(Category::class)->create();

        $data = [
            'name' => 'Update Category',
        ];

        $response = $this->patchJson("/api/categories/{$category->getKey()}", $data);

        $response->assertSuccessful();
        // $response->assertHeader('content-type', 'application/json');
    }

    public function test_show_category()
    {
        /** @var Category $category */
        $category = factory(Category::class)->create();

        $response = $this->getJson("/api/categories/{$category->getKey()}");

        $response->assertSuccessful();
        $response->assertHeader('content-type', 'application/json');
    }

    public function test_delete_category()
    {
        /** @var Category $category */
        $category = factory(Category::class)->create();

        $response = $this->deleteJson("/api/categories/{$category->getKey()}");

        $response->assertSuccessful();
        $response->assertHeader('content-type', 'application/json');
        $this->assertDeleted($category);
    }

}
