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
class DbTables {

	/**
	 * Creates the DB tables when the the plugin is activated.
	 */
	public function create_db_tables() {
		global $wpdb;
		if ( $wpdb->get_var( 'SHOW TABLES LIKE "eds_map_keys"' ) !== 'eds_map_keys' ) {
			$wpdb->query(
				'CREATE TABLE `eds_map_keys` (
				`key_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
				`key_label` varchar(45) NOT NULL,
				`key_type` varchar(10) NOT NULL,
				`key_value` varchar(55) NOT NULL,
				PRIMARY KEY (`key_id`)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;'
			);

			$wpdb->query(
				'INSERT INTO `eds_map_keys` (
				`key_label`,
				`key_type`,
				`key_value`
				) VALUES ( "Geo:", "geo_key", " " ),
				( "Map:", "map_key", " " ),
				( "Dir:", "dir_key", " " );'
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

		if ( $wpdb->get_var( 'SHOW TABLES LIKE "eds_map_route_segment_points"' ) !== 'eds_map_route_segment_points' ) {
			$wpdb->query(
				'CREATE TABLE `eds_map_route_segment_points` (
				`points_id` int(11) NOT NULL AUTO_INCREMENT,
				`points_segment_id` varchar(45) NOT NULL,
				`points_lat` float NOT NULL,
				`points_lng` float NOT NULL,
				PRIMARY KEY (`points_id`)
			) ENGINE=InnoDB AUTO_INCREMENT=5555 DEFAULT CHARSET=utf8mb4;'
			);
		}

		if ( $wpdb->get_var( 'SHOW TABLES LIKE "eds_map_route_segments"' ) !== 'eds_map_route_segments' ) {
			$wpdb->query(
				'CREATE TABLE `eds_map_route_segments` (
				`segment_id` varchar(45) NOT NULL,
				`segment_start_lat` float NOT NULL,
				`segment_start_lng` float NOT NULL,
				`segment_end_lat` float NOT NULL,
				`segment_end_lng` float NOT NULL,
				`segment_dist_miles` decimal(10,0) NOT NULL,
				`segment_time` time NOT NULL,
				PRIMARY KEY (`segment_id`)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;'
			);
		}

	}
}
