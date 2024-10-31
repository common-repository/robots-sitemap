<?php
/**
 * Repository of Sitemap Post.
 * Retrieves content from database for list of sitemap's news.
 *
 * @package Robots_Sitemap\Core\Db\News;
 * @since   1.0.0
 */

namespace Robots_Sitemap\Core\Db\News;

use Robots_Sitemap\Core\Db\Sitemap_DB;
use wpdb;

/**
 * Class Sitemap_News_Item_Repository
 *
 * @package Robots_Sitemap\Core\Db\News
 */
class Sitemap_News_Item_Repository extends Sitemap_DB {

	/**
	 * Sitemap_News_Item_Repository constructor.
	 *
	 * @param array $options Sitemap settings.
	 */
	public function __construct( $options ) {
		parent::__construct( $options );
	}

	/**
	 * Update options 'news_last_modified'
	 *
	 * @return void
	 */
	public function update_news_modified_options() {
		$get_oldest_news                     = $this->get_oldest_news();
		$this->options['news_last_modified'] = $get_oldest_news;
		update_option( 'custom_sitemap_options', $this->options );
	}

	/**
	 * Return the oldest post
	 *
	 * @return int
	 */
	private function get_oldest_news() {
		$object    = $this->options['sitemapnews_cat'];
		$cat_child = get_categories(
			[
				'child_of'   => $object,
				'hide_empty' => false,
			]
		);
		$object    = [ $object ];
		if ( ! empty( $cat_child ) ) {
			foreach ( $cat_child as $cat ) {
				array_push( $object, $cat->term_id );
			}
		}

		$cats        = implode( ',', array_fill( 0, count( $object ), '%s' ) );
		$sql         = "
						SELECT TIMESTAMP(p.post_date) FROM {$this->db->posts} AS p
						LEFT JOIN {$this->db->term_relationships} AS tr ON (p.ID = tr.object_id)
						LEFT JOIN {$this->db->term_taxonomy} AS tt ON (tr.term_taxonomy_id = tt.term_taxonomy_id)
						LEFT JOIN {$this->db->terms} AS t ON (tt.term_id = t.term_id)
						WHERE p.post_status = 'publish'
						AND p.post_type = 'post'
						AND tt.taxonomy = 'category'
						AND t.term_id
						IN  ({$cats})
						ORDER BY p.post_date DESC
						";
		$prepare_sql = call_user_func_array(
			[
				$this->db,
				'prepare',
			],
			array_merge(
				[ $sql ],
				$object
			)
		);

		$post_publish_time = $this->db->get_var( $prepare_sql );
		$timestamp         = date( 'U', strtotime( $post_publish_time ) );

		return $timestamp;
	}

	/**
	 * Posts for by cat fot sitemap render
	 *
	 * @param int $cat Id of category.
	 *
	 * @return array
	 */
	public function get_posts_by_cat( $cat ) {
		$data = wp_cache_get( 'category_' . $cat . '_sitemap_items', 'custom_sitemap' );
		$data = ! is_array( $data ) ? [] : $data;
		if ( empty( $data ) ) {

			$sql = "
					SELECT ID FROM {$this->db->posts} as p
					LEFT JOIN {$this->db->term_relationships} AS tr ON (p.ID = tr.object_id)
					LEFT JOIN {$this->db->term_taxonomy} AS tt ON (tr.term_taxonomy_id = tt.term_taxonomy_id)
					LEFT JOIN {$this->db->terms} AS t ON (tt.term_id = t.term_id)
					WHERE p.post_status = 'publish'
					AND t.term_id = '%d'
					AND p.post_date > DATE_ADD(CURRENT_DATE(), INTERVAL -2 DAY)
					ORDER BY p.post_date DESC
					";

			$posts = $this->db->get_results( $this->db->prepare( $sql, $cat ) );

			if ( ! empty( $posts ) ) {
				foreach ( $posts as $post ) {
					$post_object = get_post( $post->ID );
					$instance    = new Sitemap_News_Item( $post_object, $this->options );
					$item        = $instance->item();
					if ( ! empty( $item ) ) {
						$data[ $post->ID ] = $item;
					}
				}
				$this->wpml->reset_to_default_language();
			}

			if ( ! empty( $data ) ) {
				wp_cache_set( 'category_' . $cat . '_sitemap_items', $data, 'custom_sitemap', $this->cache_time );
			}
		}

		return $data;
	}

}