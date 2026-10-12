<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\BusinessHours\BusinessSchedule;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;

class BusinessHoursController extends Controller
{
    public function show(BusinessSchedule $schedule): JsonResponse
    {
        return response()->json([
            'timezone' => $schedule->timezone,
            'opens_at' => $schedule->opensAt,
            'closes_at' => $schedule->closesAt,
            'working_days' => array_map(
                fn (int $day) => ['day' => $day, 'label' => CarbonImmutable::now()->startOfWeek(0)->addDays($day)->format('l')],
                $schedule->workingDays,
            ),
        ]);
    }
}
