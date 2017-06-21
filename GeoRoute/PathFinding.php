<?php

namespace GeoRoute;

use GeoRoute\Distance;

/**
 * Path Finding
 *
 * @package GeoRoute
 *
 */
class PathFinding
{
    /**
     * Nearest Neighbour Algorithm
     *
     * @param array $route
     * @param array $distanceGraph
     * @param int $startingLocationKey
     *
     * @see https://en.wikipedia.org/wiki/Nearest_neighbour_algorithm
     *
     */
    public function nearestNeighbourAlgorithm(array $route, array $distanceGraph, int $startingLocationKey): array
    {
        $locationCount = count($distanceGraph);
        $locationCountDecrementer = $locationCount-1;

        // start with our starting location already added to the route array
        $route[] = $i = $startingLocationKey;

        $keys = array_keys($distanceGraph);

        while ($locationCountDecrementer--) {
            $shortestDistance = INF;

            foreach ($keys as $j) {
                if (!in_array($j, $route) && $distanceGraph[$i][$j] && $distanceGraph[$i][$j] < $shortestDistance) {
                    $shortestDistance = $distanceGraph[$i][$j];
                    $closestCityIndex = $j;
                }
            }

            $route[] = $i = $closestCityIndex;
        }

        // continue back to our starting location
        $route[] = $startingLocationKey;

        return $route;
    }

    /**
     * 2-opt Algorithm
     *
     * @param array $route
     * @param array $distanceGraph
     *
     * @see https://en.wikipedia.org/wiki/2-opt
     *
     */
    public function TwoOptAlgorithm(array $route, array $distanceGraph): array
    {
        $locationCount = count($route);
        $improve = 0;
        $distance = new Distance;

        while ($improve < 3) {
            $shortestDistance = $distance->calculateRouteDistance($route, $distanceGraph, true);

            for ($i = 1; $i < $locationCount; $i++) {
                for ($k = 1; $k < $locationCount - $i; $k++) {
                    $newRouteStart = array_slice($route, 0, $i);
                    $newRouteMiddle = array_slice($route, $i, $k);
                    $newRouteEnd = array_slice($route, $i+$k);

                    // Reverse the middle part and remerge
                    $newRoute = array_merge(
                        $newRouteStart,
                        array_reverse($newRouteMiddle),
                        $newRouteEnd);

                    $newDistance = $distance->calculateRouteDistance($newRoute, $distanceGraph, true);

                    // If there's an improvement, keep the new route
                    if ($newDistance < $shortestDistance) {
                        $shortestDistance = $newDistance;
                        $route = $newRoute;
                        $improve = 0;
                    }
                }
            }

            $improve++;
        }

        return $route;
    }

    /**
     * Custom Distance Reducer Algorithm
     *
     * @param array $route
     * @param array $distanceGraph
     * @param array $populationData
     * @param int $allowedTime
     * @param int $speed
     *
     */
    public function DistanceReducer(array $route, array $distanceGraph, array $populationData, int $allowedTime, int $speed): array
    {
        $distance = new Distance;
        $routeDistance = $distance->calculateRouteDistance($route, $distanceGraph, true);
        $allowedDistance = $allowedTime * $speed;

        // Keep reducing the distances until we reach our allowed travel time.
        while ($routeDistance > $allowedDistance) {
            $proposedSwap = [];
            $highestPopulationChange = 0;

            for ($cityToSwap = 1; $cityToSwap < count($route) - 2; $cityToSwap++) {
                // grab the locations before and after $cityToSwap. 
                $subRoute = array_slice($route, $cityToSwap-1, 3);
                $subRouteDistance = $distance->calculateRouteDistance($subRoute, $distanceGraph, false);
                $cityPopulation = $populationData[$subRoute[1]];
                $chosenNewCity = '';
                $highestSubRoutePopulation = 0;

                // find the highest population city that creates a shorter path than our chosen city
                foreach (array_keys($distanceGraph) as $newCity) {
                    // don't let it choose a city we are already visiting
                    if (in_array($newCity, $route)) continue;

                    // Swap the middle city with a new one and check it's distance
                    $subRoute[1] = $newCity;
                    $newSubRouteDistance  = $distance->calculateRouteDistance($subRoute, $distanceGraph, false);

                    // if the path is shorter with this substitute, keep the one with the highest population
                    if ($newSubRouteDistance < $subRouteDistance && $populationData[$newCity] >= $highestSubRoutePopulation) {
                        $chosenNewCity = $newCity;
                        $highestSubRoutePopulation = $populationData[$newCity];
                        $distanceSavedWithNewRoute = $subRouteDistance - $newSubRouteDistance;
                    }
                }

                // of all the subroute suggestions, which one has the highest population
                if ($highestSubRoutePopulation > $highestPopulationChange && $distanceSavedWithNewRoute > 0) {
                    $proposedSwap = [$cityToSwap, $chosenNewCity, $distanceSavedWithNewRoute];
                }
            }

            // if we have no suggested swaps, we can't go any further with this algorithm as is.
            if (empty($proposedSwap)) break;

            // update route
            array_splice($route, $proposedSwap[0], 1, $proposedSwap[1]);

            $routeDistance -= $distanceSavedWithNewRoute;
        }

        return $route;
    }
}
