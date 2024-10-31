<?php
/**
 * This class render the image sitemap
 *
 * @package Robots_Sitemap\Front\Sitemap;
 */

namespace Robots_Sitemap\Core\Sitemap;

use Robots_Sitemap\Core\Db\Image\Sitemap_Image_Item_Repository;

/**
 * Class Sitemap_Image
 */
class Sitemap_Image extends Sitemap {

	/**
	 * Return the header of sitemap
	 *
	 * @return string
	 */
	protected function header() {
		$template = $this->template;

		$template .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">';

		return $template;
	}

	/**
	 * Return an array of images item
	 *
	 * @return array
	 */
	protected function data() {
		$repository = new Sitemap_Image_Item_Repository( $this->options );

		return $repository->get_all_images();
	}

	/**
	 * Return the string of sitemap template's item
	 *
	 * @param array $item An array of sitemap template's item.
	 *
	 * @return string
	 */
	protected function item_template( $item ) {

		$content = '<url>';

		$content .= '<loc>' . apply_filters( 'sitemap_url', $item['url'] ) . '</loc>';

		foreach ( $item['image'] as $img ) {
			$content .= '<image:image>';
			$content .= '<image:loc>' . apply_filters( 'sitemap_url', $img['src'], true ) . '</image:loc>';
			$content .= '<image:title>' . htmlspecialchars( $img['title'] ) . '</image:title>';
			$content .= '<image:caption>' . htmlspecialchars( $img['caption'] ) . '</image:caption>';
			$content .= '</image:image>';
		}

		$content .= '</url>';

		return $content;
	}

}