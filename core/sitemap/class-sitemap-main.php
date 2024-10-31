<?php
/**
 * This class render the main sitemap
 *
 * @package Robots_Sitemap\Front\Sitemap;
 */

namespace Robots_Sitemap\Core\Sitemap;

/**
 * Class Sitemap_Main
 */
class Sitemap_Main extends Sitemap {

	/**
	 * Return the header of sitemap
	 *
	 * @return string
	 */
	protected function header() {
		$template = $this->template;

		$template .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

		return $template;
	}

	/**
	 * Contains a footer of sitemap
	 *
	 * @return string
	 */
	protected function footer() {
		return '</sitemapindex>';
	}

	/**
	 * Function return an array of data that can be rendered
	 *
	 * @return array
	 */
	protected function data() {
		return $this->sitemap_db->get_main_items();
	}

	/**
	 * Return the string of sitemap template's item
	 *
	 * @param array $item An array of sitemap template's item.
	 *
	 * @return string
	 */
	protected function item_template( $item ) {

		if ( ! empty( $item['type'] ) ) {
			if ( 'sitemap-news' === $item['type'] || 'sitemapimages' === $item['type'] ) {
				$url = $item['type'];
			} else {
				$url = $item['type'] .= '-sitemap';
			}
			$url = get_site_url() . '/' . $item['type'] . '.xml';
		} else {
			return false;
		}
		$xml = '<sitemap>';

		$xml .= '<loc>' . $url . '</loc>';
		$xml .= '<lastmod>' . apply_filters( 'sitemap_date_format', $item['last_modify'] ) . '</lastmod>';
		$xml .= '</sitemap>';

		return $xml;
	}

}