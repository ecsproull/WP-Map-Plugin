<?php
/**
 * Summary
 * Map settings.
 *
 * @package   Maps
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
		global $wpdb;

		// TEMP TODO: remove this.
		/*
		$places = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT * from %1s',
				self::PLACES_TABLE
			),
			OBJECT
		); // db call ok.

		foreach ( $places as $place ) {
			$tp = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT * from %1s where tp_place_id = %d',
					self::TRIP_PLACES_TABLE,
					$place->place_id
				),
				OBJECT
			); // db call ok.

			$tp_arr['tp_arrive'] = $place->place_arrive;
			$tp_arr['tp_depart'] = $place->place_depart;
			$tp_arr['tp_label']  = $place->place_label;

			if ($tp) {
				$where          = array();
				$where['tp_id'] = intval( $tp[0]->tp_id );

				$affected_rows  = $wpdb->update(
					self::TRIP_PLACES_TABLE,
					$tp_arr,
					$where
				);  // db call ok.
			} else {
				$tp_arr['tp_trip_id'] = 1;
				$tp_arr['tp_place_id'] = $place->place_id;
				$affected_rows = $wpdb->insert( self::TRIP_PLACES_TABLE, $tp_arr );
			}
		} */

		// End TEMP.

		$post = wp_unslash( $_POST );
		if ( isset( $post['mynonce'] ) && wp_verify_nonce( $post['mynonce'], 'places' ) ) {
			unset( $post['mynonce'] );
			unset( $post['_wp_http_referer'] );

			if ( isset( $post['trip_name'] ) ) {
				$this->set_current_trip_id( (int)$post['trip_name'] );
			}

			if ( isset( $post['submitPlace'] ) ) {
				$this->submit_place( $post );
			} elseif ( isset( $post['selectPlace'] ) ) {
				$this->load_place_selection();
			} elseif ( isset( $post['editPlace'] ) ) {
				$this->create_or_edit_place( $post['editPlace'] );
			} elseif ( isset( $post['deletePlace'] ) ) {
				$this->update_map_message( $this->delete_place( $post['deletePlace'] ) );
			} elseif ( isset( $post['trip_name'] ) ) {
				$current_trip_id = intval( $post['trip_name'] );
				$this->load_place_selection();
			}
		} else {
			$this->create_or_edit_place( -1 );
		}
	}

	/**
	 * This is the set up for C (create) & U (update) in CRUD
	 *
	 * @param mixed $place_id The id of the palce to edit.
	 *
	 * @return void
	 */
	private function create_or_edit_place( $place_id ) {
		global $wpdb;
		if ( -1 === $place_id ) {
			$this->create_place_form( new Place(), );
		} else {
			$place = $this->get_place( $place_id );

			$this->create_place_form( $place );
		}
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

		$tp_array                = array();
		$tp_array['tp_arrive']   = $post['tp_arrive'];
		$tp_array['tp_depart']   = $post['tp_depart'];
		$tp_array['tp_trip_id']  = $post['trip_name'];
		$tp_array['tp_place_id'] = $post['place_id'];
		$tp_where                = array();
		$tp_where['tp_id']       = $post['tp_id'];
		unset( $post['tp_arrive'] );
		unset( $post['tp_depart'] );
		unset( $post['tp_label'] );
		unset( $post['trip_name'] );
		$tp_dates = $post['tp_dates'];
		unset( $post['tp_id'] );
		unset( $post['tp_dates'] );

		if ( '' === $post['place_id'] ) {
			unset( $post['place_id'] );
			$affected_rows = $wpdb->insert( 'eds_map_places', $post ); // db call ok.

			if ( 1 === $affected_rows ) {
				$tp_array['tp_place_id'] = $wpdb->insert_id;
				$affected_rows           = $wpdb->insert( 'eds_map_trip_places', $tp_array );  // db call ok.
			}
		} else {
			$place_where             = array();
			$place_where['place_id'] = $post['place_id'];
			unset( $post['place_id'] );
			$wpdb->update( 'eds_map_places', $post, $place_where );

			if ( 'new' === $tp_dates ) {
				$affected_rows = $wpdb->insert( 'eds_map_trip_places', $tp_array ); // db call ok.
			} else {
				$affected_rows = $wpdb->update( 'eds_map_trip_places', $tp_array, $tp_where ); // db call ok.
			}
		}

		if ( $affected_rows > 0 ) {
			$this->update_labels();
		}

		$this->update_map_message( $affected_rows );
	}

	/**
	 * update_labels in sequential order.
	 *
	 * @return void
	 */
	private function update_labels() {
		global $wpdb;
		$places     = $this->get_trip_places( $this->get_current_trip_id() );
		$array_size = count( $places );
		for ( $i = 0; $i < $array_size; $i++ ) {
			if ( (int) $places[ $i ]->tp_label !== $i + 1 ) {
				$tp_array             = array();
				$tp_array['tp_label'] = $i;
				$tp_where             = array();
				$tp_where['tp_id']    = $places[ $i ]->tp_id;
				$wpdb->update( 'eds_map_trip_places', $tp_array, $tp_where );
			}
		}
	}

	/**
	 * This is the R part where we retrive our places to edit them.
	 *
	 * @return void
	 */
	private function load_place_selection() {
		$trip          = new Trip();
		$trip->places  = $this->get_trip_places( $this->get_current_trip_id() );
		$trip->trip_id = $this->get_current_trip_id();
		$this->create_place_selection_form( $trip );
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
				<h2> No change. </h2>
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
	 * @return void
	 */
	private function create_place_selection_form( $trip ) {
		?>
		<form name="place_select_form"  method="POST">
			<div id="content" class="container">
				<table class="mb-100px table table-striped mr-auto ml-auto">
					<tr>
						<td class="text-left mr-2"><label>Trip Name:</label></td>
						<td>
							<?php $this->trip_select( $trip->trip_id ); ?>
						</td>
					</tr>
					<?php
					foreach ( $trip->places as $place ) {
						?>
						<tr><td class="text-left"> <?php echo esc_html( $place->tp_label ) . '.) ' . esc_html( $place->place_name ) . ' ' . esc_html( $place->tp_arrive ); ?></td>
							<td> <input class="submitbutton editImage" type="submit" name="editPlace" value="<?php echo esc_html( $place->place_id ); ?>"> </td>
							<td> <input class="submitbutton deleteImage" type="submit" name="deletePlace" value="<?php echo esc_html( $place->place_id ); ?>"> 
						</tr>
						<?php
					}
					?>
				</table>
			</div>
			<?php wp_nonce_field( 'places', 'mynonce' ); ?>
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
	 * @return void
	 */
	private function create_place_form( $place ) {
		global $wpdb;
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
						name="place_info"><?php echo esc_html( $place->place_info ); ?></textarea>
					</td>
				</tr>
				<tr><td class="text-right mr-2"><label>Icon Name:</label></td>
					<td><select id="icon" name="place_icon_type" value="<?php echo esc_html( $place->place_icon_type ); ?>">
							<option value="0" 
							<?php
							if ( '0' === $place->place_icon_type ) {
								echo 'selected';}
							?>
							>Rv Park</option>
							<option value="1" 
							<?php
							if ( '1' === $place->place_icon_type ) {
								echo 'selected';}
							?>
							>House</option>
							<option value="2" 
							<?php
							if ( '2' === $place->place_icon_type ) {
								echo 'selected';}
							?>
							>Rest Stop</option>
							</select><td></tr>
				<tr><td class="text-right mr-2"><label>Hide Addr-Phone:</label></td>
					<td><input class="form-check-input mt-2"  type="checkbox" name="place_hide_info" value="<?php echo esc_html( $place->place_hide_info ); ?>" step="any"/> </td></tr>
				<tr><td class="text-right mr-2"><label>Address: </label></td>
					<td><input id="addr" class="w-250px"  type="text" name="place_address" value="<?php echo esc_html( $place->place_address ); ?>" onChange="updateLatLng()" /> </td></tr>
				<tr><td class="text-right mr-2"><label>Lattitude:</label></td>
					<td><input id="lat" class="w-100px"  type="number" name="place_lat" value="<?php echo esc_html( $place->place_lat ); ?>" step="any" /> </td></tr>
				<tr><td class="text-right mr-2"><label>Longitude:</label></td>
					<td><input id="lng" class="w-100px"  type="number" name="place_lng" value="<?php echo esc_html( $place->place_lng ); ?>" step="any"/> </td></tr>
				<tr><td class="text-right mr-2"><label>Phone:</label></td>
					<td><input id="phone_input" class="w-250px"  type="phone" name="place_phone" value="<?php echo esc_html( $place->place_phone ); ?>" placeholder="(888)888-8888" pattern="\([0-9]{3}\)[0-9]{3}-[0-9]{4}" /> </td></tr>
				<tr><td class="text-right mr-2"><label>Website:</label></td>
					<td><input class="w-250px"  type="url" name="place_website" value="<?php echo esc_html( $place->place_website ); ?>" /> </td></tr>
				<tr><td class="text-right mr-2">Dates:</td><td><input class="w-250px"  type="radio" name="tp_dates" value="new" <?php echo ( null === $place->tp_id ? 'checked' : '' ); ?> /> <span>New</span>
					<input class="w-250px ml-2"  type="radio" name="tp_dates" value="update" <?php echo ( null === $place->tp_id ? '' : 'checked' ); ?> /><span>Update</span> </td></tr>
				<tr>
					<td class="text-right mr-2"><label>Trip Name:</label></td>
					<td>
						<?php
						$this->trip_select( $this->get_trip_id( $place->place_id ) );
						?>
					</td>
				</tr>
				<tr><td class="text-right mr-2"><label>Arrive:</label></td>
					<td><input class="w-250px"  type="date" name="tp_arrive" value="<?php echo esc_html( $place->tp_arrive ); ?>" /> </td></tr>
				<tr><td class="text-right mr-2"><label>Depart:</label></td>
					<td><input class="w-250px"  type="date" name="tp_depart" value="<?php echo esc_html( $place->tp_depart ); ?>" /> </td></tr>
				<tr><td class="text-right mr-2"><label>Pin Label:</label></td>
					<td><input disabled class="w-100px"  type="number" name="tp_label" value="<?php echo esc_html( $place->tp_label ); ?>" /> </td></tr>

				<tr><td class="text-right mr-2"><input class="btn bt-md btn-danger" style="cursor:pointer;" type="button" onclick="   window.history.go(-0);" value="Back"></td>
					<td><input class="btn bt-md btn-primary mr-auto ml-auto"  type="submit" value="Submit" name="submitPlace"></td></tr>
			</table>
			<input type="hidden" name="place_id" value="<?php echo esc_html( $place->place_id ); ?>">
			<input type="hidden" name="tp_id" value="<?php echo esc_html( $place->tp_id ); ?>">
			<?php wp_nonce_field( 'places', 'mynonce' ); ?>
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
		?>
		<script>
			function makeRequest(url, callback) {
				var request;
				if (window.XMLHttpRequest) {
					request = new XMLHttpRequest(); 
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
				var url = "https://maps.googleapis.com/maps/api/geocode/json?address=" + address.value.replaceAll(' ', '+') + "&key=" + '<?php echo esc_html( $this->get_map_key( 'geo_key' ) ); ?>';
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

