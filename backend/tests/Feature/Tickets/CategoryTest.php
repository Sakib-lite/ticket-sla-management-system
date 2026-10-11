<?php

namespace Tests\Feature\Tickets;

use App\Models\Category;
use App\Models\User;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    public function test_only_active_categories_are_listed_alphabetically(): void
    {
        Category::factory()->create(['name' => 'Software']);
        Category::factory()->create(['name' => 'Billing']);
        Category::factory()->inactive()->create(['name' => 'Legacy']);

        $response = $this->actingAs(User::factory()->create())->getJson('/api/categories')->assertOk();

        $this->assertSame(['Billing', 'Software'], array_column($response->json(), 'name'));
    }
}
