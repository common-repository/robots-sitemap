<?php
/**
 * This class generate an Repository's Sitemap Term Item.
 *
 * @since   1.0.0
 * @package Robots_Sitemap\Core\Db\Post;
 */

namespace Robots_Sitemap\Core\Db\Term;

use Robots_Sitemap\Core\Db\Sitemap_Item;
use Robots_Sitemap\Core\Libs\Vo3da_Functions;
use DateTime;
use Exception;
use WP_Error;
use WP_Term;

/**
 * Class Sitemap_Term_Item
 *
 * @package Robots_Sitemap\Core\Db\Term
 */
class Sitemap_Term_Item extends Sitemap_Item {

	/**
	 * WP_Term instance
	 *
	 * @var WP_Term
	 */
	private $term;
	/**
	 * Term permalink
	 *
	 * @var string|WP_Error
	 */
	private $url;
	/**
	 * Date of last modify term
	 *
	 * @var mixed|string
	 */
	private $last_modify;
	/**
	 * Term sitemap's meta values
	 *
	 * @var array
	 */
	public $meta;
	/**
	 * Formatted meta for render
	 *
	 * @var array
	 */
	private $formating_meta;

	/**
	 * Sitemap_Term_Item constructor.
	 *
	 * @param WP_Term $term WP_Term instance.
	 * @param array   $options Sitemap settings.
	 *
	 * @throws Exception Exception.
	 */
	public function __construct( WP_Term $term, $options ) {
		parent::__construct( $options );
		$this->term           = $term;
		$this->options        = $options;
		$this->url            = $this->get_term_link();
		$this->meta           = $this->get_meta_values( $term->term_id );
		$this->formating_meta = $this->set_default_options( $this->meta, $term->taxonomy );
		$this->last_modify    = $this->get_last_modify( $term );

	}

	/**
	 * Return term link
	 *
	 * @return string|string[]|WP_Error
	 */
	private function get_term_link() {
		$term_link = get_term_link( $this->term );
		if ( Vo3da_Functions::is_plugin_active( 'wp-no-base-permalink/wp-no-base-permalink.php' ) && ! empty( $wp_no_base[ 'disabled-' . $this->term->taxonomy . '-base' ] ) ) {
			$term_link = str_replace( $this->term->taxonomy . '/', '', $term_link );
		}

		return $term_link;
	}

	/**
	 * Get term meta settings.
	 *
	 * @param int $term_id Term id.
	 *
	 * @return array
	 */
	protected function get_meta_values( $term_id ) {
		$meta['prioriti']    = get_term_meta( $term_id, 'prioriti', true );
		$meta['frequencies'] = get_term_meta( $term_id, 'frequencies', true );
		$meta['excludeurl']  = get_term_meta( $term_id, 'excludeurl', true );

		return $meta;
	}

	/**
	 * Check the term language
	 *
	 * @return bool
	 */
	protected function check_language() {
		return $this->wpml->check_term_language( $this->term->term_id, $this->term->taxonomy );
	}

	/**
	 * Get date of term last modify
	 *
	 * @param WP_Term $term WP_Term instance.
	 *
	 * @return string
	 * @throws Exception Exception.
	 */
	private function get_last_modify( WP_Term $term ) {
		$last_modify = get_term_meta( $term->term_id, 'term_last_mod', true );
		if ( empty( $last_modify ) || strlen( $last_modify ) < 10 ) {
			$term_last_mod = new DateTime();
			update_term_meta( $term->term_id, 'term_last_mod', $term_last_mod->format( 'Y-m-d H:i:s' ) );
			$last_modify = $term_last_mod->format( 'Y-m-d H:i:s' );
		}

		return $last_modify;

	}

	/**
	 * Check term index
	 *
	 * @param int    $term_id Terms id that must be checked.
	 *
	 * @param string $taxonomy Taxonomy name.
	 *
	 * @return bool
	 */
	public static function check_index( $term_id, $taxonomy ) {

		$exclude = get_option( $taxonomy . '_' . $term_id . '_excludeurl' );
		if ( empty( $exclude ) ) {
			$exclude = get_term_meta( $term_id, 'excludeurl', true );
		}
		if ( Vo3da_Functions::is_plugin_active( 'wordpress-seo/wp-seo.php' ) ) {
			$yseo = get_option( 'wpseo_taxonomy_meta' );
			if ( $yseo ) {
				$yseo = $yseo[ $taxonomy ][ $term_id ]['wpseo_noindex'] ? $yseo[ $taxonomy ][ $term_id ]['wpseo_noindex'] : '';
			} else {
				$yseo = true;
			}
		}

		// TODO: Delete support Seo Ultimate.
		if ( Vo3da_Functions::is_plugin_active( 'seo-ultimate/seo-ultimate.php' ) ) {
			$seou = get_option( 'seo_ultimate_module_meta' );
			if ( $seou && ! empty( $seou['taxonomy_meta_robots_noindex'][ $term_id ] ) ) {
				$seou = $seou['taxonomy_meta_robots_noindex'][ $term_id ] ? $yseo[ $taxonomy ][ $term_id ]['wpseo_noindex'] : '';
			} else {
				$seou = true;
			}
		}

		if ( ! empty( $exclude ) || (! empty( $yseo ) && 'noindex' === $yseo) ) {
			return false;
		} elseif ( ! empty( $exclude ) || (! empty( $seou )) && '1' === $seou ) {
			return false;
		} else {
			return true;
		}

	}

	/**
	 * Array of fields of sitemap term items
	 *
	 * @return array
	 */
	public function item_fields() {
		return [
			'img'         => '',
			'url'         => $this->url,
			'prioriti'    => $this->formating_meta['prioriti'],
			'frequencies' => $this->formating_meta['frequencies'],
			'last_modify' => $this->last_modify,
		];
	}

	/**
	 * Return and item of Sitemap Term Item
	 *
	 * @return array
	 */
	public function item() {

		if ( $this->check_language() ) {
			return [];
		}

		if ( ! $this->check_index( $this->term->term_id, $this->term->taxonomy ) ) {
			return [];
		}

		return $this->item_fields();
	}

}
