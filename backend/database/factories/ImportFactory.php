<?php

namespace Database\Factories;

use App\Enums\ImportStatus;
use App\Models\Import;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Import> */
class ImportFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->agent(),
            'original_name' => 'tickets.csv',
            'disk' => 'local',
            'path' => 'imports/'.fake()->uuid().'.csv',
            'status' => ImportStatus::Pending,
        ];
    }

    public function completed(int $imported = 10, int $skipped = 0, int $failed = 0): static
    {
        $total = $imported + $skipped + $failed;

        return $this->state(fn () => [
            'status' => ImportStatus::Completed,
            'total_rows' => $total,
            'processed_rows' => $total,
            'imported_rows' => $imported,
            'skipped_rows' => $skipped,
            'failed_rows' => $failed,
            'started_at' => now()->subMinute(),
            'finished_at' => now(),
        ]);
    }
}
