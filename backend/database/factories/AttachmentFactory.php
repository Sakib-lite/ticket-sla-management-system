<?php

namespace Database\Factories;

use App\Models\Attachment;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Attachment>
 */
class AttachmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'attachable_type' => Ticket::class,
            'attachable_id' => Ticket::factory(),
            'uploaded_by' => User::factory(),
            'disk' => 'local',
            'path' => 'attachments/'.Str::random(40).'.png',
            'original_name' => fake()->word().'.png',
            'mime_type' => 'image/png',
            'size' => fake()->numberBetween(1_000, 2_000_000),
        ];
    }
}
