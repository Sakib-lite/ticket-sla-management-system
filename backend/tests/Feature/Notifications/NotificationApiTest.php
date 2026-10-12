<?php

namespace Tests\Feature\Notifications;

use App\Models\Ticket;
use App\Models\User;
use App\Notifications\TicketCreatedNotification;
use App\Notifications\TicketResolvedNotification;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NotificationApiTest extends TestCase
{
    public function test_users_only_see_their_own_notifications_with_an_unread_count(): void
    {
        $me = User::factory()->create();
        $someoneElse = User::factory()->create();
        $ticket = Ticket::factory()->createdBy($me)->create();
        Notification::sendNow($me, new TicketCreatedNotification($ticket));
        Notification::sendNow($me, new TicketResolvedNotification($ticket, User::factory()->agent()->create()));
        Notification::sendNow($someoneElse, new TicketCreatedNotification($ticket));
        $me->notifications()->oldest()->first()->markAsRead();

        $response = $this->actingAs($me)->getJson('/api/notifications')->assertOk();

        $response->assertJsonCount(2, 'data')
            ->assertJsonPath('unread_count', 1)
            ->assertJsonPath('data.0.kind', 'ticket_resolved')
            ->assertJsonPath('data.0.data.ticket_number', $ticket->ticket_number)
            ->assertJsonPath('data.0.read_at', null);
    }

    public function test_a_notification_can_be_marked_as_read(): void
    {
        $me = User::factory()->create();
        $ticket = Ticket::factory()->createdBy($me)->create();
        Notification::sendNow($me, new TicketCreatedNotification($ticket));
        $notification = $me->notifications()->firstOrFail();

        $this->actingAs($me)->postJson("/api/notifications/{$notification->id}/read")
            ->assertOk()
            ->assertJsonPath('id', $notification->id);

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_someone_elses_notification_cannot_be_marked_as_read(): void
    {
        $owner = User::factory()->create();
        $ticket = Ticket::factory()->createdBy($owner)->create();
        Notification::sendNow($owner, new TicketCreatedNotification($ticket));
        $notification = $owner->notifications()->firstOrFail();

        $this->actingAs(User::factory()->create())->postJson("/api/notifications/{$notification->id}/read")->assertNotFound();

        $this->assertNull($notification->fresh()->read_at);
    }

    public function test_all_notifications_can_be_marked_as_read_at_once(): void
    {
        $me = User::factory()->create();
        $ticket = Ticket::factory()->createdBy($me)->create();
        Notification::sendNow($me, new TicketCreatedNotification($ticket));
        Notification::sendNow($me, new TicketCreatedNotification($ticket));

        $this->actingAs($me)->postJson('/api/notifications/read-all')->assertOk();

        $this->assertSame(0, $me->unreadNotifications()->count());
        $this->actingAs($me)->getJson('/api/notifications')->assertJsonPath('unread_count', 0);
    }

    public function test_guests_cannot_read_notifications(): void
    {
        $this->getJson('/api/notifications')->assertUnauthorized();
    }
}
