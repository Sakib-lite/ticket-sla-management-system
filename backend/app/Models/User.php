<?php

namespace App\Models;

use App\Enums\Role;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password', 'role', 'is_available', 'last_assigned_at'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected $attributes = [
        'is_available' => true,
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => Role::class,
            'is_available' => 'boolean',
            'last_assigned_at' => 'datetime',
        ];
    }

    /** @return HasMany<Ticket, $this> */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'created_by');
    }

    /** @return HasMany<Ticket, $this> */
    public function assignedTickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'assigned_agent_id');
    }

    public function isAdministrator(): bool
    {
        return $this->role === Role::Administrator;
    }

    public function isAgent(): bool
    {
        return $this->role === Role::Agent;
    }

    public function isCustomer(): bool
    {
        return $this->role === Role::Customer;
    }

    public function isStaff(): bool
    {
        return ! $this->isCustomer();
    }

    /** @param  Builder<User>  $query */
    public function scopeAgents(Builder $query): void
    {
        $query->where('role', Role::Agent);
    }

    /** @param  Builder<User>  $query */
    public function scopeAvailable(Builder $query): void
    {
        $query->where('is_available', true);
    }
}
