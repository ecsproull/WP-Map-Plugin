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
 * Create points to be added to the map as a route.
 */
class Routes {
	/**
	 * Route segment table name.
	 *
	 * @var string
	 */
	private $route_segments_table = 'eds_map_route_segments';

	/**
	 * Route point_table
	 *
	 * @var string
	 */
	private $route_segment_points_table = 'eds_map_route_segment_points';

	/**
	 * Map keys table
	 *
	 * @var string
	 */
	private $map_keys_table = 'eds_map_keys';

	/**
	 * WordPress database object.
	 *
	 * @var mixed
	 */
	private $my_wpdb;

	/**
	 * Rapid API Key.
	 *
	 * @var mixed
	 */
	private $rapid_api_key;

	/**
	 * __construct
	 *
	 * @return void
	 */
	public function __construct() {
		global $wpdb;
		$this->my_wpdb = $wpdb;
		$keys          = $this->my_wpdb->get_results(
			$this->my_wpdb->prepare(
				'SELECT key_value FROM %1s WHERE key_type = "dir_key";',
				$this->map_keys_table
			),
			OBJECT
		);

		$this->rapid_api_key = $keys[0]->key_value;
	}

	/**
	 * Set the route points between two locations.
	 *
	 * @param  double $start_lat Start latitude.
	 * @param  double $start_lng Start longitude.
	 * @param  double $end_lat   End latitude.
	 * @param  double $end_lng   End longitude.
	 * @return void
	 */
	public function store_points( $start_lat, $start_lng, $end_lat, $end_lng ) {
		$segment_id = $start_lat . $start_lng . $end_lat . $end_lng;

		$results = $this->my_wpdb->get_results(
			$this->my_wpdb->prepare(
				'SELECT * FROM %1s WHERE segment_id = %s',
				$this->route_segments_table,
				$segment_id
			),
			OBJECT
		);

		if ( ! $results ) {
			$curl = curl_init();
			curl_setopt_array(
				$curl,
				array(
					CURLOPT_URL            => "https://trueway-directions2.p.rapidapi.com/FindDrivingPath?origin={$start_lat}%2C{$start_lng}&destination={$end_lat}%2C{$end_lng}",
					CURLOPT_RETURNTRANSFER => true,
					CURLOPT_FOLLOWLOCATION => true,
					CURLOPT_ENCODING       => '',
					CURLOPT_MAXREDIRS      => 10,
					CURLOPT_TIMEOUT        => 30,
					CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
					CURLOPT_CUSTOMREQUEST  => 'GET',
					CURLOPT_HTTPHEADER     => array(
						'X-RapidAPI-Host: trueway-directions2.p.rapidapi.com',
						'X-RapidAPI-Key: ' . $this->rapid_api_key,
					),
				)
			);

			$response    = curl_exec( $curl );
			$route       = json_decode( $response );
			$miles       = $route->route->distance / 1609.344;
			$time        = gmdate( 'H:i:s', $route->route->duration );
			$insert_data = array(
				'segment_id'         => $segment_id,
				'segment_start_lat'  => $start_lat,
				'segment_start_lng'  => $start_lng,
				'segment_end_lat'    => $end_lat,
				'segment_end_lng'    => $end_lng,
				'segment_dist_miles' => $miles,
				'segment_time'       => $time,
			);

			$rows_affected = $this->my_wpdb->insert( $this->route_segments_table, $insert_data );
			if ( 1 !== $rows_affected ) {
				echo esc_html( '<h1>Failed to insert new segment into database.</h1>' );
			}

			$coords = $route->route->geometry->coordinates;
			$count  = 0;
			$values = array();
			foreach ( $coords as $point ) {
				if ( 0 === ( $count % 20 ) ) {
					$values[] = $this->my_wpdb->prepare( '(%s, %f, %f)', $segment_id, $point[0], $point[1] );
				}

				$count++;
			}

			$query  = $this->my_wpdb->prepare( 'INSERT INTO %1s (points_segment_id, points_lat, points_lng) VALUES ', $this->route_segment_points_table );
			$query .= implode( ",\n", $values );
			$this->my_wpdb->query( $query );

			curl_close( $curl );
		}
	}

	/**
	 * Get all of the route points in the db.
	 *
	 * @return Array of points.
	 */
	public function get_route_points() {
		return $this->my_wpdb->get_results(
			$this->my_wpdb->prepare(
				'SELECT points_lat, points_lng FROM %1s',
				$this->route_segment_points_table
			),
			OBJECT
		);
	}
}
