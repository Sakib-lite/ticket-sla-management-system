<?php

namespace Tests\Feature\Mysql;

use App\Models\Category;
use App\Models\Ticket;
use App\Models\User;
use PHPUnit\Framework\Attributes\Group;

#[Group('mysql')]
class TicketFullTextSearchTest extends MysqlTestCase
{
    public function test_keyword_search_uses_the_full_text_index_on_subject_and_description(): void
    {
        $token = 'zebra'.uniqid();
        $category = Category::factory()->create(['name' => 'Search '.uniqid(), 'slug' => 'search-'.uniqid()]);
        $customer = User::factory()->create();
        $agent = User::factory()->agent()->create();
        $bySubject = Ticket::factory()->createdBy($customer)->create(['category_id' => $category->id, 'subject' => "Printer {$token} jammed"]);
        $byDescription = Ticket::factory()->createdBy($customer)->create(['category_id' => $category->id, 'description' => "The {$token} appears in the body only."]);
        $unrelated = Ticket::factory()->createdBy($customer)->create(['category_id' => $category->id]);

        try {
            $response = $this->actingAs($agent)->getJson("/api/tickets?view=all&search={$token}")->assertOk();

            $this->assertEqualsCanonicalizing([$bySubject->id, $byDescription->id], array_column($response->json('data'), 'id'));
            $this->assertNotContains($unrelated->id, array_column($response->json('data'), 'id'));
        } finally {
            Ticket::whereIn('id', [$bySubject->id, $byDescription->id, $unrelated->id])->delete();
            User::whereIn('id', [$customer->id, $agent->id])->delete();
            $category->delete();
        }
    }
}
