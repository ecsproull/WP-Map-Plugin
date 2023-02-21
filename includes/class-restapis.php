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
	public function get_trip_points( $request ) {
		try {
			global $wpdb, $places_table;
			$trip_id = (int)$request->get_param( 'trip_id' );
			$results = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT * FROM %1$s P
					LEFT JOIN %2$s M ON P.place_id = M.tp_place_id
					WHERE  M.tp_trip_id = %3$d',
					self::PLACES_TABLE,
					self::TRIP_PLACES_TABLE,
					$trip_id
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
	public function get_route_points( $request ) {
		global $wpdb, $places_table;
		$trip_id = (int)$request->get_param( 'trip_id' );
		try {
			$router = new Routes();
			$places = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT * FROM %1$s P
					LEFT JOIN %2$s M ON P.place_id = M.tp_place_id
					WHERE  M.tp_trip_id = %3$d
					ORDER BY cast(M.tp_arrive as DateTime)',
					self::PLACES_TABLE,
					self::TRIP_PLACES_TABLE,
					$trip_id
				),
				OBJECT
			);

			if ( is_countable( $places ) ) {
				$place_count = count( $places );
				$timezone = new DateTimeZone( 'America/Phoenix' );
				$dt       = new DateTime( 'now', $timezone );
				if ( $place_count > 1 ) {
					for ( $i = 1; $i < $place_count; $i++ ) {
						$dt2 = new DateTime( $places[ $i ]->tp_arrive );
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
