<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\Dashboard\DashboardMetrics;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(private readonly DashboardMetrics $metrics) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json($this->metrics->for($request->user()));
    }
}
