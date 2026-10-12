<?php

namespace App\Policies;

use App\Models\Import;
use App\Models\User;

class ImportPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isStaff();
    }

    public function view(User $user, Import $import): bool
    {
        return $user->isAdministrator() || $import->isOwnedBy($user);
    }

    public function create(User $user): bool
    {
        return $user->isStaff();
    }
}
