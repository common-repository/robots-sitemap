<?php
/**
 * This class generate an Repository's Sitemap Image item
 *
 * @package Robots_Sitemap\Core\Db\Image;
 * @since   1.0.0
 */

namespace Robots_Sitemap\Core\Db\Image;

use Robots_Sitemap\Core\Db\Post\Sitemap_Post_Item;
use WP_Post;

/**
 * Class Sitemap_Image_Item
 *
 * @package Robots_Sitemap\Core\Db\Image
 */
class Sitemap_Image_Item extends Sitemap_Post_Item {

	/**
	 * Sitemap_Image_Item constructor.
	 *
	 * @param WP_Post $post    Instance of WP_Post.
	 * @param array   $options Plugins settings.
	 */
	public function __construct( WP_Post $post, $options ) {
		parent::__construct( $post, $options );
		$this->post = $post;
	}

	/**
	 * Item of Sitemap Image
	 *
	 * @return array
	 */
	public function item_fields() {
		$images      = [];
		$post_images = [];
		$attachments = get_attached_media( 'image', $this->post->ID );
		if ( ! empty( $attachments ) ) {
			foreach ( $attachments as $attach ) {
				array_push( $post_images, $attach->ID );
			}
		}
		$thumb_id = get_post_thumbnail_id( $this->post->ID );
		if ( $thumb_id ) {
			array_push( $post_images, $thumb_id );
		}
		if ( ! empty( $post_images ) ) {
			$images[ $this->post->ID ]['url'] = get_the_permalink( $this->post->ID );
			foreach ( $post_images as $image ) {
				$img_meta = wp_prepare_attachment_for_js( $image );

				$images[ $this->post->ID ]['image'][ $image ] = [

					'src'     => wp_get_attachment_image_url( $image ),
					'title'   => $img_meta['title'],
					'caption' => $img_meta['alt'],
				];
			}
		}

		return $images;
	}

	/**
	 * Return and item of Sitemap Image Item
	 *
	 * @return array
	 */
	public function item() {

		if ( ! $this->check_index( $this->post->ID ) ) {
			return [];
		}

		return $this->item_fields();
	}

}
