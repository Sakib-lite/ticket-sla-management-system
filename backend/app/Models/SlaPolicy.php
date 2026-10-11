<?php

namespace App\Models;

use App\Enums\TicketPriority;
use Database\Factories\SlaPolicyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['priority', 'resolution_hours', 'approaching_threshold_percent'])]
class SlaPolicy extends Model
{
    /** @use HasFactory<SlaPolicyFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'priority' => TicketPriority::class,
            'resolution_hours' => 'integer',
            'approaching_threshold_percent' => 'integer',
        ];
    }
}
