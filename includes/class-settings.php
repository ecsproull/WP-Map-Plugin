<?php
/**
 * Summary
 * Map settings.
 *
 * @package     Maps
 * @author      Edward Sproull
 * @copyright   You have the right to copy
 * @license     GPL-2.0+
 */

/**
 * Manages the map settings including adding places to the map.
 */
class Settings extends EdsMapBase {

	/**
	 * __construct
	 *
	 * @return void
	 */
	public function __construct() {
		add_action( 'admin_print_scripts', array( $this, 'add_lat_lng_script' ) );
	}

	/**
	 * Static entry point for the map settings.
	 * This delegates all the real work to helper functions.
	 * Even at the simplist level our plug-in needs to be able to be CRUD complete
	 *
	 * @return void
	 */
	public function map_settings() {
		$mynonce = wp_create_nonce( 'my-nonce' );
		$post    = wp_unslash( $_POST );

		if ( isset( $post['mynonce'] ) ) {
			$my_nonce = $post['mynonce'];
			if ( wp_verify_nonce( $my_nonce, 'my-nonce' ) ) {
				unset( $post['mynonce'] );
				if ( isset( $post['submitPlace'] ) ) {
					$this->submit_place( $post );
				} elseif ( isset( $post['selectPlace'] ) ) {
					$this->load_place_selection( $my_nonce );
				} elseif ( isset( $post['editPlace'] ) ) {
					$this->create_or_edit_place( $post['editPlace'], $my_nonce );
				} elseif ( isset( $post['deletePlace'] ) ) {
					$this->delete_place( $post );
				}
			}
		} else {
			$this->create_or_edit_place( -1, $mynonce );
		}
	}

	/**
	 * This is the set up for C (create) & U (update) in CRUD
	 *
	 * @param mixed $place_id The id of the palce to edit.
	 * @param int   $mynonce The security token.
	 *
	 * @return void
	 */
	private function create_or_edit_place( $place_id, $mynonce ) {
		global $wpdb;
		if ( -1 === $place_id ) {
			$this->create_place_form( new Place(), $mynonce );
		} else {
			$places = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT * from %1s where place_id = %s',
					self::PLACES_TABLE,
					$place_id
				),
				OBJECT
			);

			$this->create_place_form( $places[0], $mynonce );
		}
	}


	/**
	 * This is the D (delete) part of CRUD
	 *
	 * @param array $post Data returned from the form.
	 * @return void
	 */
	private function delete_place( $post ) {
		global $wpdb;
		$affected_rows = $wpdb->delete( self::PLACES_TABLE, array( 'place_id' => $post['deletePlace'] ) );
		$this->update_map_message( $affected_rows );
	}

	/**
	 * This is the U (update) part of CRUD
	 *
	 * @param array $post Data returned from the form.
	 * @return void
	 */
	private function submit_place( $post ) {
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
			$affected_rows = $wpdb->insert( self::PLACES_TABLE, $post );
		} else {
			$where             = array();
			$where['place_id'] = $post['place_id'];
			unset( $post['place_id'] );
			$affected_rows = $wpdb->update(
				self::PLACES_TABLE,
				$post,
				$where
			);
		}

		$this->update_map_message( $affected_rows );
	}

	/**
	 * Helper function to get places.
	 *
	 * @return List of places from the database.
	 */
	private function get_places() {
		global $wpdb;
		$places = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT * from %1s ORDER BY place_label;',
				self::PLACES_TABLE,
			),
			OBJECT
		);

		return $places;
	}

	/**
	 * This is the R part where we retrive our places to edit them.
	 *
	 * @param int $mynonce Security token.
	 * @return void
	 */
	private function load_place_selection( $mynonce ) {
		$places = $this->get_places();
		$this->create_place_selection_form( $places, $mynonce );
	}


	/**
	 * Display after an edit.
	 *
	 * @param  mixed $rows_updated The number of rows updated.
	 * @return void
	 */
	private function update_map_message( $rows_updated ) {
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
	private function create_place_selection_form( $places, $mynonce ) {
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
	private function create_place_form( $place, $mynonce ) {
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

	/**
	 * Google map points are located by latitude and longitude. The point of these JS functions are to
	 * take an address and retrieve the lat and lng for that address. The makeRequest is a generic html request function.
	 * I'm pretty sure I borrowed this from the web somewhere so if you perfer another way of doing this, have at it.
	 * But wait there are more JS functions below why are these up here? Great question! These are used on the
	 * administrative side and the ones below belong to the client side. More on that in the next comment.
	 */
	public function add_lat_lng_script() {
		global $wpdb;
		$keys = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT * FROM %1s WHERE key_type = "geo_key";',
				self::MAP_KEY_TABLE
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
}

