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
class EdsMapBase {

	/**
	 * __construct
	 *
	 * @return void
	 */
	public function __construct() {
	}

	/**
	 * Set_current_trip_id
	 *
	 * @param int $trip_id Trip id.
	 * @return void
	 */
	protected function set_current_trip_id( $trip_id ) {
		global $wpdb;
		$tripnames = $wpdb->get_results( 'SELECT * from eds_map_trips', OBJECT );  // db call ok.
		foreach ( $tripnames as $name ) {
			if ( $trip_id === (int) $name->trip_id ) {
				$wpdb->update( 'eds_map_trips', array( 'trip_current' => 1 ), array( 'trip_id' => $name->trip_id ) );
			} else {
				$wpdb->update( 'eds_map_trips', array( 'trip_current' => 0 ), array( 'trip_id' => $name->trip_id ) );
			}
		}
	}

	/**
	 * Get_current_trip_id
	 *
	 * @return The current trip id.
	 */
	protected function get_current_trip_id() {
		global $wpdb;
		$tripnames = $wpdb->get_results( 'SELECT * from eds_map_trips', OBJECT );  // db call ok.
		foreach ( $tripnames as $name ) {
			if ( intval( $name->trip_current ) ) {
				return $name->trip_id;
			}
		}
	}

	/**
	 * Get the set of places for a trip ordered by date
	 *
	 * @param  int $trip_id Trip id.
	 * @return The list of places for the specified trip
	 */
	protected function get_trip_places( $trip_id ) {
		global $wpdb;
		$places = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT * FROM eds_map_places P
				LEFT JOIN eds_map_trip_places M ON P.place_id = M.tp_place_id
				WHERE  M.tp_trip_id = %d
				ORDER BY cast(M.tp_arrive as DateTime)',
				$trip_id
			),
			OBJECT
		);

		return $places;
	}

	/**
	 * Get the set of routing points for a trip. These re mapping points
	 * used to draw the route on the map
	 *
	 * @param  int $trip_id Trip id.
	 * @return The list of points for the specified trip
	 */
	protected function get_trip_points( $trip_id ) {
		global $wpdb;
		$results = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT * FROM eds_map_places P
				LEFT JOIN eds_map_trip_places M ON P.place_id = M.tp_place_id
				WHERE  M.tp_trip_id = %d',
				$trip_id
			),
			OBJECT
		);

		return $results;
	}

	/**
	 * Get single place by place id
	 *
	 * @param  int $place_id Place id.
	 * @return Single place
	 */
	protected function get_place( $place_id ) {
		global $wpdb;
		$places = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT * from eds_map_places P
				LEFT JOIN eds_map_trip_places M ON P.place_id = M.tp_place_id
				where P.place_id = %d',
				$place_id
			),
			OBJECT
		); // db call ok.

		return $places[0];
	}

	/**
	 * Delete a single place.
	 *
	 * @param  int $place_id Place id to delete.
	 * @return Number of rows deleted.
	 */
	protected function delete_place( $place_id ) {
		global $wpdb;
		return $wpdb->delete( 'eds_map_places', array( 'place_id' => $place_id ) );  // db call ok.
	}

	/**
	 * Get the trip name for a place Id
	 *
	 * @param int $place_id Place Id.
	 * @return string Place name.
	 */
	protected function get_trip_id( $place_id ) {
		if ( ! $place_id ) {
			return '';
		}
		global $wpdb;
		$trip_name = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT T.trip_id 
				FROM eds_map_trips T
				LEFT JOIN eds_map_trip_places P
				ON T.trip_id = P.tp_trip_id
				where P.tp_place_id = %d',
				$place_id,
			),
			OBJECT
		);  // db call ok.

		return $trip_name[0]->trip_id;
	}

	/**
	 * Get Mapping key.
	 *
	 * @param string $key_type The type of key to get.
	 * @return The key.
	 */
	protected function get_map_key( $key_type ) {
		global $wpdb;
		$keys = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT * FROM eds_map_keys WHERE key_type = %s',
				$key_type
			),
			OBJECT
		);  // db call ok.

		return $keys[0]->key_value;
	}

	/**
	 * Get the Mapping key.
	 *
	 * @return The key.
	 */
	protected function get_map_keys() {
		global $wpdb;
		return $wpdb->get_results( 'SELECT * FROM eds_map_keys', OBJECT );
	}

	/**
	 * Inserts a trip select.
	 *
	 * @param string $trip_id Trip id to select in the select box.
	 */
	protected function trip_select( $trip_id ) {
		global $wpdb;
		$tripnames = $wpdb->get_results( 'SELECT * from eds_map_trips', OBJECT );  // db call ok.
		?>
		<select id="trip" name="trip_name" value=""  title="trip_name">
		<?php
		foreach ( $tripnames as $name ) {
			?>
			<option value="<?php echo esc_html( $name->trip_id ); ?>"
				<?php
				if ( intval( $name->trip_current ) ) {
					echo esc_html( 'selected' );
				}
				?>
				>
				<?php echo esc_html( $name->trip_name ); ?>
			</option>
			<?php
		}
		?>
		</select>
		<?php
	}
}
