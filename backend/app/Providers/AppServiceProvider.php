<?php

namespace App\Providers;

use App\Support\BusinessHours\BusinessSchedule;
use App\Support\Tickets\TicketNumberGenerator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(BusinessSchedule::class, fn () => BusinessSchedule::fromConfig(config('business_hours')));

        $this->app->singleton(TicketNumberGenerator::class, fn () => new TicketNumberGenerator(
            prefix: config('tickets.number_prefix'),
            minimumDigits: config('tickets.number_minimum_digits'),
        ));
    }

    public function boot(): void
    {
        JsonResource::withoutWrapping();
        Model::shouldBeStrict(! $this->app->isProduction());
    }
}
