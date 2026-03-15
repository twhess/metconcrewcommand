<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ReportController extends Controller
{
    protected ReportService $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    public function availableResources(string $date): JsonResponse
    {
        if (!auth()->user()->hasPermission('reports.view')) {
            abort(403);
        }

        $data = $this->reportService->getAvailableResources($date);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    public function equipmentLocations(): JsonResponse
    {
        if (!auth()->user()->hasPermission('reports.view')) {
            abort(403);
        }

        $data = $this->reportService->getEquipmentLocationReport();

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    public function inventoryStatus(): JsonResponse
    {
        if (!auth()->user()->hasPermission('reports.view')) {
            abort(403);
        }

        $data = $this->reportService->getInventoryStatusReport();

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    public function dailySchedule(string $date): JsonResponse
    {
        if (!auth()->user()->hasPermission('reports.view')) {
            abort(403);
        }

        $data = $this->reportService->getDailyScheduleSummary($date);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }
}
