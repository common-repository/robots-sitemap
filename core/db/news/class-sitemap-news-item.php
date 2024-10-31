<?php
/**
 * This class generate an Repository's Sitemap News Item.
 *
 * @since   1.0.0
 * @package Robots_Sitemap\Core\Db\News;
 */

namespace Robots_Sitemap\Core\Db\News;

use Robots_Sitemap\Core\Db\Post\Sitemap_Post_Item;
use WP_Post;

/**
 * Class Sitemap_News_Item
 *
 * @package Robots_Sitemap\Core\Db\News
 */
class Sitemap_News_Item extends Sitemap_Post_Item {

	/**
	 * Sitemp_News_Item constructor.
	 *
	 * @param WP_Post $post    Instance of WP_Post.
	 * @param array   $options Plugins settings.
	 */
	public function __construct( WP_Post $post, $options ) {
		parent::__construct( $post, $options );
		$this->post = $post;
	}

	/**
	 * Item of Sitemap News
	 *
	 * @return array
	 */
	public function item_fields() {
		$additional_fields = [
			'publication_name' => $this->options['sitemapnews_publication_name'] ?: 'News',
			'publication_date' => get_the_date( 'Y-m-d H:i:s', $this->post->ID ),
			'lang'             => $this->options['sitemapnews_publication_lang'] ?: 'en',
		];

		$post_data_fields = [
			'img'         => $this->img,
			'url'         => $this->url,
			'prioriti'    => $this->formating_meta['prioriti'],
			'frequencies' => $this->formating_meta['frequencies'],
			'last_modify' => $this->last_modify,
		];

		return array_merge( $post_data_fields, $additional_fields );
	}

	/**
	 * Return and item of Sitemap News Item
	 *
	 * @return array
	 */
	public function item() {
		if ( $this->check_language() ) {
			return [];
		}

		if ( ! $this->check_index( $this->post->ID ) ) {
			return [];
		}

		return $this->item_fields();
	}

}