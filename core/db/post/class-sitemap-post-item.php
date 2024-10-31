<?php
/**
 * This class generate an Repository's Sitemap Post Item.
 *
 * @since   1.0.0
 * @package Robots_Sitemap\Core\Db\Post;
 */

namespace Robots_Sitemap\Core\Db\Post;

use Robots_Sitemap\Core\Db\Sitemap_Item;
use Robots_Sitemap\Core\Libs\Vo3da_Functions;
use WP_Post;

/**
 * Class Sitemap_Post_Item
 *
 * @package Robots_Sitemap\Core\Db\Post
 */
class Sitemap_Post_Item extends Sitemap_Item {

	/**
	 * Instance of WP_Post
	 *
	 * @var WP_Post
	 */
	protected $post;
	/**
	 * Post thumbnail url
	 *
	 * @var false|string
	 */
	protected $img;
	/**
	 * Post permalink
	 *
	 * @var false|string
	 */
	protected $url;
	/**
	 * Last modify date
	 *
	 * @var false|int|string
	 */
	protected $last_modify;
	/**
	 * Post sitemap's meta values
	 *
	 * @var array
	 */
	public $meta;
	/**
	 * Formating meta for render
	 *
	 * @var array
	 */
	protected $formating_meta;

	/**
	 * Sitemap_Post_Item constructor.
	 *
	 * @param WP_Post $post Post instance.
	 * @param array   $options Sitemap settings.
	 */
	public function __construct( WP_Post $post, $options ) {
		parent::__construct( $options );
		$this->post           = $post;
		$this->img            = get_the_post_thumbnail_url( $post->ID );
		$this->url            = get_permalink( $post );
		$this->meta           = $this->get_meta_values( $post->ID );
		$this->formating_meta = $this->set_default_options( $this->meta, $post->post_type );
		$this->last_modify    = get_post_modified_time( 'Y-m-d H:i:s', 0, $post->ID );
	}

	/**
	 * Post sitemap's meta values
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return array
	 */
	protected function get_meta_values( $post_id ) {
		$meta['prioriti']    = get_post_meta( $post_id, 'prioriti', true );
		$meta['frequencies'] = get_post_meta( $post_id, 'frequencies', true );
		$meta['excludeurl']  = get_post_meta( $post_id, 'excludeurl', true );

		return $meta;
	}

	/**
	 * Check the post language
	 *
	 * @return bool
	 */
	protected function check_language() {
		return $this->wpml->check_post_language( $this->post->ID );
	}

	/**
	 * This method check if the post available for indexing.
	 *
	 * @param int $id Post ID.
	 *
	 * @return bool
	 */
	public static function check_index( $id ) {
		$excludeurl = get_post_meta( $id, 'excludeurl', true );
		if ( Vo3da_Functions::is_plugin_active( 'wordpress-seo/wp-seo.php' ) ) {
			$noindex_yoast = (int) filter_input( INPUT_POST, 'yoast_wpseo_meta-robots-noindex', FILTER_SANITIZE_NUMBER_INT );
			if ( null === $noindex_yoast || 0 === $noindex_yoast ) {
				$yoast_settings = get_post_meta( $id, '_yoast_wpseo_meta-robots-noindex', true );
				$noindex_yoast  = $yoast_settings && '2' !== $yoast_settings ? 1 : false;
			}
			$noindex_yoast = 1 === $noindex_yoast;
		}

		if ( Vo3da_Functions::is_plugin_active( 'seo-ultimate/seo-ultimate.php' ) ) {
			// TODO: Delete support Seo Ultimate.
			$noindex_ultimate = get_post_meta( $id, '_su_meta_robots_noindex', true );
		}

		return ! empty( $excludeurl ) || ( ! empty( $noindex_yoast ) || ! empty( $noindex_ultimate ) ) ? false : true;
	}

	/**
	 * Array of fields of sitemap post items
	 *
	 * @return array
	 */
	public function item_fields() {
		return [
			'img'         => $this->img,
			'url'         => esc_url( apply_filters( 'the_permalink', $this->url, $this->post ) ),
			'prioriti'    => $this->formating_meta['prioriti'],
			'frequencies' => $this->formating_meta['frequencies'],
			'last_modify' => $this->last_modify,
		];
	}

	/**
	 * Return and item of Sitemap Post Item
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
