<?php
/**
 * Repository of Sitemap Front.
 * Retrieves content from dabase for front page of sitemap.
 *
 * @package Robots_Sitemap\Core\Db\Image;
 * @since   1.0.0
 */

namespace Robots_Sitemap\Core\Db\Front;

use Robots_Sitemap\Core\Db\Post\Sitemap_Post_Item;
use WP_Post;

/**
 * Class Sitemap_Front_Item
 */
class Sitemap_Front_Item extends Sitemap_Post_Item {

	/**
	 * Sitemp_News_Item constructor.
	 *
	 * @param WP_Post $post    Instance of WP_Post.
	 * @param array   $options Sitemap settings.
	 */
	public function __construct( WP_Post $post, $options ) {
		parent::__construct( $post, $options );
		$this->post           = $post;
		$this->formating_meta = $this->set_default_options( $this->meta, 'home' );
	}

}
