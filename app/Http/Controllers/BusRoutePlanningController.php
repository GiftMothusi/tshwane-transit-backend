<?php

namespace App\Http\Controllers;

use App\Services\BusRoutePlanningService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class BusRoutePlanningController extends Controller
{
    protected $routePlanningService;

    public function __construct(BusRoutePlanningService $routePlanningService)
    {
        $this->routePlanningService = $routePlanningService;
    }

    public function planRoute(Request $request)
    {
        Log::info('Route planning request received', [
            'origin' => $request->input('origin'),
            'destination' => $request->input('destination')
        ]);

        $validator = Validator::make($request->all(), [
            'origin.latitude' => 'required|numeric|between:-90,90',
            'origin.longitude' => 'required|numeric|between:-180,180',
            'destination.latitude' => 'required|numeric|between:-90,90',
            'destination.longitude' => 'required|numeric|between:-180,180',
            'radius' => 'sometimes|numeric|min:0.1|max:10'
        ]);

        if ($validator->fails()) {
            Log::warning('Route planning validation failed', ['errors' => $validator->errors()]);
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid coordinates provided',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $routes = $this->routePlanningService->findRoutes(
                $request->input('origin'),
                $request->input('destination'),
                $request->input('radius', 2)
            );

            Log::info('Routes found', ['count' => $routes->count()]);

            if ($routes->isEmpty()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'No routes found for the given locations',
                    'routes' => [],
                    'total_routes_found' => 0
                ]);
            }

            return response()->json([
                'status' => 'success',
                'routes' => $routes->values(),
                'total_routes_found' => $routes->count()
            ]);
        } catch (\Exception $e) {
            Log::error('Route planning error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to plan route',
                'error' => config('app.debug') ? $e->getMessage() : 'An unexpected error occurred'
            ], 500);
        }
    }
}
