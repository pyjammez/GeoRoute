# GeoRoute
### Challenge
Using a list of cities with population and coordinates, the program accepts a latitude and longitude as input, and outputs an ordered list of 20 "suggested nearby cities".

[Prototype in action](http://52.33.24.135/)

### Please submit an explanation of your thought process
I chose to make a path finding script that takes your gps coordinates and some other parameters, and creates a return trip around your neighbourhood, choosing cities with the highest populations, then adjusting the route by changing to closer but lesser populated cities to try and meet the range that you can travel.

The majority of the code relating specifically to this test is within the GeoRoute\PathFinder class where I use 3 algorithms to select the 20 cities.

* The first one is called a Nearest Neighbour Algorithm and selects the 20 most populated cities and creates a path starting from your location to the closest city, and continues selecting the next closest city until it's chosen all 20.

* The second one is called a 2-Opt Algorithm which loops through every combination of cities and reverses the path in order to try and find a shorter distance.

* The third algorithm is one I made up that loops through each city and attempts to swap it with a closer city that has the next highest population, shrinking the path with every iteration until either the target travel distance is met, or it cannot find a shorter path.

### What your code is intended to do for our Technology Team.
I brainstormed ideas on this for 2 days, and honestly, the best thing I could come up with that perfectly utilized the parameters you set in this test was a flying machine (since air travel doesn't need to take streets into consideration and we were not given street data), that deploys beacons that are either flying or fall to the ground and start detecting devices in their proximity. A bit far fetched, but that's the only thing I could relate back to beacons or surveys! While that's how I'm relating it to your company, my actual scenario used while building out this idea was based on an episode of the British TV show, The Goodies, where they tried to reduce pollution by flying around on their bicycle powered plane while dropping grass seeds over the city, turning every surface into grass. Given the parameters, I envisioned 20 bags of grass seed, a limited amount of time and speed, and the desire to drop the seeds over the most densely populated areas, sacrificing some higher populated cities in order to reach more closer cities. It's the only idea I had that fit the requirement perfectly! But nothing to do with your business unfortunately...
