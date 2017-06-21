<?php

namespace GeoRoute;

use GeoRoute\Distance;
use GeoRoute\LocationFileHandler;
use GeoRoute\PathFinding;

/**
 * GeoRoute
 *
 * Parse a file of locations and determine a shortest hamiltonian path
 * to visit the most populated locations given the restraint of time, 
 * speed and the number you can visit.
 *
 * @package GeoRoute
 *
 * @author James Hilton <pyjammez@gmail.com>
 *
 */
class GeoRoute
{
    private $time;
    private $units;
    private $speed;
    private $route;
    private $maxVisits;
    private $distanceGraph;
    private $minPopulation;
    private $startingLatitude;
    private $startingLongitude;

    private $Distance;
    private $LocationFileHandler;
    private $PathFinding;

    public $locationData;
    public $routeDistance;
    public $routeLocationData;

    function __construct()
    {
        $this->Distance = new Distance();
        $this->PathFinding = new PathFinding;
    }

    /**
     * Set the location of the locations file
     *
     * @param string $filePath
     */
    public function setFile(string $filePath)
    {
        $this->LocationFileHandler = new LocationFileHandler($filePath);
    }

    /**
     * Set the time in hours that you have available to travel
     */
    public function setTime(int $time)
    {
        $this->time = $time;
    }

    /**
     * Set the units of measurement for your trip.
     *
     * @param string $units
     */
    public function setUnits(string $units)
    {
        $this->units = $units;
    }

    /**
     * Set the average speed that you can travel
     *
     * @param string $speed
     */
    public function setSpeed(float $speed)
    {
        $this->speed = $speed;
    }

    /**
     * Set the max number of locations you can visit on this trip
     *
     * @param int $maxVisits
     */
    public function setmaxVisits(int $maxVisits)
    {
        $this->maxVisits = $maxVisits;
    }

    /**
     * Set the minimum city population you want in your selection.
     *
     * @param int $minPopulation
     */
    public function setMinPop(int $minPopulation)
    {
        $this->minPopulation = $minPopulation;
    }

    /**
     * Set your starting coordinates
     *
     * @param float $latitude
     * @param float $longitude
     */
    public function setStartingCoordinates(float $latitude, float $longitude)
    {
        $this->startingLatitude = $latitude;
        $this->startingLongitude = $longitude;
    }

    /**
     * Get an array of location data from the file. To save memory
     * only save those with the minimum population and within our
     * maximum travel distance of a round trip.
     *
     * @return array
     */
    public function getLocationData(): array
    {
        if ($this->locationData) return $this->locationData;

        $maxDistance = $this->time * $this->speed;

        $this->locationData = $this->LocationFileHandler->getLocationData(
            $this->startingLatitude,
            $this->startingLongitude,
            $this->minPopulation,
            $maxDistance);

        return $this->locationData;
    }

    /**
     * Get the time it takes to travel our route given our speed
     *
     * @return int
     */
    public function getRouteTime(): int
    {
        return $this->routeDistance / $this->speed;
    }

    /**
     * Add our starting location into the table of data
     *
     * @param array $locationData
     * 
     * @return array
     */
    private function addStartingLocation($locationData): array
    {
        $locationData[] = [
            'name' => "Starting Location",
            'population' => 0,
            'latitude' => $this->startingLatitude,
            'longitude' => $this->startingLongitude,
            'distance' => 0,
        ];

        return $locationData;
    }

    /**
     * Create a distance graph from the location data
     *
     * @return array
     */
    public function getDistanceGraph(array $locationData): array
    {
        $this->distanceGraph = $this->Distance->getDistanceGraph($locationData);

        return $this->distanceGraph;
    }

    /**
     * Get the shortest route given the parameters
     *
     * @return array
     */
    public function getRoute(): array
    {
        if ($this->route) {
            return $this->route;
        }

        if (!$this->locationData) {
            $this->getLocationData(); 
        }

        // Add in our starting location
        $this->locationData = $this->addStartingLocation($this->locationData);
        end($this->locationData);
        $startingLocationKey = key($this->locationData);

        // Get the graph of all the location data
        $this->distanceGraph = $this->getDistanceGraph($this->locationData);

        // Order by highest population
        uasort($this->locationData, function($a, $b) {
            return intval($b['population']) <=> intval($a['population']);
        });

        // Get our first route with the highest populations
        $this->highestPopLocationData = array_slice($this->locationData, 0, $this->maxVisits, true);

        // Add our starting location back in
        $this->highestPopLocationData[$startingLocationKey] = $this->locationData[$startingLocationKey];

        // Start with the nearest neighbour algorithm of the highest populations
        // to get a starting route for the 2-opt.
        $highestPopGraph = array_intersect_key($this->distanceGraph, $this->highestPopLocationData);

        $this->route = $this->PathFinding->nearestNeighbourAlgorithm([], $highestPopGraph, $startingLocationKey);

        // Now do the two-opt algorithm
        $this->route = $this->PathFinding->TwoOptAlgorithm($this->route, $this->distanceGraph);

        // Now do the reducer with all the locations we have available to us
        foreach ($this->locationData as $key => $location) {
            $populationData[$key] = $location['population'];
        }

        $this->route = $this->PathFinding->DistanceReducer($this->route, $this->distanceGraph, $populationData, $this->time, $this->speed);

        // Run the 2-top again to clean it up
        $this->route = $this->PathFinding->TwoOptAlgorithm($this->route, $this->distanceGraph);

        return $this->route;
    }

    /**
     * Get the distance of your route
     *
     * @return float
     */
    public function getRouteDistance(): float
    {
        $this->routeDistance = $this->Distance->calculateRouteDistance($this->route, $this->distanceGraph, true);

        return $this->routeDistance;
    }

    /**
     * Get the total population you have covered from your visits
     *
     * @return int
     */
    public function getPopulationVisited(): int
    {
        $population = 0;

        foreach ($this->route as $city) {
            $population += $this->locationData[$city]['population'];
        }

        return $population;
    }

    /**
     * Get number of locations visited
     *
     * @return int
     */
    public function getNumberOfLocationsVisited(): int
    {
        return count($this->route) - 2;
    }

    /**
     * Get the location data for your route in the correct order
     *
     * @return array
     */
    public function getRouteLocationData(): array
    {
        foreach ($this->route as $key) {
            $orderedLocation[$key] = $this->locationData[$key];
        }
        return $orderedLocation;
    }
}
