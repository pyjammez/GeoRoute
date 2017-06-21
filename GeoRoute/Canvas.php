<?php

namespace GeoRoute;

/**
 * Canvas
 *
 * Functions to help display data on the html canvas
 *
 * @package GeoRoute
 */
class Canvas
{
    /**
     * Convert lat lon coordinates into canvas coordinates.
     */
    public function canvasCoordinates(float $lat = null, float $lon = null): string
    {
        $multiplier = 800;
        $latOffset = -33.6;
        $lonOffset = 118.3;

        $newLat = 500 - ($lat + $latOffset) * $multiplier;
        $newLon = ($lon + $lonOffset) * $multiplier;

        return "$newLon, $newLat"; 
    }

    /**
     * Draw canvas line
     */
    public function drawLine(string $from, string $to): string
    {
        return "
            ctx.beginPath();
            ctx.moveTo($from);
            ctx.lineTo($to);
            ctx.stroke();
        ";
    }

    /**
     * Draw the route path
     */
    public function drawRoute(array $route, array $cities): string
    {
        $lines = [];

        foreach ($route as $city) {
            $canvasCoordinates = $this->canvasCoordinates($cities[$city]['latitude'], $cities[$city]['longitude']);

            if (isset($prev)) {
                $lines[] = $this->drawLine($prev, $canvasCoordinates);
            }

            $prev = $canvasCoordinates;
        }

        return implode('', $lines);
    }
}
