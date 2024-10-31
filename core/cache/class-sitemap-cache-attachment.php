<?php
/**
 * Attachment caching
 *
 * @since   1.0.0
 * @package Robots_Sitemap\Core\Cache;
 */

namespace Robots_Sitemap\Core\Cache;

use Robots_Sitemap\Core\Db\Image\Sitemap_Image_Item;

/**
 * Class Sitemap_Cache_Attachment
 *
 * @package Robots_Sitemap\Core\Cache
 */
class Sitemap_Cache_Attachment extends Sitemap_Cache {

	/**
	 * Post ID
	 *
	 * @var int
	 */
	private $id;
	/**
	 * Plugins options
	 *
	 * @var array
	 */
	protected $options;

	/**
	 * Sitemap_Cache_Post constructor.
	 *
	 * @param int   $id      Post ID.
	 * @param array $options Plugin options.
	 */
	public function __construct( $id, $options ) {
		$this->id      = $id;
		$this->options = $options;
		parent::__construct( $id, $options );
		$this->repository_item = new Sitemap_Image_Item( get_post( $id ), $options );
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
			$this->set_cache_data( $this->prepare_data() );
		} else {
			$this->delete( $cache );
		}
	}

	/**
	 * Get attachment cache
	 *
	 * @return array
	 */
	protected function get_cache_data() {
		return get_option( 'attachment_sitemap_items', [] );
	}

	/**
	 * Set cache attachment
	 *
	 * @param array $cache Cache data.
	 */
	protected function set_cache_data( $cache ) {
		update_option( 'attachment_sitemap_items', $cache, false );
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
	protected function prepare_data() {
		return $this->repository_item->item_fields();
	}

}
