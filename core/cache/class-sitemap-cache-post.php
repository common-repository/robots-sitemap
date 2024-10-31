<?php
/**
 * Post caching
 *
 * @since   1.0.0
 * @package Robots_Sitemap\Core\Cache;
 */

namespace Robots_Sitemap\Core\Cache;

use Robots_Sitemap\Core\Db\Post\Sitemap_Post_Item;

/**
 * Class Sitemap_Cache_Post
 *
 * @package Robots_Sitemap\Core\Cache
 */
class Sitemap_Cache_Post extends Sitemap_Cache {

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
	private $post_type;
	/**
	 * Plugins options
	 *
	 * @var array
	 */
	protected $options;
	/**
	 * Cache method to be executed.
	 *
	 * @var string
	 */
	private $method;

	/**
	 * Sitemap_Cache_Post constructor.
	 *
	 * @param int    $id        Post ID.
	 * @param string $post_type Post type.
	 * @param string $method    Cache method to be executed.
	 * @param array  $options   Plugin options.
	 */
	public function __construct( $id, $post_type, $method, $options ) {
		$this->id        = $id;
		$this->post_type = $post_type;
		$this->options   = $options;
		$this->method    = $method;
		parent::__construct( $id, $options );
		$this->repository_item = new Sitemap_Post_Item( get_post( $this->id ), $this->options );
	}

	/**
	 * Get post type cache
	 *
	 * @return array
	 */
	public function get_cache_data() {
		return get_option( $this->post_type . '_sitemap_items', [] );
	}

	/**
	 * Set cache by post type
	 *
	 * @param array $cache Cache data.
	 */
	public function set_cache_data( $cache ) {
		update_option( $this->post_type . '_sitemap_items', $cache, false );
		if ( 'post' === $this->post_type ) {
			$this->update_news_cache();
		}
		if ( $this->options['img_enable'] || $this->options['sitemapimg_enable'] ) {
			$this->update_attachment_cache();
		}
	}

	/**
	 * Whether caching is allowed for current post
	 *
	 * @return bool
	 */
	public function check_index() {
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

	/**
	 * Update news cache
	 */
	private function update_news_cache() {
		$categories = wp_get_post_categories( $this->id );
		if ( ! empty( $categories ) ) {
			foreach ( $categories as $category ) {
				$news_cache = new Sitemap_Cache_News( $this->id, $category, $this->options );
				$news_cache->execute( $this->method );
			}
		}
	}

	/**
	 * Update attachment cache
	 */
	private function update_attachment_cache() {
		$attachment_ache = new Sitemap_Cache_Attachment( $this->id, $this->options );
		$attachment_ache->execute( $this->method );
	}

}
