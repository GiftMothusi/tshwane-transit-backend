<?php

namespace App\Http\Controllers;

use App\Models\BusRoute;
use App\Models\BusSchedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BusScheduleController extends Controller
{
    public function index(Request $request)
    {
        try {
            $routeId = $request->query('route_id');
            $dayType = $request->query('day_type', date('N') >= 6 ? (date('N') == 6 ? 'saturday' : 'sunday') : 'weekday');

            $schedules = BusSchedule::with('route')
                ->when($routeId, fn($query) => $query->where('route_id', $routeId))
                ->where('day_type', $dayType)
                ->where('is_active', true)
                ->orderBy('departure_time')
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => $schedules
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching schedules: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch schedules'
            ], 500);
        }
    }

    public function getLiveLocations(Request $request)
    {
        try {
            // Simulate live bus locations for active schedules
            $busLocations = BusSchedule::with('route')
                ->where('is_active', true)
                ->get()
                ->map(function ($schedule) {
                    // Generate random coordinates around Tshwane
                    $baseLat = -25.7479;
                    $baseLng = 28.2293;

                    return [
                        'id' => $schedule->id,
                        'bus_number' => $schedule->bus_number,
                        'route_number' => $schedule->route->route_number,
                        'location' => [
                            'latitude' => $baseLat + (rand(-1000, 1000) / 10000),
                            'longitude' => $baseLng + (rand(-1000, 1000) / 10000),
                        ],
                        'heading' => rand(0, 359),
                        'speed' => rand(20, 60),
                        'next_stop' => $schedule->route->stops[rand(0, count($schedule->route->stops) - 1)],
                    ];
                });

            return response()->json([
                'status' => 'success',
                'data' => $busLocations
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching live locations: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch live locations'
            ], 500);
        }
    }
}
