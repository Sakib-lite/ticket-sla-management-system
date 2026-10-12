<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\SlaPolicy;
use App\Models\Ticket;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\SlaPolicySeeder;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SlaPolicySeeder::class);
    }

    public function test_staff_see_sla_policies_from_most_to_least_urgent(): void
    {
        $response = $this->actingAs(User::factory()->agent()->create())->getJson('/api/settings/sla-policies')->assertOk();

        $this->assertSame(['critical', 'high', 'medium', 'low'], array_column($response->json(), 'priority'));
        $this->assertSame(2, $response->json('0.resolution_hours'));
        $this->actingAs(User::factory()->create())->getJson('/api/settings/sla-policies')->assertForbidden();
    }

    public function test_changing_an_sla_policy_affects_new_tickets_immediately(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-12 09:00', 'Asia/Dhaka'));
        $admin = User::factory()->administrator()->create();
        $policy = SlaPolicy::where('priority', 'critical')->firstOrFail();
        $payload = ['subject' => 'S', 'description' => 'D', 'priority' => 'critical', 'category_id' => Category::factory()->create()->id];

        $before = $this->actingAs(User::factory()->create())->postJson('/api/tickets', $payload)->json('id');
        $this->actingAs($admin)->patchJson("/api/settings/sla-policies/{$policy->id}", ['resolution_hours' => 4, 'approaching_threshold_percent' => 50])
            ->assertOk()
            ->assertJsonPath('resolution_hours', 4);
        $after = $this->actingAs(User::factory()->create())->postJson('/api/tickets', $payload)->json('id');

        $this->assertSame('11:00', Ticket::findOrFail($before)->sla_due_at->setTimezone('Asia/Dhaka')->format('H:i'));
        $this->assertSame('13:00', Ticket::findOrFail($after)->sla_due_at->setTimezone('Asia/Dhaka')->format('H:i'));
        $this->assertSame('11:00', Ticket::findOrFail($after)->sla_approaching_at->setTimezone('Asia/Dhaka')->format('H:i'));
    }

    public function test_sla_policy_changes_are_validated_and_admin_only(): void
    {
        $policy = SlaPolicy::where('priority', 'low')->firstOrFail();

        $this->actingAs(User::factory()->agent()->create())
            ->patchJson("/api/settings/sla-policies/{$policy->id}", ['resolution_hours' => 4, 'approaching_threshold_percent' => 50])
            ->assertForbidden();

        $this->actingAs(User::factory()->administrator()->create())
            ->patchJson("/api/settings/sla-policies/{$policy->id}", ['resolution_hours' => 0, 'approaching_threshold_percent' => 100])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['resolution_hours', 'approaching_threshold_percent']);
    }

    public function test_business_hours_are_readable_by_everyone_signed_in(): void
    {
        $this->actingAs(User::factory()->create())->getJson('/api/settings/business-hours')
            ->assertOk()
            ->assertJsonPath('timezone', 'Asia/Dhaka')
            ->assertJsonPath('opens_at', '09:00')
            ->assertJsonPath('closes_at', '18:00')
            ->assertJsonPath('working_days.0.label', 'Sunday')
            ->assertJsonPath('working_days.4.label', 'Thursday');
    }

    public function test_administrators_manage_categories(): void
    {
        $admin = User::factory()->administrator()->create();

        $id = $this->actingAs($admin)->postJson('/api/categories', ['name' => 'Facilities'])
            ->assertCreated()
            ->assertJsonPath('slug', 'facilities')
            ->json('id');

        $this->actingAs($admin)->patchJson("/api/categories/{$id}", ['name' => 'Office Facilities', 'is_active' => false])
            ->assertOk()
            ->assertJsonPath('slug', 'office-facilities')
            ->assertJsonPath('is_active', false);

        $this->actingAs($admin)->postJson('/api/categories', ['name' => 'Office Facilities'])->assertUnprocessable()->assertJsonValidationErrors('name');
        $this->actingAs(User::factory()->agent()->create())->postJson('/api/categories', ['name' => 'Nope'])->assertForbidden();
    }

    public function test_inactive_categories_are_only_listed_for_administrators_who_ask(): void
    {
        Category::factory()->create(['name' => 'Active']);
        Category::factory()->inactive()->create(['name' => 'Retired']);
        $admin = User::factory()->administrator()->create();

        $this->actingAs(User::factory()->agent()->create())->getJson('/api/categories?include_inactive=1')->assertJsonCount(1);
        $this->actingAs($admin)->getJson('/api/categories')->assertJsonCount(1);
        $this->actingAs($admin)->getJson('/api/categories?include_inactive=1')->assertJsonCount(2);
    }
}
