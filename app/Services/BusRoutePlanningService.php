<?php

namespace App\Services;

use App\Models\BusRoute;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class BusRoutePlanningService
{
    public function findRoutes(array $origin, array $destination, float $radius = 2): Collection
    {
        try {
            Log::info('Finding routes', compact('origin', 'destination', 'radius'));

            $routes = BusRoute::all()->filter(function ($route) use ($origin, $destination, $radius) {
                $originStops = $this->findNearbyStops($route->stops, $origin, $radius);
                $destinationStops = $this->findNearbyStops($route->stops, $destination, $radius);
                return !empty($originStops) && !empty($destinationStops);
            });

            Log::info('Found initial routes', ['count' => $routes->count()]);

            $mappedRoutes = $routes->map(function ($route) use ($origin, $destination) {
                Log::info('Processing route', ['route_id' => $route->id]);
                return $this->calculateRouteDetails($route, $origin, $destination);
            });

            $sortedRoutes = $mappedRoutes->sortBy('totalDistance');

            Log::info('Routes processed', ['total' => $sortedRoutes->count()]);

            return $sortedRoutes;
        } catch (\Exception $e) {
            Log::error('Route finding error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    private function findNearbyStops(array $routeStops, array $coordinates, float $radius): array
    {
        return array_filter($routeStops, function ($stop) use ($coordinates, $radius) {
            $distance = $this->calculateHaversineDistance(
                $coordinates['latitude'], $coordinates['longitude'],
                $stop['coordinates']['latitude'], $stop['coordinates']['longitude']
            );
            return $distance <= $radius;
        });
    }

    private function calculateRouteDetails(BusRoute $route, array $origin, array $destination): array
    {
        $totalDistance = $this->calculateTotalRouteDistance($route->stops, $origin, $destination);

        return [
            'routeId' => $route->id,
            'routeNumber' => $route->route_number,
            'name' => $route->name,
            'isExpress' => $route->is_express,
            'totalDistance' => round($totalDistance, 2),
            'estimatedDuration' => $this->calculateEstimatedTime($totalDistance),
            'fare' => $this->calculateFare($route, $totalDistance),
            'stops' => array_map(function($stop) {
                return ['name' => $stop['name'], 'coordinates' => $stop['coordinates']];
            }, $route->stops)
        ];
    }

    private function calculateTotalRouteDistance(array $routeStops, array $origin, array $destination): float
    {
        $total = 0;
        $total += $this->calculateHaversineDistance($origin['latitude'], $origin['longitude'], $routeStops[0]['coordinates']['latitude'], $routeStops[0]['coordinates']['longitude']);

        for ($i = 0; $i < count($routeStops) - 1; $i++) {
            $total += $this->calculateHaversineDistance($routeStops[$i]['coordinates']['latitude'], $routeStops[$i]['coordinates']['longitude'], $routeStops[$i + 1]['coordinates']['latitude'], $routeStops[$i + 1]['coordinates']['longitude']);
        }

        $total += $this->calculateHaversineDistance(end($routeStops)['coordinates']['latitude'], end($routeStops)['coordinates']['longitude'], $destination['latitude'], $destination['longitude']);
        return $total;
    }

    private function calculateHaversineDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $R = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon/2) * sin($dLon/2);
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        return $R * $c;
    }

    private function calculateEstimatedTime(float $distance): int
    {
        $speed = 30;
        return round(($distance / $speed) * 60);
    }

    private function calculateFare(BusRoute $route, float $distance): float
    {
        $base = 10.00;
        $perKm = 1.50;
        $express = $route->is_express ? 5.00 : 0.00;
        return round($base + ($distance * $perKm) + $express, 2);
    }
}
