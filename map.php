<?php
/**
 * Summary
 * Database class.
 *
 * @package     Maps
 * @author      Edward Sproull
 * @copyright   You have the right to copy
 * @license     GPL-2.0+
 */

/**
 * Plugin Name: Map
 * Plugin URI:
 * Description: Map administration tools.
 * Version: 1.0
 * Author: Ed Sproull
 * Author URI:
 * Author Email:
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wpccp
 * Domain Path: /languages
 */

require 'includes/class-place.php';
$map_key_table = 'eds_map_keys';
$places_table  = 'eds_map_places';

/**
 * Adds styles.
 *
 * Adds the CSS that is used to style the admin side of the plug-in.
 * Note the use of "admin_enqueue_scripts". It took me a while to out that
 * that wp_enqueue_scripts adds scripts to the user side only.
 * I like using bootstrap but this is a personal preference.
 *
 * @return void
 */
function add_map_scripts_and_css() {
	wp_register_style( 'signup_bs_style', plugin_dir_url( __FILE__ ) . 'bootstrap/css/bootstrap.min.css', array(), 1 );
	wp_enqueue_style( 'signup_bs_style' );
	wp_register_style( 'signup_style', plugin_dir_url( __FILE__ ) . 'css/style.css', array(), 1 );
	wp_enqueue_style( 'signup_style' );
}
add_action( 'admin_enqueue_scripts', 'add_map_scripts_and_css' );

/**
 * Adds the one and only menu item for the plugin.
 *
 * @return void
 */
function map_plugin_top_menu() {
	add_menu_page( 'Map', 'Map', 'manage_options', 'mapsettings', 'map_settings_page', plugins_url( '/WP-Map-Plugin/img/pug.png', __DIR__ ) );
	add_submenu_page( 'mapsettings', 'Map Keys', 'Map Keys', 'manage_options', 'mapkeys', 'map_keys_page' );
}
add_action( 'admin_menu', 'map_plugin_top_menu' );

/**
 * Register the endpoint for API call to get the mapped points.
 * This will be called from JS. This will be used by the page
 * displaying our map. I added it to the plug-in just to keep
 * all of the code in one place.
 *
 *  @return void
 */
add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'edsplaces/v1',
			'/places',
			array(
				'methods'             => 'GET',
				'callback'            => 'get_trip_points',
				'permission_callback' => '__return_true',
			)
		);
	}
);

register_activation_hook( __FILE__, 'eds_google_map_plugin_function' );
/**
 * Activation function that creates the database tables.
 *
 * @return void
 */
function eds_google_map_plugin_function() {
	global $wpdb;
	if ( $wpdb->get_var( 'SHOW TABLES LIKE "eds_map_keys"' ) !== 'eds_map_keys' ) {
		$wpdb->query(
			'CREATE TABLE `eds_map_keys` (
			`key_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
			`key_label` varchar(45) NOT NULL,
			`key_type` varchar(10) NOT NULL,
			`key_value` varchar(45) NOT NULL,
			PRIMARY KEY (`key_id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;'
		);

		$wpdb->query(
			'INSERT INTO `eds_map_keys` (
			`key_label`,
			`key_type`,
			`key_value`
			) VALUES ( "Geo:", "geo_key", " " ),
			( "Map:", "map_key", " " );'
		);
	}

	if ( $wpdb->get_var( 'SHOW TABLES LIKE "eds_map_places"' ) !== 'eds_map_places' ) {
		$wpdb->query(
			'CREATE TABLE `eds_map_places` (
			`place_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
			`place_name` varchar(45) NOT NULL,
			`place_info` text NOT NULL,
			`place_lat` float NOT NULL,
			`place_lng` float NOT NULL,
			`place_label` int(11) DEFAULT NULL,
			`place_icon_type` tinyint(4) DEFAULT "0",
			`place_address` varchar(100) NOT NULL,
			`place_phone` varchar(45) NOT NULL,
			`place_website` varchar(150) NOT NULL,
			`place_arrive` date DEFAULT NULL,
			`place_depart` date DEFAULT NULL,
			`place_hide_info` tinyint(4) DEFAULT "0",
			PRIMARY KEY (`place_id`)) 
			ENGINE=InnoDB DEFAULT CHARSET=utf8;'
		);
	}
}
/**
 * The actual function that does the work of retrieving the points.
 *
 * @param  mixed $place Unused parameter, TODO verify if it can be removed.
 * @return array The results of the query.
 */
function get_trip_points( $place ) {
	try {
		global $wpdb, $places_table;
		$results = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT * FROM %1s',
				$places_table
			),
			OBJECT
		);

		return $results;

	} catch ( Exception $e ) {
		return $e->getMessage();
	}
}

/**
 * Google map points are located by latitude and longitude. The point of these JS functions are to
 * take an address and retrieve the lat and lng for that address. The makeRequest is a generic html request function.
 * I'm pretty sure I borrowed this from the web somewhere so if you perfer another way of doing this, have at it.
 * But wait there are more JS functions below why are these up here? Great question! These are used on the
 * administrative side and the ones below belong to the client side. More on that in the next comment.
 */
function add_lat_lng_script() {
	global $wpdb, $map_key_table;
	$keys = $wpdb->get_results(
		$wpdb->prepare(
			'SELECT * FROM %1s WHERE key_type = "geo_key";',
			$map_key_table
		),
		OBJECT
	);
	$map_key_geo = $keys[0]->key_value;
	?>
	<script>
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

		function updateLatLng() {
			var address = document.getElementById("addr");
			var url = "https://maps.googleapis.com/maps/api/geocode/json?address=" + address.value.replaceAll(' ', '+') + "&key=" + '<?php echo esc_html( $map_key_geo ); ?>';
			makeRequest(url, function (results) {
				var data = JSON.parse(results.response);
				var lat = document.getElementById("lat");
				var lng = document.getElementById("lng");
				lat.value = data.results[0].geometry.location.lat;
				lng.value = data.results[0].geometry.location.lng;
			});
		}
	</script>
	<?php
}

// Here again note that we are using the "admin" version of print_scripts since the plug-in runs
// on the admin side of things.
add_action( 'admin_print_scripts', 'add_lat_lng_script' );

/**
 *   The main function of the Plugin.
 *   This delegates all the real work to helper functions.
 *   Even at the simplist level our plug-in needs to be able to be CRUD complete
 */
function map_settings_page() {

	$mynonce = wp_create_nonce( 'my-nonce' );
	$post    = wp_unslash( $_POST );

	if ( isset( $post['mynonce'] ) ) {
		$my_nonce = $post['mynonce'];
		if ( wp_verify_nonce( $my_nonce, 'my-nonce' ) ) {
			unset( $post['mynonce'] );
			if ( isset( $post['submitPlace'] ) ) {
				submit_place( $post );
			} elseif ( isset( $post['selectPlace'] ) ) {
				load_place_selection( $my_nonce );
			} elseif ( isset( $post['editPlace'] ) ) {
				create_or_edit_place( $post['editPlace'], $my_nonce );
			} elseif ( isset( $post['deletePlace'] ) ) {
				delete_place( $post );
			}
		}
	} else {
		create_or_edit_place( -1, $mynonce );
	}
}

/**
 * Creates the page to enter Google Map keys.
 *
 * @return void
 */
function map_keys_page() {
	global $wpdb, $map_key_table;
	$mynonce = wp_create_nonce( 'my-nonce' );
	$post    = wp_unslash( $_POST );
	if ( isset( $post['mynonce'] ) ) {
		$my_nonce = $post['mynonce'];
		if ( wp_verify_nonce( $my_nonce, 'my-nonce' ) ) {
			if ( isset( $post['submit'] ) ) {
				$where = array( 'key_type' => $post['submit'] );
				$key   = array( 'key_value' => $post[ $post['submit'] ] );
				$rows = $wpdb->update(
					$map_key_table,
					$key,
					$where
				);

				?>
				<div class='text-center mt-4'>
					<h1><?php echo esc_html( $post['submit'] ); ?> was updated.</h1>
				</div>
				<?php
			}
		}
	}

	$keys = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM %1s;', $map_key_table ), OBJECT );
	?>
	<form  method="POST">
		<div id="content" class="container">
			<table class="mb-100px table table-striped mr-auto ml-auto">
				<?php
				foreach ( $keys as $key ) {
					?>
					<tr><td class="text-right mr-2"><label><?php echo esc_html( $key->key_label ); ?></label></td>
						<td><input  style="width: 350px;"
									type="text"
									name='<?php echo esc_html( $key->key_type ); ?>'
									value='<?php echo esc_html( $key->key_value ); ?>'
									placeholder="Enter Key" /> </td>
						<td><input 	class='submitbutton addItem'
									type="submit"
									value='<?php echo esc_html( $key->key_type ); ?>'
									name="submit"></td>
					</tr>
					<?php
				}
				?>
			</table>
		</div>
		<input type="hidden" name="mynonce" value="<?php echo esc_html( $mynonce ); ?>">
	</form>
	<?php
}

/**
 * This is the set up for C (create) & U (update) in CRUD
 *
 * @param mixed $place_id The id of the palce to edit.
 * @param int   $mynonce The security token.
 *
 * @return void
 */
function create_or_edit_place( $place_id, $mynonce ) {
	global $wpdb, $places_table;
	if ( -1 === $place_id ) {
		create_place_form( new Place(), $mynonce );
	} else {
		$places = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT * from %1s where place_id = %s',
				$places_table,
				$place_id
			),
			OBJECT
		);

		create_place_form( $places[0], $mynonce );
	}
}


/**
 * This is the D (delete) part of CRUD
 *
 * @param array $post Data returned from the form.
 * @return void
 */
function delete_place( $post ) {
	global $wpdb;
	$affected_rows = $wpdb->delete( 'places', array( 'place_id' => $post['deletePlace'] ) );
	update_map_message( $affected_rows );
}

/**
 * This is the U (update) part of CRUD
 *
 * @param array $post Data returned from the form.
 * @return void
 */
function submit_place( $post ) {
	global $wpdb;
	if ( ! isset( $post['place_hide_info'] ) ) {
		$post['place_hide_info'] = 0;
	} else {
		$post['place_hide_info'] = 1;
	}

	$affected_rows = 0;
	unset( $post['submitPlace'] );
	if ( '' === $post['place_id'] ) {
		unset( $post['place_id'] );
		$affected_rows = $wpdb->insert( 'places', $post );
	} else {
		$where             = array();
		$where['place_id'] = $post['place_id'];
		unset( $post['place_id'] );
		$affected_rows = $wpdb->update(
			'places',
			$post,
			$where
		);
	}

	update_map_message( $affected_rows );
}

/**
 * This is the R part where we retrive our places to edit them.
 *
 * @param int $mynonce Security token.
 * @return void
 */
function load_place_selection( $mynonce ) {
	global $wpdb, $places_table;
	$places = $wpdb->get_results(
		$wpdb->prepare(
			'SELECT * from %1s',
			$places_table,
		),
		OBJECT
	);

	create_place_selection_form( $places, $mynonce );
}


/**
 * Display after an edit.
 *
 * @param  mixed $rows_updated The number of rows updated.
 * @return void
 */
function update_map_message( $rows_updated ) {
	global $wpdb;
	if ( 1 === $rows_updated ) {
		?>
		<div class="text-center mt-5">
			<h2> Place Updated </h2>
		</div>
		<?php
	} else {
		?>
		<div class="text-center mt-5">
			<h2> Something went wrong. </h2>
			<h3><?php echo esc_html( $rows_updated ); ?> Rows Updated</h3>
			<h3><?php echo esc_html( $wpdb->last_error ); ?></h3>
		</div>
		<?php
	}
	?>
	<div class="text-center mr-2">
		<input class="btn bt-md btn-danger" style="cursor:pointer;" type="button" onclick="window.history.go( -1 );" value="Back">
	</div>
	<?php
}

/**
 * Creates a list of places to select one to edit.
 *
 * @param array $places The list of places to select from.
 * @param int   $mynonce Security token.
 * @return void
 */
function create_place_selection_form( $places, $mynonce ) {
	?>
	<form  method="POST">
		<div id="content" class="container">
			<table class="mb-100px table table-striped mr-auto ml-auto">
				<?php
				foreach ( $places as $place ) {
					?>
					<tr><td class="text-left"> <?php echo esc_html( $place->place_name ); ?></td>
						<td> <input class="submitbutton editImage" type="submit" name="editPlace" value="<?php echo esc_html( $place->place_id ); ?>"> </td>
						<td> <input class="submitbutton deleteImage" type="submit" name="deletePlace" value="<?php echo esc_html( $place->place_id ); ?>"> 
					</tr>
					<?php
				}
				?>
			</table>
		</div>
		<input type="hidden" name="mynonce" value="<?php echo esc_html( $mynonce ); ?>">
	</form>
	</div>
		<input class="btn btn-danger" style="cursor:pointer;" type="button" onclick="   window.history.go(-0);" value="Back">
	</div>
	<?php
}

/**
 * Create a form to update a place.
 *
 * @param  mixed $place The data about the place to be edited.
 * @param int   $mynonce Security token.
 * @return void
 */
function create_place_form( $place, $mynonce ) {
	?>
	<form  method="POST">
		<input class="btn bt-md btn-primary mr-auto ml-auto d-block mt-2 mb-2"  type="submit" value="Select Place" name="selectPlace">
		<table class="table table-striped mr-auto ml-auto">
			<tr><td class="text-right mr-2"><label>Place Name:</label></td>
				<td><input class="w-250px"  type="text" name="place_name" value="<?php echo esc_html( $place->place_name ); ?>" /> </td></tr>
			<tr><td class="text-right mr-2"><label>Place Info:</label></td>
				<td><textarea 
					class="w-250px"
					type="text"
					name="place_info"
					value="<?php echo esc_html( $place->place_info ); ?>">
					<?php echo esc_html( $place->place_info ); ?></textarea>
				</td>
			</tr>
			<tr><td class="text-right mr-2"><label>Icon Name:</label></td>
				<td><select id="icon" name="place_icon_type" value="<?php echo esc_html( $place->place_icon_type ); ?>">
						<option value="0">Rv Park</option>
						<option value="1">House</option>
						<option value="2">Rest Stop</option>
						</select><td></tr>
				<tr><td class="text-right mr-2"><label>Address: </label></td>
				<td><input id="addr" class="w-250px"  type="text" name="place_address" value="<?php echo esc_html( $place->place_address ); ?>" onChange="updateLatLng()" /> </td></tr>
			<tr><td class="text-right mr-2"><label>Phone:</label></td>
				<td><input class="w-250px"  type="phone" name="place_phone" value="<?php echo esc_html( $place->place_phone ); ?>" placeholder="(888)888-8888" pattern="\([0-9]{3}\)[0-9]{3}-[0-9]{4}" /> </td></tr>
			<tr><td class="text-right mr-2"><label>Website:</label></td>
				<td><input class="w-250px"  type="url" name="place_website" value="<?php echo esc_html( $place->place_website ); ?>" /> </td></tr>

			<tr><td class="text-right mr-2"><label>Arrive:</label></td>
				<td><input class="w-250px"  type="date" name="place_arrive" value="<?php echo esc_html( $place->place_arrive ); ?>" /> </td></tr>
			<tr><td class="text-right mr-2"><label>Depart:</label></td>
				<td><input class="w-250px"  type="date" name="place_depart" value="<?php echo esc_html( $place->place_depart ); ?>" /> </td></tr>
			<tr><td class="text-right mr-2"><label>Pin Label:</label></td>
				<td><input class="w-100px"  type="number" name="place_label" value="<?php echo esc_html( $place->place_label ); ?>" /> </td></tr>

			<tr><td class="text-right mr-2"><label>Lattitude:</label></td>
				<td><input id="lat" class="w-100px"  type="number" name="place_lat" value="<?php echo esc_html( $place->place_lat ); ?>" step="any" /> </td></tr>
			<tr><td class="text-right mr-2"><label>Longitude:</label></td>
				<td><input id="lng" class="w-100px"  type="number" name="place_lng" value="<?php echo esc_html( $place->place_lng ); ?>" step="any"/> </td></tr>
			<tr><td class="text-right mr-2"><label>Hide Addr-Phone:</label></td>
				<td><input class="form-check-input"  type="checkbox" name="place_hide_info" value="<?php echo esc_html( $place->place_hide_info ); ?>" step="any"/> </td></tr>

			<tr><td class="text-right mr-2"><input class="btn bt-md btn-danger" style="cursor:pointer;" type="button" onclick="   window.history.go(-0);" value="Back"></td>
				<td><input class="btn bt-md btn-primary mr-auto ml-auto"  type="submit" value="Submit" name="submitPlace"></td></tr>
		</table>
		<input type="hidden" name="place_id" value="<?php echo esc_html( $place->place_id ); ?>">
		<input type="hidden" name="mynonce" value="<?php echo esc_html( $mynonce ); ?>">
	</form>
	<?php
}

add_shortcode( 'display_eds_map', 'display_eds_map_func' );
/**
 * This adds the shortcode that is used in the client to display the map.
 *
 * @return void
 */
function display_eds_map_func() {
	global $wpdb, $map_key_table;
	$keys = $wpdb->get_results(
		$wpdb->prepare(
			'SELECT * FROM %1s WHERE key_type = "map_key";',
			$map_key_table
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

	// The script element is created dynamically so I can add the "mapkey" variable to the src string.
	// Dynamic scripts behave as “async” by default. 
	document.addEventListener('DOMContentLoaded', () => {
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
	}

function populateMap(map) {
	// TODO: This call will need to go to your website, not mine. :)
	//var hostroot = 'http://localhost/wp';
	var hostroot = 'https://edandlinda.com';
	makeRequest(hostroot + '/wp-json/edsplaces/v1/places', function (data) {
		var data = JSON.parse(data.response);
		for (var i = 0; i < data.length; i++) {
			var place = data[i];
			var infowindow = new google.maps.InfoWindow();
			var content = '<div class="infoWindow"><strong><a href=\"' + place.place_website + '\" >' + place.place_name + '</a></strong><br>'
							+ "<a href='tel:" + place.place_phone +"'>" + place.place_phone + '</a>'
							+ '<p>' + place.place_info + '</p></div>' ;
			var position = new google.maps.LatLng(parseFloat(place.place_lat), parseFloat(place.place_lng));
			marker = new google.maps.Marker({
				position: position,
				map,
				title: place.place_name,
				label: place.place_label
			});

			google.maps.event.addListener(marker, 'click', (function (marker, content, infoWindow) {
				return function () {
					infowindow.setContent(content);
					infowindow.open(map, marker);
				};
			})(marker, content, infowindow));
		}
	});
}
</script>
</head>
<body>

	<div id="mapdived" style="position: unset;"></div>
	<?php
}
?>
