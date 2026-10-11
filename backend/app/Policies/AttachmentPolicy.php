<?php

namespace App\Policies;

use App\Models\Attachment;
use App\Models\User;

class AttachmentPolicy
{
    public function view(User $user, Attachment $attachment): bool
    {
        if ($attachment->isInternal() && ! $user->isStaff()) {
            return false;
        }

        return $user->can('view', $attachment->ticket());
    }

    public function delete(User $user, Attachment $attachment): bool
    {
        if (! $attachment->ticket()->status->isActive()) {
            return false;
        }

        return $user->isAdministrator() || $attachment->uploaded_by === $user->id;
    }
}
