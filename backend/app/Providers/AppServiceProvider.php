<?php

namespace App\Providers;

use App\Services\ActivityRecorder;
use App\Services\Assignment\AgentAssigner;
use App\Services\Assignment\AssignmentService;
use App\Services\Assignment\LeastWorkloadAssigner;
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

        $this->app->bind(AgentAssigner::class, LeastWorkloadAssigner::class);

        $this->app->bind(AssignmentService::class, fn ($app) => new AssignmentService(
            assigner: $app->make(AgentAssigner::class),
            activities: $app->make(ActivityRecorder::class),
            autoAssignEnabled: config('tickets.auto_assign'),
        ));
    }

    public function boot(): void
    {
        JsonResource::withoutWrapping();
        Model::shouldBeStrict(! $this->app->isProduction());
    }
}
