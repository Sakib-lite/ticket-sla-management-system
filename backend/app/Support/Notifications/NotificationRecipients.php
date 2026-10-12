<?php

namespace App\Support\Notifications;

use App\Enums\Role;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;

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
     * @param  SupportCollection<int, User>  $recipients
     * @return SupportCollection<int, User>
     */
    public function without(SupportCollection $recipients, ?User $actor): SupportCollection
    {
        return $recipients->reject(fn (User $user) => $actor?->is($user))->values();
    }
}
