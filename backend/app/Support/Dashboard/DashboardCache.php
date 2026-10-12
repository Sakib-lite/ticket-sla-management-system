<?php

namespace App\Support\Dashboard;

use App\Models\User;
use Illuminate\Support\Facades\Cache;

final class DashboardCache
{
    private const VERSION_KEY = 'dashboard:version';

    public function key(User $user): string
    {
        $scope = $user->isCustomer() ? "customer:{$user->id}" : 'staff';

        return "dashboard:{$this->version()}:{$scope}";
    }

    public function bump(): void
    {
        Cache::increment(self::VERSION_KEY);
    }

    private function version(): int
    {
        return (int) Cache::get(self::VERSION_KEY, 0);
    }
}
