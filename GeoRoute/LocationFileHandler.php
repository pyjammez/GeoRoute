<?php

namespace GeoRoute;

use GeoRoute\Distance;

/**
 * LocationFileHandler
 *
 * @package GeoRoute
 *
 */
class LocationFileHandler
{
    private $Distance;
    private $fileHandle;
    private $headerColumns;

    /**
     * Open the file upon instantiation
     *
     * @param string $filePath
     */
    public function __construct(string $filePath)
    {
        if (!$this->fileHandle = fopen($filePath, "r")) {
            exit("Error. Need to get the cities document from the gov.");
        }

        $this->Distance = new Distance();
    }

    /**
     * Remove the header from the top row and get the indexes of the columns
     */
    private function parseHeaderColumns()
    {
        $header = fgets($this->fileHandle);
        $parts = explode("\t", trim($header));

        // We need the column index for each header
        $name = array_search('city', $parts);
        $pop  = array_search('pop', $parts);
        $lat  = array_search('lat', $parts);
        $lon  = array_search('lon', $parts);

        if ($lat === false || $lon === false || $pop === false || $name === false) {
            exit("Error. Column names have been changed. Email dev to fix.");
        }

        $this->headerColumns = [
            'name' => $name,
            'population' => $pop,
            'lat' => $lat,
            'lon' => $lon,
        ];
    }

    /**
     * Create an array of the city data, discarding those outside of our max travel distance
     * and those satisfying our minimum population requirement
     *
     * @param float $startingLatitude
     * @param float $startingLongitude
     * @param int minPopulation
     * @param int maxDistance
     *
     * @return array
     */
    public function getLocationData(float $startingLatitude, float $startingLongitude, int $minPopulation, int $maxDistance): array
    {
        $this->parseHeaderColumns();

        $nameIndex = $this->headerColumns['name'];
        $populationIndex = $this->headerColumns['population'];
        $latIndex = $this->headerColumns['lat'];
        $lonIndex = $this->headerColumns['lon'];

        while (($line = fgets($this->fileHandle)) !== false) {
            $parts = explode("\t", $line);
            $parts = array_map('trim', $parts);

            // check if all the expected data is there
            if (count($parts) < 5) {
                continue;
            }

            // discard if population less than our minimum
            if ($parts[$populationIndex] < $minPopulation) {
                continue;
            }

            $distanceToCity = $this->Distance->distanceBetweenCoordinates(
                $startingLatitude,
                $startingLongitude,
                $parts[$latIndex],
                $parts[$lonIndex]);

            // if it's further than half our max distance (a return trip) discard it
            if ($distanceToCity < $maxDistance/2) {
                $locationData[] = [
                    'name' => $parts[$nameIndex],
                    'population' => $parts[$populationIndex],
                    'latitude' => $parts[$latIndex],
                    'longitude' => $parts[$lonIndex],
                    'distance' => $distanceToCity,
                ];
            }
        }

        fclose($this->fileHandle);

        return $locationData;
    }
}
