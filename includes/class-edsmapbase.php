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
	 * WordPress database object.
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
	 * __construct
	 *
	 * @return void
	 */
	public function __construct() {

	}
}
