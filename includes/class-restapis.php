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
class RestApis extends EdsMapBase {

	/**
	 * The actual function that does the work of retrieving the points.
	 *
	 * @return array The results of the query.
	 */
	public function get_trip_points() {
		try {
			global $wpdb, $places_table;
			$results = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT * FROM %1s',
					self::PLACES_TABLE
				),
				OBJECT
			);

			return $results;

		} catch ( Exception $e ) {
			return $e->getMessage();
		}
	}

	/**
	 * The actual function that does the work of retrieving the route points.
	 *
	 * @return array The results of the query.
	 */
	public function get_route_points() {
		global $wpdb, $places_table;
		try {
			$router = new Routes();
			$places = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT * from %1s ORDER BY place_label;',
					self::PLACES_TABLE,
				),
				OBJECT
			);

			if ( is_countable( $places ) ) {
				$place_count = count( $places );
				$timezone = new DateTimeZone( 'America/Phoenix' );
				$dt       = new DateTime( 'now', $timezone );
				if ( $place_count > 1 ) {
					for ( $i = 1; $i < $place_count; $i++ ) {
						$dt2 = new DateTime( $places[ $i ]->place_arrive );
						if ( $dt > $dt2 ) {
							$router->store_points( $places[ $i - 1 ]->place_lat, $places[ $i - 1 ]->place_lng, $places[ $i ]->place_lat, $places[ $i ]->place_lng );
						}
					}
				}
			}

			return $router->get_route_points();

		} catch ( Exception $e ) {
			return $e->getMessage();
		}
	}
}
