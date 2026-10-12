<?php

namespace App\Models;

use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

#[Fillable(['name', 'slug', 'is_active'])]
class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory;

    public const ACTIVE_CACHE_KEY = 'categories:active';

    protected static function booted(): void
    {
        $flushCache = fn () => Cache::forget(self::ACTIVE_CACHE_KEY);

        static::saved($flushCache);
        static::deleted($flushCache);
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /** @return Collection<int, self> */
    public static function activeCached(): Collection
    {
        $rows = Cache::rememberForever(
            self::ACTIVE_CACHE_KEY,
            fn () => self::query()->active()->orderBy('name')->get()->map->getAttributes()->all(),
        );

        return (new self)->newCollection(array_map(
            fn (array $attributes) => (new self)->newFromBuilder($attributes),
            $rows,
        ));
    }

    /** @return HasMany<Ticket, $this> */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    /** @param  Builder<Category>  $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }
}
