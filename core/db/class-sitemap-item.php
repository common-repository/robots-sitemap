<?php
/**
 * Default abstract class for Sitemap Items.
 * Contains mandatory and general methods.
 *
 * @since   1.0.0
 * @package Robots_Sitemap\Core\Db;
 */

namespace Robots_Sitemap\Core\Db;

use Robots_Sitemap\Core\WPML;

/**
 * Class Sitemap_Item
 *
 * @package Robots_Sitemap\Core\Db
 */
abstract class Sitemap_Item {

	/**
	 * Sitemap settings
	 *
	 * @var array
	 */
	protected $options;
	/**
	 * Instance of WPML Sitemap Class
	 *
	 * @var WPML
	 */
	protected $wpml;

	/**
	 * Sitemap_Item constructor.
	 *
	 * @param array $options Sitemap settings.
	 */
	public function __construct( $options ) {
		$this->options = $options;
		$this->wpml    = new WPML();
	}

	/**
	 * This methods return default meta data.
	 *
	 * @param array  $meta          Current meta data.
	 * @param string $group_options Sitemap group name.
	 *
	 * @return array
	 */
	protected function set_default_options( $meta, $group_options ) {
		if ( empty( $meta['prioriti'] ) ) {
			$meta['prioriti'] = $this->options[ $group_options . '_prioriti' ];
		}
		if ( empty( $meta['frequencies'] ) ) {
			$meta['frequencies'] = $this->options[ $group_options . '_frequencies' ];
		}
		unset( $meta['excludeurl'] );

		return $meta;
	}

	/**
	 * Method for checking item and its language.
	 *
	 * @return bool
	 */
	abstract protected function check_language();

	/**
	 * Method that return full information about sitemap item for render.
	 *
	 * @return mixed
	 */
	abstract protected function item();

}
