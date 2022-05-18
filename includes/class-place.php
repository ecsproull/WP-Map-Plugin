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
 * The add place form is also used to update a place.
 * Thus it expects a Place to edit. When adding a place
 * we can use this class to create a blank place.
 */
class Place {
	/**
	 * Place id.
	 *
	 * @var place_id.
	 */
	public $place_id;

	/**
	 * Place name.
	 *
	 * @var place_name.
	 */
	public $place_name;

	/**
	 * Information generally copied from the places website.
	 *
	 * @var place_info.
	 */
	public $place_info;

	/**
	 * Lattitude.
	 *
	 * @var place_lat.
	 */
	public $place_lat;

	/**
	 * Longitude.
	 *
	 * @var place_lng.
	 */
	public $place_lng;

	/**
	 * Icon type can be, house, rv park, restarea.
	 *
	 * @var place_icon_type.
	 */
	public $place_icon_type;

	/**
	 * Address.
	 *
	 * @var place_address.
	 */
	public $place_address;

	/**
	 * Phone number.
	 *
	 * @var place_phone.
	 */
	public $place_phone;

	/**
	 * Website url.
	 *
	 * @var place_website.
	 */
	public $place_website;

	/**
	 * Arrival date.
	 *
	 * @var place_arrive.
	 */
	public $place_arrive;

	/**
	 * Departure date.
	 *
	 * @var place_depart.
	 */
	public $place_depart;

	/**
	 * Hide place info from public view.
	 *
	 * @var place_hide_info.
	 */
	public $place_hide_info;

	/**
	 * A number or leter lable to be displayed on the pushpin.
	 *
	 * @var place_label.
	 */
	public $place_label;
}
