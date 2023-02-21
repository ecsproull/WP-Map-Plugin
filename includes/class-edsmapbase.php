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
	 * Name of the map keys.
	 *
	 * @var mixed
	 */
	protected const MAP_KEY_TABLE = 'eds_map_keys';

	/**
	 * Name of the place table.
	 *
	 * @var mixed
	 */
	protected const PLACES_TABLE = 'eds_map_places';

	/**
	 * Name of the trips table.
	 *
	 * @var mixed
	 */
	protected const TRIPS_TABLE = 'eds_map_trips';

	/**
	 * Name of the trip to place table.
	 *
	 * @var mixed
	 */
	protected const TRIP_PLACES_TABLE = 'eds_map_trip_places';

	/**
	 * __construct
	 *
	 * @return void
	 */
	public function __construct() {
	}

	/**
	 * Inserts a trip select.
	 *
	 * @param string $trip_name Trip name to select in the select box.
	 */
	protected function trip_select( $trip_id ) {
		global $wpdb;
		?>
		<select id="trip" name="trip_name" value="<?php echo esc_html( $trip_name ); ?>">
		<?php
		$tripnames = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT * from %1s',
				self::TRIPS_TABLE
			),
			OBJECT
		);  // db call ok.

		foreach ( $tripnames as $name ) {
			?>
			<option value="<?php echo esc_html( $name->trip_id ); ?>"
				<?php
				if ( $trip_id === $name->trip_id ) {
					echo esc_html( 'selected' );}
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
