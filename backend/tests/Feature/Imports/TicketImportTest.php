<?php

namespace Tests\Feature\Imports;

use App\Enums\ActivityType;
use App\Enums\ImportStatus;
use App\Jobs\ProcessTicketImport;
use App\Models\Category;
use App\Models\Import;
use App\Models\ImportError;
use App\Models\Ticket;
use App\Models\User;
use App\Services\Imports\TicketImporter;
use Database\Seeders\SlaPolicySeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TicketImportTest extends TestCase
{
    private User $agent;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        $this->seed(SlaPolicySeeder::class);
        Category::factory()->create(['name' => 'Hardware']);
        User::factory()->create(['email' => 'riley@example.test']);
        $this->agent = User::factory()->agent()->create();
    }

    public function test_uploading_a_csv_stores_it_and_queues_the_import(): void
    {
        Queue::fake();

        $response = $this->actingAs($this->agent)->post('/api/imports', [
            'file' => UploadedFile::fake()->createWithContent('legacy.csv', $this->csv([$this->row()])),
        ], ['Accept' => 'application/json']);

        $response->assertCreated()
            ->assertJsonPath('status', 'pending')
            ->assertJsonPath('original_name', 'legacy.csv')
            ->assertJsonPath('user.id', $this->agent->id);

        $import = Import::firstOrFail();
        Storage::disk('local')->assertExists($import->path);
        Queue::assertPushed(ProcessTicketImport::class, fn (ProcessTicketImport $job) => $job->import->is($import));
    }

    public function test_valid_invalid_and_duplicate_rows_are_each_handled_and_reported(): void
    {
        Ticket::factory()->create(['external_reference' => 'LEGACY-0001']);
        $rows = [
            $this->row(['external_reference' => 'LEGACY-0001']),
            $this->row(['external_reference' => 'LEGACY-0002', 'subject' => 'First new ticket']),
            $this->row(['external_reference' => 'LEGACY-0002', 'subject' => 'Same reference again']),
            $this->row(['priority' => 'urgent', 'category' => 'Plumbing', 'customer_email' => 'nobody@example.test']),
            $this->row(['external_reference' => '', 'subject' => 'No reference, always imported', 'created_at' => '2026-03-02 10:00:00']),
        ];
        $import = $this->storeImport($rows);

        app(TicketImporter::class)->import($import);

        $import->refresh();
        $this->assertSame(ImportStatus::Completed, $import->status);
        $this->assertSame([5, 5, 2, 2, 1], [$import->total_rows, $import->processed_rows, $import->imported_rows, $import->skipped_rows, $import->failed_rows]);
        $this->assertSame(100, $import->progress());

        $imported = Ticket::where('subject', 'First new ticket')->firstOrFail();
        $this->assertMatchesRegularExpression('/^TKT-\d{4}-\d{6}$/', $imported->ticket_number);
        $this->assertNotNull($imported->sla_due_at);
        $this->assertSame('LEGACY-0002', $imported->external_reference);
        $this->assertSame($this->agent->id, $imported->activities()->firstOrFail()->actor_id);
        $this->assertSame(ActivityType::Imported, $imported->activities()->firstOrFail()->type);
        $this->assertSame('2026-03-02 10:00:00', Ticket::where('subject', 'No reference, always imported')->firstOrFail()->created_at->format('Y-m-d H:i:s'));

        $errors = ImportError::orderBy('row_number')->get();
        $this->assertSame([2, 4, 5], $errors->pluck('row_number')->all());
        $this->assertSame(['duplicate', 'duplicate', 'invalid'], $errors->pluck('kind')->map(fn ($kind) => $kind->value)->all());
        $this->assertCount(3, $errors[2]->messages);
        $this->assertSame('urgent', $errors[2]->raw['priority']);
    }

    public function test_a_file_without_the_required_columns_fails_cleanly(): void
    {
        $import = $this->storeImport([], header: ['subject', 'description']);

        try {
            ProcessTicketImport::dispatchSync($import);
        } catch (\Throwable) {
        }

        $import->refresh();
        $this->assertSame(ImportStatus::Failed, $import->status);
        $this->assertStringContainsString('Missing required columns: priority, category, customer_email', $import->error_message);
        $this->assertDatabaseCount('tickets', 0);
    }

    public function test_a_large_file_is_imported_in_chunks_with_bounded_memory(): void
    {
        $rows = array_map(fn (int $i) => $this->row(['external_reference' => "BULK-{$i}", 'subject' => "Bulk ticket {$i}"]), range(1, 3000));
        $import = $this->storeImport($rows);
        $before = memory_get_peak_usage(true);

        app(TicketImporter::class)->import($import);

        $this->assertSame(3000, Ticket::count());
        $this->assertSame(3000, $import->refresh()->imported_rows);
        $this->assertSame(3000, Ticket::distinct('ticket_number')->count('ticket_number'));
        $this->assertLessThan(64 * 1024 * 1024, memory_get_peak_usage(true) - $before);
    }

    public function test_imports_are_listed_and_inspected_by_their_owner_or_an_administrator(): void
    {
        $mine = Import::factory()->completed(imported: 3, failed: 1)->create(['user_id' => $this->agent->id]);
        $theirs = Import::factory()->completed()->create();
        ImportError::create(['import_id' => $mine->id, 'row_number' => 4, 'kind' => 'invalid', 'messages' => ['Bad priority'], 'raw' => ['priority' => 'x'], 'created_at' => now()]);

        $this->actingAs($this->agent)->getJson('/api/imports')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $mine->id);
        $this->actingAs($this->agent)->getJson("/api/imports/{$mine->id}")->assertOk()->assertJsonPath('progress', 100)->assertJsonPath('failed_rows', 1);
        $this->actingAs($this->agent)->getJson("/api/imports/{$mine->id}/errors")->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.row_number', 4);
        $this->actingAs($this->agent)->getJson("/api/imports/{$theirs->id}")->assertForbidden();
        $this->actingAs(User::factory()->administrator()->create())->getJson('/api/imports')->assertJsonCount(2, 'data');
    }

    public function test_customers_cannot_import_or_see_imports(): void
    {
        $customer = User::factory()->create();

        $this->actingAs($customer)->getJson('/api/imports')->assertForbidden();
        $this->actingAs($customer)->getJson('/api/imports/template')->assertForbidden();
        $this->actingAs($customer)->post('/api/imports', ['file' => UploadedFile::fake()->create('x.csv', 1, 'text/csv')], ['Accept' => 'application/json'])->assertForbidden();
    }

    public function test_only_csv_files_within_the_size_limit_are_accepted(): void
    {
        $this->actingAs($this->agent)->post('/api/imports', [
            'file' => UploadedFile::fake()->create('tickets.xlsx', 10, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'),
        ], ['Accept' => 'application/json'])->assertUnprocessable()->assertJsonValidationErrors('file');

        $this->actingAs($this->agent)->post('/api/imports', [
            'file' => UploadedFile::fake()->create('huge.csv', 30000, 'text/csv'),
        ], ['Accept' => 'application/json'])->assertUnprocessable()->assertJsonValidationErrors('file');
    }

    public function test_the_template_lists_every_column(): void
    {
        $response = $this->actingAs($this->agent)->get('/api/imports/template')->assertOk()->assertDownload('ticket-import-template.csv');

        $this->assertStringStartsWith('external_reference,created_at,subject,description,priority,category,customer_email', $response->streamedContent());
    }

    /** @param  array<string, string>  $overrides */
    private function row(array $overrides = []): array
    {
        return array_merge([
            'external_reference' => '',
            'created_at' => '',
            'subject' => 'Imported subject',
            'description' => 'Imported description',
            'priority' => 'medium',
            'category' => 'Hardware',
            'customer_email' => 'riley@example.test',
        ], $overrides);
    }

    /**
     * @param  list<array<string, string>>  $rows
     * @param  list<string>|null  $header
     */
    private function csv(array $rows, ?array $header = null): string
    {
        $header ??= ['external_reference', 'created_at', 'subject', 'description', 'priority', 'category', 'customer_email'];
        $lines = [implode(',', $header)];

        foreach ($rows as $row) {
            $lines[] = implode(',', array_map(fn (string $value) => '"'.str_replace('"', '""', $value).'"', array_values(array_intersect_key($row, array_flip($header)))));
        }

        return implode("\n", $lines)."\n";
    }

    /**
     * @param  list<array<string, string>>  $rows
     * @param  list<string>|null  $header
     */
    private function storeImport(array $rows, ?array $header = null): Import
    {
        Storage::disk('local')->put('imports/test.csv', $this->csv($rows, $header));

        return Import::factory()->create(['user_id' => $this->agent->id, 'path' => 'imports/test.csv']);
    }
}
