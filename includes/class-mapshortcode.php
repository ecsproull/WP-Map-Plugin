<?php
/**
 * Summary
 * Place class.
 *
 * @package     Maps
 * @author      Edward Sproull
 * @copyright   You have the right to copy
 * @license     GPL-2.0+
 */

/**
 * Create the database tables on activation.
 */
class MapShortcode extends EdsMapBase {

	/**
	 * Creates the DB tables when the the plugin is activated.f
	 */
	public function add_shortcode() {
		global $wpdb;
		$keys   = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT * FROM %1$s WHERE key_type = "map_key";',
				self::MAP_KEY_TABLE
			),
			OBJECT
		);
		$mapkey = $keys[0]->key_value;
		?>
		<style>

			#mapdived {
				height: 500px;
				/* The height is 400 pixels */
				width: 80%;
				/* The width is the width of the web page */
			}

		</style>
		<head>
		<script type="text/javascript">
		var currentMarker = null;
		var markerVisible = true;
		var currentTrip = 1;
		// The script element is created dynamically so I can add the "mapkey" variable to the src string.
		// Dynamic scripts behave as “async” by default. 
		document.addEventListener('DOMContentLoaded', (e) => {
			document.getElementById('trip').addEventListener('change', () => { 
				currentTrip = document.getElementById('trip').value;
			});

			document.head.appendChild(document.createElement('script'))
				.src = "https://maps.googleapis.com/maps/api/js?key=" + '<?php echo esc_html( $mapkey ); ?>' + "&callback=initMap&v=weekly";
		});

		// Generic function for making calls back to the server.
		function makeRequest(url, callback) {
			var request;
			if (window.XMLHttpRequest) {
				request = new XMLHttpRequest(); // IE7+, Firefox, Chrome, Opera, Safari
			} else {
				request = new ActiveXObject("Microsoft.XMLHTTP"); // IE6, IE5
			}
			request.onreadystatechange = function () {
				if (request.readyState == 4 && request.status == 200) {
					callback(request);
				}
			}
			request.open("GET", url, true);
			request.send();
		}

		// Where the map gets created.
		function initMap() {
			const kansas = {
				lat: 39.8283,
				lng: -98.5795
			};

			const map = new google.maps.Map(document.getElementById("mapdived"), {
				zoom: 4,
				center: kansas,
			});

			map.setOptions({styles: [{
			stylers: [
			{ position: "unset" }
			]
			}]});

			populateMap(map);
			setInterval(function () {
				if (currentMarker) {
					if (markerVisible) {
						currentMarker.setOpacity(1.0);
					} else {
						currentMarker.setOpacity(0.2);
					}

					markerVisible = ! markerVisible;
				}
			}, 1000);
		}

		function populateMap(map) {
			// TODO: This call will need to go to your website, not mine. :)
			var hostroot = 'http://localhost/wp';
			//var hostroot = 'https://edandlinda.com';
			makeRequest(hostroot + '/wp-json/edsplaces/v1/places?trip_id=' + currentTrip, function (data) {
				var data = JSON.parse(data.response);
				for (var i = 0; i < data.length; i++) {
					var place = data[i];
					var infowindow = new google.maps.InfoWindow();
					var content = '<div class="infoWindow"><strong><a href=\"' + place.place_website + '\" >' + place.place_name + '</a></strong><br>'
									+ "<a href='tel:" + place.place_phone +"'>" + place.place_phone + '</a>'
									+ '<p>' + place.place_info + '</p><br>'
									+ '<p>Arrive: ' + place.tp_arrive + '</p>'
									+ '<p>Depart: ' + place.tp_depart + '</p></div>' ;
					var position = new google.maps.LatLng(parseFloat(place.place_lat), parseFloat(place.place_lng));
					var today = new Date();
					var arriveDateParts = place.tp_arrive.split('-');
					var arriveDate = new Date(
						parseInt(arriveDateParts[0]),
						parseInt(arriveDateParts[1] - 1),
						parseInt(arriveDateParts[2])
					);

					var departDateParts = place.tp_depart.split('-');
					var departDate = new Date(
						parseInt(departDateParts[0]),
						parseInt(departDateParts[1] - 1),
						parseInt(departDateParts[2])
					);

					var iconPath = hostroot + '/wp-content/plugins/WP-Map-Plugin/img/DarkGreen.png';
					if ( arriveDate >= today) {
						iconPath =  hostroot + '/wp-content/plugins/WP-Map-Plugin/img/Red.png'
					}

					marker = new google.maps.Marker({
						position: position,
						map,
						title: place.place_name + '\nArrive: ' + place.tp_arrive + '\nDepart: ' + place.tp_depart,
						label: place.tp_label,
						icon: iconPath,
					});

					if (today >= arriveDate && today <= departDate) {
						currentMarker = marker;
					}

					google.maps.event.addListener(marker, 'click', (function (marker, content, infoWindow) {
						return function () {
							infowindow.setContent(content);
							infowindow.open(map, marker);
						};
					})(marker, content, infowindow));
				}
			});

			makeRequest(hostroot + '/wp-json/edsroute/v1/points?trip_id=' + currentTrip, function (data) {
				var data = JSON.parse(data.response);
				var values = [];
				for (var i = 0; i < data.length; i++) {
					values[i] = { lat: parseFloat(data[i].points_lat), lng: parseFloat(data[i].points_lng) }
				}

				const route = new google.maps.Polyline({
					path: values,
					geodesic: true,
					strokeColor: "#FF0000",
					strokeOpacity: 1.0,
					strokeWeight: 4,
				});

				route.setMap(map);
			});
		}
		</script>
		</head>
		<body>
			<div style="position: unset;">
			<?php
			$this->trip_select( '2' );
			?>
			</div>
			<div id="mapdived" style="position: unset;"></div>
		</body>
		<?php
	}
}
