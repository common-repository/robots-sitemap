<?php
/**
 * This class render the Post Type sitemap
 *
 * @package Customn_Sitemap\Front\Sitemap;
 */

namespace Robots_Sitemap\Core\Sitemap;

use Robots_Sitemap\Core\Db\Post\Sitemap_Post_Item_Repository;

/**
 * Class Sitemap_Post_Type
 */
class Sitemap_Post_Type extends Sitemap {

	/**
	 * Function return an array of data that can be rendered
	 *
	 * @return array
	 */
	protected function data() {
		$repository = new Sitemap_Post_Item_Repository( $this->options );

		return $repository->get_posts_by_post_type( $this->name );
	}

}