<?php
/**
 * This class work with WordPress DB
 * Get results for Sitemap
 *
 * @package Robots_Sitemap\Front\Sitemap
 */

namespace Robots_Sitemap\Core\Db;

use Robots_Sitemap\Core\Db\Post\Sitemap_Post_Item;
use Robots_Sitemap\Core\Db\Term\Sitemap_Term_Item;
use Robots_Sitemap\Core\WPML;
use wpdb;

/**
 * Class DB
 *
 * @package Robots_Sitemap\Front\Sitemap
 */
class Sitemap_DB {

	/**
	 * This property contains a options from DB
	 *
	 * @var array $options
	 */
	public $options;
	/**
	 * WordPress database
	 *
	 * @var wpdb $db
	 */
	public $db;
	/**
	 * WPML
	 *
	 * @var WPML
	 */
	public $wpml;
	/**
	 * Cache timeout
	 *
	 * @var int
	 */
	public $cache_time;

	/**
	 * DB constructor.
	 *
	 * @param array $options plugin settings.
	 */
	public function __construct( $options ) {
		global $wpdb;
		$this->db         = $wpdb;
		$this->options    = $options;
		$this->cache_time = 60 * 60 * 6;
		$this->wpml       = new WPML();
	}

	/**
	 * Get list of post_types for queries
	 *
	 * @return array
	 */
	public function get_active_post_types() {
		$active_post_types = [];
		$post_types        = $this->get_post_types();
		foreach ( $post_types as $post_type ) {
			if ( ! empty( $this->options[ $post_type . '_enable' ] ) ) {
				$active_post_types[] = $post_type;
			}
		}

		return $active_post_types;
	}

	/**
	 * Get arrays of public post types
	 *
	 * @param bool $labels Return post types with labels.
	 *
	 * @return array
	 */
	public function get_post_types( $labels = false ) {
		if ( false === $labels ) {
			$post_types = get_post_types( [ 'public' => true ] );
			unset( $post_types['attachment'] );
		} else {
			$post_types_obj = get_post_types( [ 'public' => true ], 'objects' );
			unset( $post_types_obj['attachment'] );
			$post_types = [];
			foreach ( $post_types_obj as $post_type ) {
				$post_types[ $post_type->name ]['label'] = $post_type->label;
			}
		}

		return $post_types;
	}

	/**
	 * Get array of public taxonomies
	 *
	 * @param bool $labels Return taxonomies with labels.
	 *
	 * @return array
	 */
	public function get_taxonomies( $labels = false ) {
		if ( false === $labels ) {
			$taxonomies = get_taxonomies( [ 'public' => true ] );
			unset( $taxonomies['post_format'] );
		} else {
			$taxonomies_obj = get_taxonomies( [ 'public' => true ], 'objects' );
			unset( $taxonomies_obj['post_format'] );
			$taxonomies = [];
			foreach ( $taxonomies_obj as $taxonomy ) {
				$taxonomies[ $taxonomy->name ]['label'] = $taxonomy->label;
			}
		}

		return $taxonomies;
	}

	/**
	 * Get list of taxonomies for queries
	 *
	 * @return array
	 */
	public function get_active_taxonomies() {
		$active_taxonomies = [];
		$taxonomies        = $this->get_taxonomies();
		foreach ( $taxonomies as $taxonomy ) {
			if ( ! empty( $this->options[ $taxonomy . '_enable' ] ) ) {
				$active_taxonomies[] = $taxonomy;
			}
		}

		return $active_taxonomies;
	}

	/**
	 * Check post type
	 *
	 * @param string $post_type Post type string that must be checked.
	 *
	 * @return bool
	 */
	public function check_post_type( $post_type ) {

		$sql = "SELECT ID FROM {$this->db->posts} WHERE post_type = '{$post_type}' AND post_status = 'publish'";

		if ( 'page' === $post_type ) {
			$front_id = intval( get_option( 'page_on_front' ) );

			$sql .= ' AND ID != ' . $front_id;
		}
		//phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$posts = $this->db->get_results( $this->db->prepare( $sql, [] ) );
		if ( ! empty( $posts ) ) {
			foreach ( $posts as $post ) {
				if ( Sitemap_Post_Item::check_index( $post->ID ) ) {
					unset( $posts );

					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Check taxonomy
	 *
	 * @param string $taxonomy Taxonomy that must be checked.
	 *
	 * @return bool
	 */
	private function check_taxonomy( $taxonomy ) {

		//phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$terms = $this->db->get_results(
			$this->db->prepare(
				"SELECT term_id FROM {$this->db->term_taxonomy} WHERE taxonomy = %s",
				$taxonomy
			)
		);
		//phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		if ( ! empty( $terms ) ) {
			foreach ( $terms as $term ) {
				if ( Sitemap_Term_Item::check_index( $term->term_id, $taxonomy ) ) {
					unset( $terms );

					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Return the date of last modified post
	 *
	 * @param string $type Type of sitemap.
	 *
	 * @return string
	 */
	private function get_last_modify( $type ) {

		$last_modify = '';
		$posts       = [];
		$terms       = [];
		$post_types  = $this->get_active_post_types();
		$taxonomies  = $this->get_active_taxonomies();

		if ( 'home' === $type ) {
			if ( get_option( 'show_on_front' ) === 'page' ) {
				$front_id    = get_option( 'page_on_front' );
				$last_modify = get_post_modified_time( 'c', false, $front_id );
			} else {
				$sql = "SELECT ID, post_modified FROM {$this->db->posts} WHERE post_type LIKE 'post' AND post_status LIKE 'publish' ORDER BY post_modified DESC";

				//phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$posts = $this->db->get_results( $this->db->prepare( $sql, '' ) );

				foreach ( $posts as $post ) {
					if ( Sitemap_Post_Item::check_index( $post->ID ) ) {
						$last_modify = $post->post_modified;
						break;
					}
				}
			}
		} elseif ( in_array( $type, $post_types, true ) ) {
			$sql = "SELECT ID, post_modified FROM {$this->db->posts} WHERE post_type = '%s' AND post_status = 'publish' ORDER BY post_modified DESC";

			//phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$posts = $this->db->get_results( $this->db->prepare( $sql, $type ) );

			foreach ( $posts as $post ) {
				if ( Sitemap_Post_Item::check_index( $post->ID ) ) {
					$last_modify = $post->post_modified;
					break;
				}
			}
		} elseif ( in_array( $type, $taxonomies, true ) ) {
			$sql = "SELECT {$this->db->termmeta}.term_id, {$this->db->termmeta}.meta_value
					FROM {$this->db->term_taxonomy}
					INNER JOIN {$this->db->termmeta}
					ON {$this->db->term_taxonomy}.term_id = '{$this->db->termmeta}.term_id'
					WHERE {$this->db->term_taxonomy}.taxonomy = '{$type}' AND '{$this->db->termmeta}.meta_key'
					LIKE 'term_last_mod' ORDER BY {$this->db->termmeta}.meta_value DESC";

			//phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$terms = $this->db->get_results( $this->db->prepare( $sql, [] ) );

			if ( $terms ) {
				foreach ( $terms as $term ) {
					if ( Sitemap_Term_Item::check_index( $term->term_id, $type ) ) {
						$last_modify = $term->meta_value;
						break;
					}
				}
			}

			//phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
			$last_modify = ! empty( $last_modify ) ? $last_modify : date( 'Y-m-d' ) . ' 01:00:00';
		}

		return $last_modify;

	}

	/**
	 * Get items for main sitemap
	 *
	 * @return array
	 */
	public function get_main_items() {
		$items = [];
		if ( ! empty( $this->options['home_enable'] ) ) {
			$items[] = [
				'last_modify' => get_post_modified_time( 'c', false, get_option( 'page_on_front' ) ),
			];
		}

		$post_types = $this->get_active_post_types();
		if ( ! empty( $post_types ) ) {
			foreach ( $post_types as $post_type ) {
				if ( ! empty( $this->options[ $post_type . '_enable' ] ) && $this->check_post_type( $post_type ) ) {
					$items[] = [
						'type'        => $post_type,
						'last_modify' => $this->get_last_modify( $post_type ),
					];
				}
			}
		}

		$taxonomies = $this->get_active_taxonomies();
		if ( ! empty( $taxonomies ) ) {
			foreach ( $taxonomies as $taxonomy ) {
				if ( ! empty( $this->options[ $taxonomy . '_enable' ] ) && $this->check_taxonomy( $taxonomy ) ) {
					$items[] = [
						'type'        => $taxonomy,
						'last_modify' => $this->get_last_modify( $taxonomy ),
					];
				}
			}
		}

		if ( $this->options['sitemapimg_enable'] && ! empty( $post_types ) ) {
			$sitemapimages = false;
			foreach ( $post_types as $post_type ) {
				if ( ! empty( $this->options[ $post_type . '_enable' ] ) && $this->check_post_type( $post_type ) ) {
					$sitemapimages = true;
					break;
				}
			}
			if ( $sitemapimages ) {
				$items[] = [
					'type'        => 'sitemapimages',
					'last_modify' => $this->get_last_modify( 'attachment' ),
				];
			}
		}
		if ( $this->options['sitemapnews_enable'] ) {
			$items[] = [
				'type'        => 'sitemap-news',
				'last_modify' => $this->get_last_modify( 'attachment' ),
			];
		}

		return $items;
	}

}
