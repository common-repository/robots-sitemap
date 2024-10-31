<?php
/**
 * Tha abstract class of sitemap. Main file.
 * It has default methods that other sitemaps must have.
 *
 * @package Robots_Sitemap\Front\Sitemap;
 */

namespace Robots_Sitemap\Core\Sitemap;

use Robots_Sitemap\Core\Db\News\Sitemap_News_Item_Repository;
use Robots_Sitemap\Core\Db\Sitemap_DB;

/**
 * Class Sitemap_News
 *
 * @package Robots_Sitemap\Front\Sitemap
 */
class Sitemap_News extends Sitemap {

	/**
	 * Sitemap_Single constructor.
	 *
	 * @param Sitemap_DB $db      Instance of Sitemap_DB.
	 * @param array      $options Custom Sitemap settings.
	 * @param string     $file    File link in cache.
	 * @param string     $type    Type of sitemap.
	 * @param string     $name    Some name.
	 */
	public function __construct( Sitemap_DB $db, $options, $file, $type, $name = '' ) {
		parent::__construct( $db, $options, $file, $type, $name );
	}

	/**
	 * Return the header of sitemap
	 *
	 * @return string
	 */
	protected function header() {
		$template = $this->template;

		$template .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:news="http://www.google.com/schemas/sitemap-news/0.9"' . $this->image . '>';

		return $template;
	}

	/**
	 * Function return an array of data that can be rendered
	 *
	 * @return array
	 */
	protected function data() {
		$repository = new Sitemap_News_Item_Repository( $this->options );
		$repository->update_news_modified_options();
		return $repository->get_posts_by_cat( $this->options['sitemapnews_cat'] );
	}

	/**
	 * Return the string of sitemap template's item
	 *
	 * @param array $item An array of sitemap template's item.
	 *
	 * @return string
	 */
	protected function item_template( $item ) {
		$xml = '<url>';

		$xml .= '<loc>' . apply_filters( 'sitemap_url', $item['url'] ) . '</loc>';
		if ( ! empty( $this->options['img_enable'] ) && ! empty( $item['img'] ) ) {
			$xml .= '<image:image><image:loc>' . $item['img'] . '</image:loc></image:image>';
		}
		$xml .= '<news:news> <news:publication> <news:name>' . $item['publication_name'] . '</news:name> <news:language>' . $item['lang'] . '</news:language> </news:publication> <news:publication_date>' . apply_filters( 'sitemap_date_format', $item['publication_date'] ) . '</news:publication_date> <news:title>' . $item['title'] . '</news:title> </news:news>';
		$xml .= '</url>';

		return $xml;
	}

}