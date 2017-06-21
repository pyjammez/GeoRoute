<?php

    spl_autoload_register(function($class) {
        $class = str_replace('\\', '/', $class);
        include "/var/www/html/$class.php";
    });

    use GeoRoute\GeoRoute;
    use GeoRoute\Canvas;

    $distanceGraph = [];
    $locationData = [];
    $file = "./cities.txt";
    $speed = 5;
    $time = 12;
    $maxVisits = 20;
    $minPop = 1000;
    $startingLatitude = 33.807944;
    $startingLongitude = -117.951391;
    $routeTime = null;
    $routeDistance = null;
    $populationVisited = null;
    $numberLocationsVisited = null;

    if ($_POST) {
        $file   = $_POST['file'];
        $speed  = $_POST['speed'];
        $time   = $_POST['time'];
        $units  = $_POST['units'];
        $minPop = $_POST['minPop'];
        $maxVisits = $_POST['maxVisits'];
        $startingLatitude  = $_POST['latitude'];
        $startingLongitude = $_POST['longitude'];

        $geoRoute = new GeoRoute;

        $geoRoute->setFile($file);
        $geoRoute->setTime($time);
        $geoRoute->setSpeed($speed);
        $geoRoute->setMinPop($minPop);
        $geoRoute->setUnits($units);
        $geoRoute->setMaxVisits($maxVisits);
        $geoRoute->setStartingCoordinates($startingLatitude, $startingLongitude);

        $route                  = $geoRoute->getRoute();
        $routeDistance          = $geoRoute->getRouteDistance();
        $routeTime              = $geoRoute->getRouteTime();
        $locationData           = $geoRoute->getLocationData();
        $routeLocationData      = $geoRoute->getRouteLocationData();
        $populationVisited      = $geoRoute->getPopulationVisited();
        $numberLocationsVisited = $geoRoute->getNumberOfLocationsVisited();
    }
?>

<html>
    <head>
        <title>Geo Route Demo</title>

        <script
          src="https://code.jquery.com/jquery-3.2.1.slim.min.js"
          integrity="sha256-k2WSCIexGzOj3Euiig+TlR8gA0EmPjuc79OEeY5L45g="
          crossorigin="anonymous"></script>

        <!-- Latest compiled and minified CSS -->
        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">

        <!-- Optional theme -->
        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap-theme.min.css" integrity="sha384-rHyoN1iRsVXV4nD0JutlnGaslCJuC7uwjduW9SVrLvRYooPp2bWYgmgJQIXwl/Sp" crossorigin="anonymous">

        <!-- Latest compiled and minified JavaScript -->
        <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>
    </head>

    <body>
        <div class="container">
            <h1>Geo Route Demo</h1>

            <hr />

            <div class="row">
                <div class="col-md-6">
                    <div class="panel panel-default">
                        <div class="panel-heading">Set the details of your trip.</div>

                        <div class="panel-body">
                            <form method="POST">
                                <div class="row">
                                    <div class="form-group col-md-6">
                                        <label>Units</label>
                                        <select name="units" class="form-control">
                                            <option>Miles</option>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group col-md-6">
                                        <label>Speed (per hour)</label>
                                        <input name="speed" placeholder="Speed" class="form-control"
                                            value="<?php echo $speed; ?>" />
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="form-group col-md-6">
                                        <label>Time available (hours)</label>
                                        <input name="time" placeholder="Time (hours)" class="form-control"
                                            value="<?php echo $time; ?>" />
                                    </div>
                                    
                                    <div class="form-group col-md-6">
                                        <label>Maximum visits you can make</label>
                                        <input name="maxVisits" placeholder="How many locations can you visit"
                                            class="form-control" value="<?php echo $maxVisits; ?>" />
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="form-group col-md-6">
                                        <label>Path to cities file</label>
                                        <input name="file" class="form-control"
                                            value="<?php echo $file; ?>" />
                                    </div>

                                    <div class="form-group col-md-6">
                                        <label>Minimum population to visit</label>
                                        <input name="minPop" class="form-control"
                                            placeholder="Minimum Population" value="<?php echo $minPop; ?>" />
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="form-group col-md-6">
                                        <label>Starting latitude</label>
                                        <input name="latitude" class="form-control"
                                            placeholder="Latitude" value="<?php echo $startingLatitude; ?>" />
                                    </div>

                                    <div class="form-group col-md-6">
                                        <label>Starting longitude</label>
                                        <input name="longitude" class="form-control"
                                            placeholder="Longitude" value="<?php echo $startingLongitude; ?>" />
                                    </div>
                                </div>

                                <div class="form-group">
                                    <button type="submit" class="btn btn-success">Create Route</button>
                                </div>
                            </form>

                            <h3>Trip Statistics</h3>

                            <p>Locations Visited: <?php echo $numberLocationsVisited; ?></p>
                            <p>Route Distance: <?php echo $routeDistance; ?></p>
                            <p>Expected Time: <?php echo $routeTime ? $routeTime." hours" : ""; ?></p>
                            <p>Population Covered: <?php echo $populationVisited; ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="panel panel-default">
                        <div class="panel-heading">Short-ish route given the constraints.ONLY WORKS WITH DEFAULT COORDS</div>
                
                        <div class="panel-body">
                            <canvas id="routeCanvas" width="500" height="500"style="width: 100%;height: 100%;max-height: 500px;"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (isset($routeLocationData)): ?>
                <h3>Best Route</h3>
                <table class="table bordered-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Pop</th>
                            <th>Lat</th>
                            <th>Lon</th>
                            <th>Distance</th>
                        </tr>
                    </thead>
                    
                    <tbody>
                        <?php
                            foreach ($routeLocationData as $city) {
                                echo "<tr>";

                                foreach ($city as $val)  {
                                    echo "<td>$val</td>";
                                }

                                echo "</tr>";
                            }
                        ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </body>
    
    <script>
        var canvas = document.getElementById("routeCanvas");
        var ctx = canvas.getContext("2d");
        ctx.font = "10px Arial";
        ctx.fillStyle = "#000000";

        <?php
            $canvas = new Canvas;
            if (isset($routeLocationData)) {
                foreach ($routeLocationData as $city) {
                    $canvasCoordinates = $canvas->canvasCoordinates($city['latitude'], $city['longitude']);

                    echo "ctx.fillRect($canvasCoordinates, 5, 5);ctx.fillText(\"{$city['name']}\", $canvasCoordinates);"; 
                }
            }

            if (isset($route)) {
                echo $canvas->drawRoute($route, $locationData);
            }
        ?>
    </script>
</html>
