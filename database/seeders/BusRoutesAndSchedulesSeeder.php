<?php

namespace Database\Seeders;

use App\Models\BusRoute;
use App\Models\BusSchedule;
use Illuminate\Database\Seeder;

class BusRoutesAndSchedulesSeeder extends Seeder
{
    public function run(): void
    {
        // Define common bus stops in Tshwane
        $commonStops = [
            ['name' => 'Pretoria Station', 'coordinates' => ['latitude' => -25.7544, 'longitude' => 28.1917]],
            ['name' => 'Church Square', 'coordinates' => ['latitude' => -25.7459, 'longitude' => 28.1879]],
            ['name' => 'Hatfield', 'coordinates' => ['latitude' => -25.7487, 'longitude' => 28.2396]],
            ['name' => 'Menlyn Mall', 'coordinates' => ['latitude' => -25.7827, 'longitude' => 28.2767]],
            ['name' => 'Centurion Mall', 'coordinates' => ['latitude' => -25.8614, 'longitude' => 28.1891]],
            ['name' => 'Soshanguve', 'coordinates' => ['latitude' => -25.5276, 'longitude' => 28.0982]],
            ['name' => 'Mamelodi', 'coordinates' => ['latitude' => -25.7271, 'longitude' => 28.3659]],
            ['name' => 'Wonder Park', 'coordinates' => ['latitude' => -25.6871, 'longitude' => 28.1831]],
        ];

        // Create main routes
        $routes = [
            [
                'route_number' => 'A1',
                'name' => 'Pretoria Central - Hatfield',
                'description' => 'Main route connecting CBD to Hatfield',
                'stops' => array_slice($commonStops, 0, 3),
                'fare' => 18.50,
                'is_express' => false,
                'estimated_duration' => 25,
            ],
            [
                'route_number' => 'B2',
                'name' => 'Pretoria - Centurion Express',
                'description' => 'Express service to Centurion',
                'stops' => [$commonStops[0], $commonStops[4]],
                'fare' => 25.00,
                'is_express' => true,
                'estimated_duration' => 30,
            ],
            [
                'route_number' => 'C3',
                'name' => 'Hatfield - Menlyn',
                'description' => 'Eastern route via main shopping areas',
                'stops' => array_slice($commonStops, 2, 2),
                'fare' => 15.00,
                'is_express' => false,
                'estimated_duration' => 20,
            ],
            [
                'route_number' => 'D4',
                'name' => 'Pretoria - Soshanguve',
                'description' => 'Northern suburbs connector',
                'stops' => [$commonStops[0], $commonStops[5]],
                'fare' => 22.50,
                'is_express' => false,
                'estimated_duration' => 45,
            ],
            [
                'route_number' => 'E5',
                'name' => 'CBD - Mamelodi Express',
                'description' => 'Eastern townships express service',
                'stops' => [$commonStops[0], $commonStops[6]],
                'fare' => 20.00,
                'is_express' => true,
                'estimated_duration' => 35,
            ],
        ];

        foreach ($routes as $routeData) {
            $route = BusRoute::create($routeData);

            // Create weekday schedules
            $weekdayTimes = ['06:00', '07:00', '08:00', '09:00', '12:00', '16:00', '17:00', '18:00'];
            foreach ($weekdayTimes as $index => $time) {
                BusSchedule::create([
                    'route_id' => $route->id,
                    'departure_time' => $time,
                    'day_type' => 'weekday',
                    'is_active' => true,
                    'bus_number' => $route->route_number . sprintf('%02d', $index + 1),
                    'capacity' => $route->is_express ? 45 : 60,
                    'current_location' => [
                        'latitude' => $route->stops[0]['coordinates']['latitude'],
                        'longitude' => $route->stops[0]['coordinates']['longitude'],
                    ],
                ]);
            }

            // Create weekend schedules
            $weekendTimes = ['08:00', '10:00', '12:00', '14:00', '16:00', '18:00'];
            foreach ($weekendTimes as $index => $time) {
                foreach (['saturday', 'sunday'] as $day) {
                    BusSchedule::create([
                        'route_id' => $route->id,
                        'departure_time' => $time,
                        'day_type' => $day,
                        'is_active' => true,
                        'bus_number' => $route->route_number . 'W' . sprintf('%02d', $index + 1),
                        'capacity' => $route->is_express ? 45 : 60,
                        'current_location' => [
                            'latitude' => $route->stops[0]['coordinates']['latitude'],
                            'longitude' => $route->stops[0]['coordinates']['longitude'],
                        ],
                    ]);
                }
            }
        }
    }
}
