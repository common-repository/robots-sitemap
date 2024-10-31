<?php

namespace Robots_Sitemap\Core\Sitemap;

use Robots_Sitemap\Core\Db\Term\Sitemap_Term_Item_Repository;
use Exception;

/**
 * Class Sitemap_Taxonomy
 *
 * @package Robots_Sitemap\Front\Sitemap
 */
class Sitemap_Taxonomy extends Sitemap {

	/**
	 * Function return an array of data that can be rendered
	 *
	 * @return array
	 * @throws Exception Redis Exception.
	 */
	protected function data() {
		$repository = new Sitemap_Term_Item_Repository( $this->options );

		return $repository->get_all_terms_by_taxonomy( $this->name );
	}

}