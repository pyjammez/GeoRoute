<?php

/**
 * ----------------------------------------------------------
 * Goodies Pollution Reduction Initiative
 * ----------------------------------------------------------
 * 
 * This script helps us determine the best path and locations
 * to drop our 20 bags of grass seed in order to have the 
 * maximum effect on decreasing pollution by ensuring seeds are
 * dropped in the most densely populated areas.
 *
 * @author James Hilton <pyjammez@gmail.com>
 */

/**
 * Constants
 */
$hours_of_flight_time = 12;
$vehicle_speed_mph = 5;
$seed_bags = 20;
$seed_bag_mile_radius_of_effectiveness = 10;
$starting_lat = 33.807944;
$starting_lon = -117.951391;
$city_min_population = 1000;

/**
 * We must land in the same place we took off from, so the max
 * return trip distance we can travel is based on our hours of
 * flight time multipled by our speed.
 */
$max_distance = $hours_of_flight_time * $vehicle_speed_mph / 2;

/**
 * Calculate the great circle distance using Haversine formula
 */
function distanceToCity($starting_lat, $starting_lon, $city_lat, $city_lon) {
    $latFrom = deg2rad($starting_lat);
    $lonFrom = deg2rad($starting_lon);
    $latTo = deg2rad($city_lat);
    $lonTo = deg2rad($city_lon);

    $latDelta = $latTo - $latFrom;
    $lonDelta = $lonTo - $lonFrom;

    $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) + cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));

    return round($angle * 3959, 3);
}

/**
 * Grab the latest cities txt document because the federal gov
 * hasn't figured out how to use databases and APIs yet.
 */
if (!$file_handle = fopen("./cities.txt", "r")) {
    exit("Error. Need to get the cities document from the gov.");
}

/** 
 * Get the column headers
 */
$header = fgets($file_handle);
$parts = explode("\t", trim($header));
$lat_key = array_search('lat', $parts);
$lon_key = array_search('lon', $parts);
$population_key = array_search('pop', $parts);
$name_key = array_search('city', $parts);

if ($lat_key === false || $lon_key === false || $population_key === false || $name_key === false) {
    exit("Error. Column names have been changed. Email dev to fix.");
}

/**
 * Create an array of the city data, discarding those outside of our max travel distance
 */
$cities = [];

while (($line = fgets($file_handle)) !== false) {
    $parts = explode("\t", $line);

    // Discard the small cities.
    if ($parts[$population_key] < $city_min_population) {
        continue;
    }

    // Only keep cities within our travel distance
    $distance = distanceToCity($starting_lat, $starting_lon, $parts[$lat_key], $parts[$lon_key]);

    if ($distance < $max_distance) {
        $extra_data = [
            'distance' => $distance
        ];
        
        $cities[] = array_merge($parts, $extra_data);
    }
}

fclose($file_handle);

if (empty($cities)) {
    exit("Error. You are not close enough to any cities to perform your task.");
}

/**
 * Get the 20 most populated cities to start our route with.
 */
usort($cities, function($a, $b) {
    return intval($b[2]) <=> intval($a[2]);
});

$top_cities = array_slice($cities, 0, 20);

array_unshift($top_cities, ['Starting Location', '', 0, $starting_lat, $starting_lon, 'distance' => 0]);
array_unshift($cities, ['Starting Location', '', 0, $starting_lat, $starting_lon, 'distance' => 0]);

/**
 * Create the mapping of distances between each city
 */
$distances = [];

for ($i=0; $i<count($top_cities); $i++) {
    $distance_set = [];
    for ($j=0; $j<count($top_cities); $j++) {
        $distance_set[$j] = distanceToCity($top_cities[$i][$lat_key], $top_cities[$i][$lon_key], $top_cities[$j][$lat_key], $top_cities[$j][$lon_key]);
    }

    $distances[$i] = $distance_set;
}

/**
 * Calculate route distance
 */
function calculateDistance($route, $distances) {
    $distance = 0;

    // Check that it returns to the start
    if ($route[0] != $route[count($route)-1]) {
        $route[] = $route[0];
    }

    for ($i=0; $i<count($route); $i++) {
        $distance += $distances[$route[$i]][$route[$i+1]]; 
    }

    return $distance;
}

/**
 * Convert lat lon coordinates into canvas coordinates.
 */
function canvasCoordinates($lat = null, $lon = null) {
    $multiplier = 500;
    $lat_offset = -33.5;
    $lon_offset = 118.4;

    $new_lat = 500 - ($lat + $lat_offset) * $multiplier;
    $new_lon = ($lon + $lon_offset) * $multiplier;

    return "$new_lon, $new_lat"; 
}

/**
 * Draw canvas line
 */
function drawLine($from, $to) {
    echo "
        ctx.beginPath();
        ctx.moveTo($from);
        ctx.lineTo($to);
        ctx.stroke();
    ";
}

/**
 * Draw the route path
 */
function drawRoute($route, $cities, $lat_key, $lon_key) {
    foreach ($route as $city) {
        $canvasCoordinates = canvasCoordinates($cities[$city][$lat_key], $cities[$city][$lon_key]);

        if (isset($prev)) {
            drawLine($prev, $canvasCoordinates);
        }

        $prev = $canvasCoordinates;
    }
}
?>

<canvas id="myCanvas" width="500" height="500" style="border:1px solid #d3d3d3;float: left;margin: 0 20px 20px 0;"></canvas>
<script>
    var canvas = document.getElementById("myCanvas");
    var ctx = canvas.getContext("2d");
    ctx.font = "10px Arial";
    ctx.fillStyle = "#000000";

    <?php
        /**
         * Create the city markers
         */
        foreach ($top_cities as $city) {
            $canvasCoordinates = canvasCoordinates($city[$lat_key], $city[$lon_key]);
            echo "ctx.fillRect($canvasCoordinates, 5, 5);ctx.fillText(\"{$city[0]}\", $canvasCoordinates);"; 
        }

        /**
         * Path from most popular to least popular starting at home

        $route = [0];
        foreach ($top_cities as $key => $city) {
            $canvasCoordinates = canvasCoordinates($city[$lat_key], $city[$lon_key]);

            if (isset($prev)) {
                drawLine($prev, $canvasCoordinates);
            }

            $prev = $canvasCoordinates;
            $route[] = $key;
        }

        echo "//distance: ".calculateDistance($route, $distances)."\n";
        */

        /**
         * Nearest Neighbour Hamiltonian Path
         * This can be rerun with a different starting point to generate a different route that might be shorter
         */
        $city_count = count($distances);
        $city_count_inc = $city_count-1;
        $i = 0; // starting city index
        $route = [0];

        while ($city_count_inc--) {
            // draw a line from starting location to closest city.
            $shortest_distance = INF;

            for ($j=0; $j<$city_count; $j++) {
                if (!in_array($j, $route) && $distances[$i][$j] && $distances[$i][$j] < $shortest_distance) {
                    $shortest_distance = $distances[$i][$j];
                    $closest_city_index = $j;
                }
            }

            $route[] = $closest_city_index;
            $i = $closest_city_index;
        }

        // continue back to beginning
        $route[] = 0;
        echo "//distance: ".calculateDistance($route, $distances)."\n";
        //drawRoute($route, $top_cities, $lat_key, $lon_key);



        /**
         * 2-opt Algorithm
         */
        $city_count = count($route);
        $improve = 0;

        while ($improve < 2) {
            $shortest_distance = calculateDistance($route, $distances);

            for ($i = 1; $i < $city_count; $i++) {
                for ($k = $i+1; $k < $city_count-1; $k++) {
                    $new_route_start = array_slice($route, 0, $i); //echo "\nstart"; print_r($new_route_start);
                    $new_route_middle = array_slice($route, $i, $k); //echo "\nmiddle"; print_r($new_route_middle);
                    $new_route_end = array_slice($route, $i+$k); //echo "\nend"; print_r($new_route_end);
                    $new_route = array_merge($new_route_start, array_reverse($new_route_middle), $new_route_end); //if ($i==3) exit;
                    $new_distance = calculateDistance($new_route, $distances);

                    if ($new_distance < $shortest_distance) {
                        //echo "$new_distance is less than $shortest_distance\nnew route"; print_r($new_route)";
                        $shortest_distance = $new_distance;
                        $route = $new_route;
                        $improve = 0;
                    }
                }
            }

            $improve++;
        }

        drawRoute($route, $top_cities, $lat_key, $lon_key);

    ?>
</script>

<table border='1' cellpadding='5' style='border-collapse:collapse'>
    <thead>
        <tr>
            <th>Name</th><th>State</th><th>Pop</th><th>Lat</th><th>Lon</th><th>Distance</th>
        </tr>
    </thead>
    
    <tbody>
        <?php
        foreach ($top_cities as $city) {
            echo "<tr>";
            foreach ($city as $val) {
                echo "<td>$val</td>";
            }
            echo "</tr>";
        }
        ?>
    </tbody>
</table>

<table border='1' cellpadding='5' style='border-collapse:collapse;'>
    <thead>
        <tr>
            <th>Name</th>
            <?php
            foreach ($distances as $key => $value) {
                echo "<th>{$cities[$key][0]}</th>";
            }
            ?>
        </tr>
    </thead>
    
    <tbody>
        <?php
        foreach ($distances as $key => $value) {
            echo "<tr>";
                echo "<td>{$cities[$key][0]}</td>";

                foreach ($value as $k => $v) {
                    echo "<td>$v</td>";
                }
            echo "</tr>";
        }
        ?>
    </tbody>
</table>
