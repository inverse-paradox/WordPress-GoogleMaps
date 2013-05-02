//initialize the google map
function calcRoute() {
	var start = document.getElementById("g-start").value;
	var end = document.getElementById("g-end").value;

	var request = {
		origin:start,
		destination:end,
		travelMode: google.maps.TravelMode.DRIVING
	};

	directionsService.route(request, function(result, status) {
		if (status == google.maps.DirectionsStatus.OK) {
			directionsDisplay.setDirections(result);
		}
	});
}