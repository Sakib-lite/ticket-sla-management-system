<?php

namespace Tests\Unit\Support;

use App\Enums\ActivityType;
use App\Models\TicketActivity;
use App\Models\User;
use App\Support\Activities\ActivityPresenter;
use Illuminate\Foundation\Testing\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class ActivityPresenterTest extends TestCase
{
    /** @return array<string, array{ActivityType, array<string, mixed>|null, string}> */
    public static function activities(): array
    {
        return [
            'created' => [ActivityType::Created, null, 'Riley Chen created the ticket'],
            'imported' => [ActivityType::Imported, null, 'Riley Chen imported the ticket'],
            'assigned' => [ActivityType::Assigned, ['from' => null, 'to' => 'Jordan Reed', 'agent_id' => 2], 'Riley Chen assigned the ticket to Jordan Reed'],
            'reassigned' => [ActivityType::Assigned, ['from' => 'Jordan Reed', 'to' => 'Sam Okafor', 'agent_id' => 3], 'Riley Chen reassigned the ticket from Jordan Reed to Sam Okafor'],
            'claimed' => [ActivityType::Claimed, ['to' => 'Riley Chen', 'agent_id' => 1], 'Riley Chen claimed the ticket'],
            'unassigned' => [ActivityType::Unassigned, ['from' => 'Jordan Reed'], 'Riley Chen unassigned Jordan Reed'],
            'status' => [ActivityType::StatusChanged, ['from' => 'in_progress', 'to' => 'resolved'], 'Riley Chen changed the status from In Progress to Resolved'],
            'priority' => [ActivityType::PriorityChanged, ['from' => 'medium', 'to' => 'high'], 'Riley Chen changed the priority from Medium to High'],
            'category' => [ActivityType::CategoryChanged, ['from' => 'General', 'to' => 'Billing'], 'Riley Chen moved the ticket from General to Billing'],
            'subject' => [ActivityType::SubjectChanged, ['from' => 'Old', 'to' => 'New subject'], 'Riley Chen changed the subject to "New subject"'],
            'reply' => [ActivityType::ReplyAdded, ['reply_id' => 9], 'Riley Chen replied'],
            'note' => [ActivityType::NoteAdded, ['reply_id' => 9], 'Riley Chen added an internal note'],
            'one attachment' => [ActivityType::AttachmentAdded, ['count' => 1, 'names' => ['jam.jpg'], 'reply_id' => null], 'Riley Chen attached 1 file: jam.jpg'],
            'many attachments' => [ActivityType::AttachmentAdded, ['count' => 2, 'names' => ['a.png', 'b.pdf'], 'reply_id' => null], 'Riley Chen attached 2 files: a.png, b.pdf'],
        ];
    }

    #[DataProvider('activities')]
    public function test_it_describes_each_activity_in_plain_english(ActivityType $type, ?array $payload, string $expected): void
    {
        $activity = $this->activity($type, $payload, new User(['name' => 'Riley Chen']));

        $this->assertSame($expected, app(ActivityPresenter::class)->describe($activity));
    }

    public function test_system_activities_have_no_actor(): void
    {
        $presenter = app(ActivityPresenter::class);

        $this->assertSame('Automatically assigned to Jordan Reed', $presenter->describe($this->activity(ActivityType::AutoAssigned, ['to' => 'Jordan Reed', 'agent_id' => 2])));
        $this->assertSame('SLA deadline is approaching', $presenter->describe($this->activity(ActivityType::SlaApproaching)));
        $this->assertSame('Ticket escalated: SLA breached', $presenter->describe($this->activity(ActivityType::Escalated)));
    }

    /** @param  array<string, mixed>|null  $payload */
    private function activity(ActivityType $type, ?array $payload = null, ?User $actor = null): TicketActivity
    {
        $activity = new TicketActivity(['type' => $type, 'payload' => $payload]);
        $activity->setRelation('actor', $actor);

        return $activity;
    }
}
