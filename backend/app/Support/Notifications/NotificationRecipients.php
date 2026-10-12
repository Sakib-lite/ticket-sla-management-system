<?php

namespace App\Support\Notifications;

use App\Enums\Role;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

final class NotificationRecipients
{
    /** @return Collection<int, User> */
    public function admins(): Collection
    {
        return User::query()->where('role', Role::Administrator)->get();
    }

    /** @return Collection<int, User> */
    public function staffFor(Ticket $ticket): Collection
    {
        return $ticket->assignee ? new Collection([$ticket->assignee]) : $this->admins();
    }

    /** @return Collection<int, User> */
    public function escalationFor(Ticket $ticket): Collection
    {
        $recipients = $this->admins();

        if ($ticket->assignee) {
            $recipients->push($ticket->assignee);
        }

        return $recipients->unique('id')->values();
    }

    /**
     * @param  Collection<int, User>  $recipients
     * @return Collection<int, User>
     */
    public function without(Collection $recipients, ?User $actor): Collection
    {
        return $recipients->reject(fn (User $user) => $actor?->is($user))->values();
    }
}
