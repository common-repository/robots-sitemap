<?php
/**
 * News caching
 *
 * @since   1.0.0
 * @package Robots_Sitemap\Core\Cache;
 */

namespace Robots_Sitemap\Core\Cache;

use Robots_Sitemap\Core\Db\News\Sitemap_News_Item;

/**
 * Class Sitemap_Cache_News
 *
 * @package Robots_Sitemap\Core\Cache
 */
class Sitemap_Cache_News extends Sitemap_Cache {

	/**
	 * Post ID
	 *
	 * @var int
	 */
	private $id;
	/**
	 * Current post type
	 *
	 * @var string
	 */
	private $category;
	/**
	 * Plugins options
	 *
	 * @var array
	 */
	protected $options;

	/**
	 * Sitemap_Cache_Post constructor.
	 *
	 * @param int    $id       Post ID.
	 * @param string $category Post category.
	 * @param array  $options  Plugin options.
	 */
	public function __construct( $id, $category, $options ) {
		$this->id       = $id;
		$this->category = $category;
		$this->options  = $options;
		parent::__construct( $id, $options );
		$this->repository_item = new Sitemap_News_Item( get_post( $this->id ), $this->options );
	}

	/**
	 * Get category cache
	 *
	 * @return array
	 */
	protected function get_cache_data() {
		$category_cache = wp_cache_get( 'category_' . $this->category . '_sitemap_items', 'custom_sitemap' );

		return ! is_array( $category_cache ) ? [] : $category_cache;

	}

	/**
	 * Set cache by category
	 *
	 * @param array $cache Cache data.
	 */
	protected function set_cache_data( $cache ) {
		wp_cache_set( 'category_' . $this->category . '_sitemap_items', $cache, 'custom_sitemap', $this->factory->sitemap_db->cache_time );
	}

	/**
	 * Whether caching is allowed for current post
	 *
	 * @return bool
	 */
	protected function check_index() {
		return $this->repository_item->check_index( $this->id );
	}

	/**
	 * Formatting data for cache
	 *
	 * @return array
	 */
	public function prepare_data() {
		return $this->repository_item->item_fields();
	}

}