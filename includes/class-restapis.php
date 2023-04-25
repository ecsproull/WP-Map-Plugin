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
	 * @param mixed $request Rest Api Request.
	 * @return array The results of the query.
	 */
	public function get_trip_points( $request ) {
		try {
			$trip_id = $this->get_current_trip_id();
			return $this->get_trip_places( $trip_id );
		} catch ( Exception $e ) {
			return $e->getMessage();
		}
	}

	/**
	 * The actual function that does the work of retrieving the route points.
	 *
	 * @param mixed $request Rest Api Request.
	 * @return array The results of the query.
	 */
	public function get_route_points( $request ) {
		$trip_id = $this->get_current_trip_id();
		try {
			$router = new Routes();
			$places = $this->get_trip_places( $trip_id );

			if ( is_countable( $places ) ) {
				$place_count = count( $places );
				$timezone    = new DateTimeZone( 'America/Phoenix' );
				$dt          = new DateTime( 'now', $timezone );
				if ( $place_count > 1 ) {
					for ( $i = 1; $i < $place_count; $i++ ) {
						$dt2 = new DateTime( $places[ $i ]->tp_arrive );
						if ( $dt > $dt2 ) {
							$router->store_points(
								$places[ $i - 1 ]->place_lat,
								$places[ $i - 1 ]->place_lng,
								$places[ $i ]->place_lat,
								$places[ $i ]->place_lng,
								$trip_id,
								$places[ $i - 1 ]->place_name,
								$places[ $i ]->place_name
							);
						}
					}
				}
			}

			return $router->get_route_points( $trip_id );

		} catch ( Exception $e ) {
			return $e->getMessage();
		}
	}

	/**
	 * The actual function that does the work of retrieving the points.
	 *
	 * @param mixed $request Rest Api Request.
	 * @return array The results of the query.
	 */
	public function get_trip( $request ) {
		try {
			return $this->get_current_trip_id();
		} catch ( Exception $e ) {
			return $e->getMessage();
		}
	}

	/**
	 * The actual function that does the work of retrieving the points.
	 *
	 * @param mixed $request Rest Api Request.
	 * @return array The results of the query.
	 */
	public function set_trip( $request ) {
		try {
			$trip_id = (int) $request->get_param( 'trip_id' );
			$this->set_current_trip_id( $trip_id );
			return;
		} catch ( Exception $e ) {
			return $e->getMessage();
		}
	}
}
