<?php

namespace App\Support\Sla;

use App\Enums\TicketPriority;
use App\Models\SlaPolicy;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

final class SlaPolicies
{
    private const CACHE_KEY = 'sla-policies';

    public function forPriority(TicketPriority $priority): SlaRule
    {
        return $this->all()->get($priority->value) ?? throw new MissingSlaPolicyException($priority);
    }

    /** @return Collection<string, SlaRule> */
    public function all(): Collection
    {
        $rules = Cache::rememberForever(
            self::CACHE_KEY,
            fn () => SlaPolicy::all()->map(fn (SlaPolicy $policy) => SlaRule::fromPolicy($policy)->toArray())->all(),
        );

        return collect($rules)->map(SlaRule::fromArray(...))->keyBy(fn (SlaRule $rule) => $rule->priority->value);
    }

    public function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
