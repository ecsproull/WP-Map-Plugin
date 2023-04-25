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
 * Plugin Name: Map & Route
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

require_once 'includes/class-edsmapbase.php';
require 'includes/class-dbtables.php';
require 'includes/class-settings.php';
require 'includes/class-keys.php';
require 'includes/class-restapis.php';
require 'includes/class-mapshortcode.php';
require 'includes/class-place.php';
require 'includes/class-trip.php';
require 'includes/class-routes.php';

/**
 * Main map class.
 */
class EdsMappingPlugin {

	/**
	 * __construct
	 *
	 * @return void
	 */
	public function __construct() {
		register_activation_hook( __FILE__, array( new DbTables(), 'create_db_tables' ) );
		add_shortcode( 'display_eds_map', array( new MapShortcode(), 'add_shortcode' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'add_map_scripts_and_css' ) );
		add_action( 'admin_menu', array( $this, 'map_plugin_top_menu' ) );
		add_action(
			'rest_api_init',
			function () {
				$this->register_route( 'edsplaces/v1', '/places', new RestApis(), 'get_trip_points' );
			}
		);

		add_action(
			'rest_api_init',
			function () {
				$this->register_route( 'edsroute/v1', '/points', new RestApis(), 'get_route_points' );
			}
		);

		add_action(
			'rest_api_init',
			function () {
				$this->register_route( 'edsroute/v1', '/get_trip', new RestApis(), 'get_trip' );
			}
		);

		add_action(
			'rest_api_init',
			function () {
				$this->register_route( 'edsroute/v1', '/set_trip', new RestApis(), 'set_trip' );
			}
		);
	}

	/**
	 * Helper function for registering routes.
	 *
	 * @param  string $namespace The namespace.
	 * @param  string $route End of the route.
	 * @param  class  $class Instance of the calss containing the endpoint function.
	 * @param  string $func The endpoint function.
	 * @return void
	 */
	private function register_route( $namespace, $route, $class, $func ) {
		register_rest_route(
			$namespace,
			$route,
			array(
				'methods'             => 'GET',
				'callback'            => array( $class, $func ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * Adds the one and only menu item for the plugin.
	 *
	 * @return void
	 */
	public function map_plugin_top_menu() {
		add_menu_page( 'Map', 'Map & Route', 'manage_options', 'mapsettings', array( new Settings(), 'map_settings' ), plugins_url( '/WP-Map-Plugin/img/pug.png', __DIR__ ) );
		add_submenu_page( 'mapsettings', 'Map Keys', 'Map Keys', 'manage_options', 'mapkeys', array( new Keys(), 'keys_menu_handler' ) );
	}

	/**
	 * Adds styles.
	 *
	 * Adds the CSS that is used to style the admin side of the plug-in.
	 * Note the use of "admin_enqueue_scripts". It took me a while to out that
	 * that wp_enqueue_scripts adds scripts to the user side only.
	 * I like using bootstrap but this is a personal preference.
	 *
	 * @param string $host The calling host.
	 * @return void
	 */
	public function add_map_scripts_and_css( $host ) {
		if ( 'toplevel_page_mapsettings' !== $host && 'map-route_page_mapkeys' !== $host ) {
			return;
		}

		wp_register_style( 'signup_bs_style', plugin_dir_url( __FILE__ ) . 'bootstrap/css/bootstrap.min.css', array(), 1 );
		wp_enqueue_style( 'signup_bs_style' );
		wp_register_style( 'signup_style', plugin_dir_url( __FILE__ ) . 'css/style.css', array(), 1 );
		wp_enqueue_script( 'edsmap_script', plugins_url( 'js/edsmaps.js', __FILE__ ), array( 'jquery' ), '1.0.0.0', false );
		wp_enqueue_style( 'signup_style' );
	}
}

$edsmap = new EdsMappingPlugin();

