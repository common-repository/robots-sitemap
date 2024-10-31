<?php
/**
 * Repository of Sitemap Post.
 * Retrieves content from database for list of sitemap's posts.
 *
 * @package Robots_Sitemap\Core\Db\Post;
 * @since   1.0.0
 */

namespace Robots_Sitemap\Core\Db\Post;

use Robots_Sitemap\Core\Db\Front\Sitemap_Front_Item;
use Robots_Sitemap\Core\Db\Sitemap_DB;
use QM_DB;
use wpdb;

/**
 * Class Sitemap_Post_Item_Repository
 *
 * @package Robots_Sitemap\Core\Db\Post
 */
class Sitemap_Post_Item_Repository extends Sitemap_DB {

	/**
	 * Sitemap_Post_Item_Repository constructor.
	 *
	 * @param array $options Sitemap settings.
	 */
	public function __construct( $options ) {
		parent::__construct( $options );
	}

	/**
	 * Get front page id
	 *
	 * @return int
	 */
	private function get_front_page_id() {
		$home = get_option( 'page_on_front' );

		return (int) $home;
	}

	/**
	 * Return sitemap front page item.
	 *
	 * @return array
	 */
	private function get_front_page() {
		$front_page_id = $this->get_front_page_id();
		$data          = get_option( 'home_sitemap_items', [] );

		if ( empty( $data ) ) {
			if ( 0 === $front_page_id ) {
				$data[0] = $this->get_not_static_front_page();
			} else {
				$instance = new Sitemap_Front_Item( get_post( $front_page_id ), $this->options );
				$item     = $instance->item();
				if ( ! empty( $item ) ) {
					$data[ $front_page_id ] = $item;
				}
			}
			update_option( 'home_sitemap_items', $data, false );
		}

		return $data;
	}

	/**
	 * Generate data for not static front page.
	 *
	 * @return array
	 */
	private function get_not_static_front_page() {
		return [
			'img'         => false,
			'url'         => get_site_url(),
			'prioriti'    => $this->options['home_prioriti'],
			'frequencies' => $this->options['home_frequencies'],
			'last_modify' => get_lastpostmodified('server', 'post'),
		];
	}

	/**
	 * Get all posts items
	 *
	 * @return array
	 */
	public function get_all_posts() {
		$data       = [];
		$post_types = $this->get_active_post_types();
		$home       = (int) $this->options['home_enable'];
		if ( 1 === $home ) {
			$data = $this->get_front_page();
		}
		if ( ! empty( $post_types ) ) {
			foreach ( $post_types as $post_type ) {
				$cache = get_option( $post_type . '_sitemap_items', [] );
				if ( empty( $cache ) ) {
					$data += $this->get_posts_by_post_type( $post_type );
				} else {
					$data += $cache;
				}
			}
		}

		return $data;
	}

	/**
	 * Get posts by post type
	 *
	 * @param string $post_type Post type.
	 *
	 * @return array
	 */
	public function get_posts_by_post_type( $post_type ) {

		$data       = [];
		$types      = [];
		$front_page = $this->get_front_page_id();

		if ( ! empty( $post_type ) ) {

			//phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
			$sql = "SELECT ID, post_type FROM {$this->db->posts} WHERE post_status = '%s' AND post_type = '%s' AND ID NOT IN (%d)";

			$posts = $this->db->get_results(
				$this->db->prepare(
					$sql,
					'publish',
					$post_type,
					$front_page
				)
			);
			//phpcs:enable WordPress.DB.PreparedSQL.NotPrepared

			if ( ! empty( $posts ) ) {
				foreach ( $posts as $post ) {
					$post_object = get_post( $post->ID );
					$instance    = new Sitemap_Post_Item( $post_object, $this->options );
					$item        = $instance->item();
					if ( ! empty( $item ) ) {
						$data[ $post->ID ]                      = $item;
						$types[ $post_type . '_sitemap_items' ] = $data;
					}
				}
				$this->wpml->reset_to_default_language();
			}

			if ( ! empty( $types ) ) {
				foreach ( $types as $type_key => $type ) {
					update_option( $type_key, $type, false );
				}
			}
		}

		return $data;
	}

}
