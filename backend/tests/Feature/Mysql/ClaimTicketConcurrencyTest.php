<?php

namespace Tests\Feature\Mysql;

use App\Enums\ActivityType;
use App\Models\Category;
use App\Models\Ticket;
use App\Models\User;
use App\Services\Assignment\AssignmentService;
use App\Services\Assignment\TicketAlreadyAssignedException;
use PHPUnit\Framework\Attributes\Group;

#[Group('mysql')]
class ClaimTicketConcurrencyTest extends MysqlTestCase
{
    public function test_only_one_of_many_simultaneous_claims_wins(): void
    {
        $category = Category::factory()->create(['name' => 'Concurrency '.uniqid(), 'slug' => 'concurrency-'.uniqid()]);
        $customer = User::factory()->create();
        $ticket = Ticket::factory()->createdBy($customer)->create(['category_id' => $category->id]);
        $agents = User::factory()->agent()->count(6)->create();

        try {
            $outcomes = $this->runInParallel($agents->count(), function (int $index) use ($ticket, $agents) {
                try {
                    app(AssignmentService::class)->claim($ticket->fresh(), $agents[$index]);

                    return 'won';
                } catch (TicketAlreadyAssignedException) {
                    return 'lost';
                }
            });

            $this->assertSame(1, count(array_keys($outcomes, 'won')));
            $this->assertSame($agents->count() - 1, count(array_keys($outcomes, 'lost')));
            $this->assertContains($ticket->fresh()->assigned_agent_id, $agents->pluck('id'));
            $this->assertSame(1, $ticket->activities()->where('type', ActivityType::Claimed)->count());
        } finally {
            $ticket->delete();
            User::whereIn('id', $agents->pluck('id')->push($customer->id))->delete();
            $category->delete();
        }
    }
}
