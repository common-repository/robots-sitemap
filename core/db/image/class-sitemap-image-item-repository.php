<?php
/**
 * Repository of Sitemap Image.
 * Retrieves content from dabase for list of sitemap's images.
 *
 * @package Robots_Sitemap\Core\Db\Image;
 * @since   1.0.0
 */

namespace Robots_Sitemap\Core\Db\Image;

use Robots_Sitemap\Core\Db\Sitemap_DB;
use wpdb;

/**
 * Class Sitemap_Image_Item_Repository
 */
class Sitemap_Image_Item_Repository extends Sitemap_DB {

	/**
	 * Sitemap_Image_Item_Repository constructor.
	 *
	 * @param array $options Sitemap settings.
	 */
	public function __construct( $options ) {
		parent::__construct( $options );
	}

	/**
	 * Function return an array of images for Custom Sitemap
	 *
	 * @return array
	 */
	public function get_all_images() {

		$images = get_option( 'attachment_sitemap_items', [] );
		if ( empty( $images ) ) {
			$post_types_string = implode( ',', array_fill( 0, count( $this->get_active_post_types() ), '%s' ) );
			$sql               = "SELECT ID, post_type FROM {$this->db->posts} WHERE post_status = 'publish' AND post_type IN ({$post_types_string})  ORDER BY post_date DESC";
			$prepare_sql       = call_user_func_array(
				[
					$this->db,
					'prepare',
				],
				array_merge(
					[ $sql ],
					$this->get_active_post_types()
				)
			);
			$posts             = $this->db->get_results( $prepare_sql );

			if ( ! empty( $posts ) ) {
				foreach ( $posts as $post ) {
					$instance = new Sitemap_Image_Item( get_post( $post->ID ), $this->options );
					$item     = $instance->item();
					if ( ! empty( $item ) ) {
						$images[ $post->ID ] = $item[ $post->ID ];
					}
				}
			}

			update_option( 'attachment_sitemap_items', $images, false );
		}

		return $images;
	}

}
