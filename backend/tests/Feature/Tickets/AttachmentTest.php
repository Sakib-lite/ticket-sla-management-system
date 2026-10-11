<?php

namespace Tests\Feature\Tickets;

use App\Enums\ActivityType;
use App\Models\Attachment;
use App\Models\Category;
use App\Models\Ticket;
use App\Models\TicketReply;
use App\Models\User;
use Database\Seeders\SlaPolicySeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AttachmentTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    public function test_a_customer_attaches_files_to_their_ticket(): void
    {
        $customer = User::factory()->create();
        $ticket = Ticket::factory()->createdBy($customer)->create();

        $response = $this->actingAs($customer)->postJson("/api/tickets/{$ticket->id}/attachments", [
            'files' => [
                UploadedFile::fake()->image('screenshot.png'),
                UploadedFile::fake()->create('log.txt', 12, 'text/plain'),
            ],
        ]);

        $response->assertCreated()->assertJsonCount(2);
        $this->assertSame(['screenshot.png', 'log.txt'], array_column($response->json(), 'original_name'));

        $attachment = Attachment::firstOrFail();
        Storage::disk('local')->assertExists($attachment->path);
        $this->assertStringStartsWith("attachments/tickets/{$ticket->id}/", $attachment->path);
        $this->assertStringNotContainsString('screenshot', $attachment->path);
        $this->assertDatabaseHas('ticket_activities', [
            'ticket_id' => $ticket->id,
            'type' => ActivityType::AttachmentAdded->value,
            'actor_id' => $customer->id,
        ]);
    }

    public function test_file_type_size_and_count_limits_are_enforced(): void
    {
        $agent = User::factory()->agent()->create();
        $ticket = Ticket::factory()->create();

        $this->actingAs($agent)->postJson("/api/tickets/{$ticket->id}/attachments", [
            'files' => [UploadedFile::fake()->create('virus.exe', 10, 'application/octet-stream')],
        ])->assertUnprocessable()->assertJsonValidationErrors('files.0');

        $this->actingAs($agent)->postJson("/api/tickets/{$ticket->id}/attachments", [
            'files' => [UploadedFile::fake()->create('huge.pdf', 6000, 'application/pdf')],
        ])->assertUnprocessable()->assertJsonValidationErrors('files.0');

        $this->actingAs($agent)->postJson("/api/tickets/{$ticket->id}/attachments", [
            'files' => array_map(fn (int $i) => UploadedFile::fake()->image("{$i}.png"), range(1, 6)),
        ])->assertUnprocessable()->assertJsonValidationErrors('files');

        $this->assertDatabaseCount('attachments', 0);
    }

    public function test_downloads_are_authorised_through_the_ticket(): void
    {
        $customer = User::factory()->create();
        $stranger = User::factory()->create();
        $ticket = Ticket::factory()->createdBy($customer)->create();
        $attachment = Attachment::factory()->for($ticket, 'attachable')->create([
            'uploaded_by' => $customer->id,
            'original_name' => 'invoice.pdf',
            'path' => 'attachments/invoice.pdf',
        ]);
        Storage::disk('local')->put($attachment->path, 'pdf-bytes');

        $this->get("/api/attachments/{$attachment->id}/download")->assertUnauthorized();
        $this->actingAs($stranger)->get("/api/attachments/{$attachment->id}/download")->assertForbidden();
        $this->actingAs(User::factory()->agent()->create())->get("/api/attachments/{$attachment->id}/download")->assertOk();
        $this->actingAs($customer)->get("/api/attachments/{$attachment->id}/download")
            ->assertOk()
            ->assertDownload('invoice.pdf');
    }

    public function test_attachments_on_internal_notes_are_hidden_from_customers(): void
    {
        $customer = User::factory()->create();
        $agent = User::factory()->agent()->create();
        $ticket = Ticket::factory()->createdBy($customer)->create();

        $reply = $this->actingAs($agent)->post("/api/tickets/{$ticket->id}/replies", [
            'body' => 'Internal evidence attached.',
            'is_internal' => true,
            'files' => [UploadedFile::fake()->image('evidence.png')],
        ], ['Accept' => 'application/json'])->assertCreated();

        $attachmentId = $reply->json('attachments.0.id');
        $this->assertNotNull($attachmentId);
        $this->assertSame(TicketReply::class, Attachment::findOrFail($attachmentId)->attachable_type);

        $this->actingAs($customer)->get("/api/attachments/{$attachmentId}/download")->assertForbidden();
        $this->actingAs($agent)->get("/api/attachments/{$attachmentId}/download")->assertOk();
    }

    public function test_a_ticket_can_be_created_with_attachments(): void
    {
        $this->seed(SlaPolicySeeder::class);
        $customer = User::factory()->create();

        $response = $this->actingAs($customer)->post('/api/tickets', [
            'subject' => 'Printer jammed',
            'description' => 'See photo.',
            'priority' => 'medium',
            'category_id' => Category::factory()->create()->id,
            'files' => [UploadedFile::fake()->image('jam.jpg')],
        ], ['Accept' => 'application/json']);

        $response->assertCreated()->assertJsonCount(1, 'attachments')->assertJsonPath('attachments.0.original_name', 'jam.jpg');
    }

    public function test_the_uploader_or_an_administrator_can_delete_an_attachment(): void
    {
        $customer = User::factory()->create();
        $ticket = Ticket::factory()->createdBy($customer)->create();
        $mine = $this->upload($ticket, $customer, 'mine.pdf');
        $theirs = $this->upload($ticket, User::factory()->agent()->create(), 'theirs.pdf');

        $this->actingAs($customer)->deleteJson("/api/attachments/{$theirs->id}")->assertForbidden();
        $this->actingAs($customer)->deleteJson("/api/attachments/{$mine->id}")->assertNoContent();
        $this->actingAs(User::factory()->administrator()->create())->deleteJson("/api/attachments/{$theirs->id}")->assertNoContent();

        Storage::disk('local')->assertMissing($mine->path);
        $this->assertDatabaseCount('attachments', 0);
    }

    public function test_attachments_cannot_be_removed_from_finished_tickets(): void
    {
        $customer = User::factory()->create();
        $ticket = Ticket::factory()->createdBy($customer)->resolved()->create();
        $attachment = Attachment::factory()->for($ticket, 'attachable')->create(['uploaded_by' => $customer->id]);

        $this->actingAs($customer)->deleteJson("/api/attachments/{$attachment->id}")->assertForbidden();
    }

    public function test_ticket_and_reply_payloads_include_their_attachments(): void
    {
        $agent = User::factory()->agent()->create();
        $ticket = Ticket::factory()->create();
        $this->upload($ticket, $agent, 'ticket-level.pdf');
        $reply = TicketReply::factory()->for($ticket)->by($agent)->create();
        Attachment::factory()->for($reply, 'attachable')->create(['original_name' => 'reply-level.png', 'uploaded_by' => $agent->id]);

        $this->actingAs($agent)->getJson("/api/tickets/{$ticket->id}")
            ->assertJsonPath('attachments.0.original_name', 'ticket-level.pdf');
        $this->actingAs($agent)->getJson("/api/tickets/{$ticket->id}/replies")
            ->assertJsonPath('0.attachments.0.original_name', 'reply-level.png');
    }

    private function upload(Ticket $ticket, User $uploader, string $name): Attachment
    {
        $file = str_ends_with($name, '.png') || str_ends_with($name, '.jpg')
            ? UploadedFile::fake()->image($name)
            : UploadedFile::fake()->create($name, 20, 'application/pdf');

        $id = $this->actingAs($uploader)
            ->postJson("/api/tickets/{$ticket->id}/attachments", ['files' => [$file]])
            ->assertCreated()
            ->json('0.id');

        return Attachment::findOrFail($id);
    }
}
