<?php

namespace App\Http\Controllers;

use App\Models\BusRoute;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class BusRouteController extends Controller
{
    /**
     * Get all active bus routes with their current schedules.
     * Optionally filter by route number or search by name.
     */
    public function index(Request $request)
    {
        try {
            $query = BusRoute::with(['schedules' => function ($query) {
                $query->where('is_active', true)
                    ->orderBy('departure_time');
            }]);

            // Apply filters if provided
            if ($request->has('route_number')) {
                $query->where('route_number', $request->route_number);
            }

            if ($request->has('search')) {
                $query->where('name', 'like', '%' . $request->search . '%');
            }

            $routes = $query->get();

            return response()->json([
                'status' => 'success',
                'data' => $routes
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching bus routes: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch bus routes'
            ], 500);
        }
    }

    /**
     * Get detailed information about a specific route,
     * including all stops and current schedule.
     */
    public function show(string $id)
    {
        try {
            $route = BusRoute::with(['schedules' => function ($query) {
                $query->where('is_active', true)
                    ->orderBy('departure_time');
            }])->findOrFail($id);

            return response()->json([
                'status' => 'success',
                'data' => $route
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching bus route: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Route not found'
            ], 404);
        }
    }

    /**
     * Create a new bus route with initial schedule.
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'route_number' => 'required|string|unique:bus_routes',
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'stops' => 'required|array|min:2',
                'stops.*.name' => 'required|string',
                'stops.*.coordinates' => 'required|array',
                'stops.*.coordinates.latitude' => 'required|numeric',
                'stops.*.coordinates.longitude' => 'required|numeric',
                'fare' => 'required|numeric|min:0',
                'is_express' => 'boolean',
                'estimated_duration' => 'required|integer|min:1'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $route = BusRoute::create($request->all());

            Log::info('New bus route created', [
                'route_id' => $route->id,
                'route_number' => $route->route_number
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Bus route created successfully',
                'data' => $route
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error creating bus route: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create bus route'
            ], 500);
        }
    }

    /**
     * Update an existing bus route information.
     */
    public function update(Request $request, string $id)
    {
        try {
            $route = BusRoute::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'route_number' => 'string|unique:bus_routes,route_number,' . $id,
                'name' => 'string|max:255',
                'description' => 'nullable|string',
                'stops' => 'array|min:2',
                'stops.*.name' => 'required|string',
                'stops.*.coordinates' => 'required|array',
                'stops.*.coordinates.latitude' => 'required|numeric',
                'stops.*.coordinates.longitude' => 'required|numeric',
                'fare' => 'numeric|min:0',
                'is_express' => 'boolean',
                'estimated_duration' => 'integer|min:1'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $route->update($request->all());

            Log::info('Bus route updated', [
                'route_id' => $route->id,
                'route_number' => $route->route_number
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Bus route updated successfully',
                'data' => $route
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating bus route: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update bus route'
            ], 500);
        }
    }

    /**
     * Remove a bus route and all associated schedules.
     */
    public function destroy(string $id)
    {
        try {
            $route = BusRoute::findOrFail($id);

            // This will also delete associated schedules due to cascade
            $route->delete();

            Log::info('Bus route deleted', [
                'route_id' => $id,
                'route_number' => $route->route_number
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Bus route deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting bus route: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete bus route'
            ], 500);
        }
    }

    public function getNearbyStops(Request $request)
    {
        try {
            $latitude = $request->query('latitude');
            $longitude = $request->query('longitude');
            $radius = $request->query('radius', 2); // Default 2km radius

            $routes = BusRoute::all();
            $nearbyStops = [];

            foreach ($routes as $route) {
                foreach ($route->stops as $stop) {
                    $distance = $this->calculateDistance(
                        $latitude,
                        $longitude,
                        $stop['coordinates']['latitude'],
                        $stop['coordinates']['longitude']
                    );

                    if ($distance <= $radius) {
                        $stop['distance'] = $distance;
                        $stop['route_numbers'] = [$route->route_number];
                        $nearbyStops[] = $stop;
                    }
                }
            }

            // Group stops by location and combine route numbers
            $groupedStops = collect($nearbyStops)->groupBy('name')
                ->map(function ($group) {
                    $first = $group->first();
                    $first['route_numbers'] = $group->pluck('route_numbers')->flatten()->unique()->values();
                    return $first;
                })->values();

            return response()->json([
                'status' => 'success',
                'data' => $groupedStops
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching nearby stops: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch nearby stops'
            ], 500);
        }
    }

    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $R = 6371; // Earth's radius in km
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat/2) * sin($dLat/2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon/2) * sin($dLon/2);
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        return $R * $c;
    }
}
