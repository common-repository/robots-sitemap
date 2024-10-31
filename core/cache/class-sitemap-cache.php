<?php
/**
 * Main class for Sitemap Cache.
 *
 * @since   1.0.0
 * @package Robots_Sitemap\Core\Cache;
 */

namespace Robots_Sitemap\Core\Cache;

use Robots_Sitemap\Core\Db\Post\Sitemap_Post_Item;
use Robots_Sitemap\Core\Sitemap\Sitemap_Factory;

/**
 * Class Sitemap_Cache
 *
 * @package Robots_Sitemap\Core\Cache
 */
abstract class Sitemap_Cache {

	/**
	 * ID of Post or Term
	 *
	 * @var int
	 */
	private $id;
	/**
	 * Plugin settings
	 *
	 * @var array
	 */
	protected $options;
	/**
	 * Instance of Sitemap_Factory
	 *
	 * @var Sitemap_Factory
	 */
	protected $factory;
	/**
	 * Cache data
	 *
	 * @var array
	 */
	private $cache;
	/**
	 * Instance of sitemap Post Item
	 *
	 * @var Sitemap_Post_Item
	 */
	protected $repository_item;

	/**
	 * Sitemap_Cache constructor.
	 *
	 * @param int   $id Cache item id.
	 * @param array $options Plugins settings.
	 */
	public function __construct( $id, $options ) {
		$this->id      = $id;
		$this->options = $options;
		$this->factory = new Sitemap_Factory( $this->options );
		$this->cache   = $this->get_cache_data();
	}

	/**
	 * Update existing cache
	 */
	protected function update() {
		$cache = $this->get_cache_data();
		if ( $this->check_index() ) {
			if ( empty( $cache ) ) {
				$this->factory->generate();
			}
			$cache[ $this->id ] = $this->prepare_data();
			$this->set_cache_data( $cache );
		} else {
			$this->delete( $cache );
		}
	}

	/**
	 * Delete existing cache
	 *
	 * @param array $cache Cache object.
	 */
	protected function delete( $cache ) {
		if ( ! empty( $cache[ $this->id ] ) ) {
			unset( $cache[ $this->id ] );
		}

		$this->set_cache_data( $cache );
	}

	/**
	 * The main function that operates on methods
	 *
	 * @param string $method Method that must be executed.
	 */
	public function execute( $method ) {
		if ( 'update' === $method ) {
			$this->update();
		} elseif ( 'delete' === $method ) {
			$this->delete( $this->cache );
		}
	}

	/**
	 * Get cache data from options or wp_cache or etc.
	 *
	 * @return array
	 */
	abstract protected function get_cache_data();

	/**
	 * Set cache data to options or wp_cache or etc.
	 *
	 * @param array $cache Data that must be cached.
	 */
	abstract protected function set_cache_data( $cache );

	/**
	 * Whether caching is allowed for current post/term.
	 *
	 * @return bool
	 */
	abstract protected function check_index();

	/**
	 * Formatting data for cache.
	 *
	 * @return array
	 */
	abstract protected function prepare_data();

}