<?php

namespace GeoRoute;

/**
 * Distance
 *
 * @package GeoRoute
 *
 */
class Distance
{
    private $unit = "miles";

    /**
     * Add custom units here
     */
    private $earthRadius = [
        'miles' => 3959,
    ];

    /**
     * Get the radius of Earth in our preferred unit for the Haversine formula
     */
    private function getRadius(): int
    {
        return $this->earthRadius[$this->unit];
    } 

    /**
     * To change the units of measurement if you wish to do so
     */
    public function setUnits(string $unit)
    {
        $this->$unit = $unit;
    }

    /**
     * Haversine Formula for measuring great circle distance between two coordinates on a sphere
     *
     * @param float $latFrom
     * @param float $lonFrom
     * @param float $latTo
     * @param float $lonTo
     *
     * @return float
     *
     * @see https://en.wikipedia.org/wiki/Haversine_formula
     */
    public function distanceBetweenCoordinates(float $latFrom, float $lonFrom, float $latTo, float $lonTo): float
    {
        $latFrom = deg2rad($latFrom);
        $lonFrom = deg2rad($lonFrom);
        $latTo = deg2rad($latTo);
        $lonTo = deg2rad($lonTo);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) + cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));

        return round($angle * $this->getRadius(), 3);
    }

    /**
     * Create the graph of distances between each city
     *
     * @param array $locationData
     *
     * @return array
     */
    public function getDistanceGraph(array $locationData): array
    {
        $distanceGraph = [];

        for ($i = 0; $i < count($locationData); $i++) {
            $distanceSet = [];

            for ($j = 0; $j < count($locationData); $j++) {
                $distanceSet[$j] = $this->distanceBetweenCoordinates(
                    $locationData[$i]['latitude'],
                    $locationData[$i]['longitude'],
                    $locationData[$j]['latitude'],
                    $locationData[$j]['longitude']);
            }

            $distanceGraph[$i] = $distanceSet;
        }

        return $distanceGraph;
    }

    /**
     * Calculate route distance
     *
     * @param array $route
     * @param array $distanceGraph
     * @param bool $hamiltonian
     *
     * @return float
     */
    function calculateRouteDistance(array $route, array $distanceGraph, bool $hamiltonian): float
    {
        $distance = 0;

        // Check that it returns to the start
        if ($hamiltonian && $route[0] != $route[count($route) - 1]) {
            $route[] = $route[0];
        }

        for ($i = 0; $i < count($route) - 1; $i++) {
            $distance += $distanceGraph[$route[$i]][$route[$i + 1]]; 
        }

        return $distance;
    }
}
 
