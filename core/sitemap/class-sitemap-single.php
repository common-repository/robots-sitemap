<?php
/**
 * Render the sitemap for single type
 *
 * @package Robots_Sitemap\Front\Sitemap
 */

namespace Robots_Sitemap\Core\Sitemap;

use Robots_Sitemap\Core\Db\Sitemap_DB;
use Robots_Sitemap\Core\Db\Post\Sitemap_Post_Item_Repository;
use Robots_Sitemap\Core\Db\Term\Sitemap_Term_Item_Repository;
use Exception;

/**
 * Class Sitemap_Single
 *
 * @package Robots_Sitemap\Front\Sitemap
 */
class Sitemap_Single extends Sitemap {

	/**
	 * Function return array of data that can be rendered
	 *
	 * @param Sitemap_DB $sitemap_db WPDB object.
	 * @param array      $options    This property contains a options from DB.
	 * @param string     $file       Path to file.
	 * @param string     $type       Type of sitemap.
	 * @param string     $name       Taxonomy name.
	 */
	public function __construct( Sitemap_DB $sitemap_db, $options, $file, $type, $name = '' ) {
		parent::__construct( $sitemap_db, $options, $file, $type, $name );
	}

	/**
	 * Array of data that can be rendered
	 *
	 * @return array
	 * @throws Exception Throw an Exception.
	 */
	public function data() {
		$post_repository = new Sitemap_Post_Item_Repository( $this->options );
		$term_repository = new Sitemap_Term_Item_Repository( $this->options );

		return array_merge( $post_repository->get_all_posts(), $term_repository->get_all_terms() );
	}

}