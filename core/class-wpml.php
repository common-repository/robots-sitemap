<?php
/**
 * Class WPML for multilanguage processing.
 *
 * @since   1.0.0
 * @package Robots_Sitemap\Core;
 */

namespace Robots_Sitemap\Core;

use SitePress;

/**
 * Class WPML for multilanguage processing.
 *
 * @package Robots_Sitemap\Core
 */
class WPML {

	/**
	 * Instance of WPML.
	 *
	 * @global SitePress;
	 * @var object
	 */
	private $sitepress;

	/**
	 * Array of posts languages
	 *
	 * @var array
	 */
	public $posts_language_codes;
	/**
	 * Array of terms languages
	 *
	 * @var array
	 */
	public $terms_language_codes;

	/**
	 * WPML constructor.
	 */
	public function __construct() {
		global $sitepress;

		$this->posts_language_codes = $this->get_posts_language_codes();
		$this->terms_language_codes = $this->get_terms_language_codes();
		$this->sitepress            = $sitepress;

	}

	/**
	 * Check if WPML is active
	 *
	 * @return bool
	 */
	private function is_wpml_active() {
		return class_exists( 'SitePress' ) ? true : false;
	}

	/**
	 * Get posts language codes
	 *
	 * @return array
	 */
	public function get_posts_language_codes() {
		$language_codes = [];
		if ( $this->is_wpml_active() ) {
			global $sitepress;
			remove_filter( 'pre_get_posts', [ $sitepress, 'pre_get_posts' ] );
			$languages      = apply_filters( 'wpml_active_languages', null, 'orderby=id&order=desc' );
			$language_codes = [];
			foreach ( $languages as $language ) {
				$language_codes[] = $language['code'];
			}
		}

		return $language_codes;
	}

	/**
	 * Check post lang
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return bool
	 */
	public function check_post_language( $post_id ) {
		if ( empty( $this->posts_language_codes ) ) {
			return false;
		}
		$language      = apply_filters( 'wpml_post_language_details', null, $post_id );
		$language_code = $language['language_code'];

		if ( ! in_array( $language_code, $this->posts_language_codes, true ) ) {

			return true;
		}
		do_action( 'wpml_switch_language', $language_code );

		return false;
	}

	/**
	 * Get terms language codes
	 *
	 * @return array
	 */
	public function get_terms_language_codes() {
		$language_codes = [];
		if ( $this->is_wpml_active() ) {
			global $sitepress;
			remove_filter( 'get_terms_args', [ $sitepress, 'get_terms_args_filter' ] );
			remove_filter( 'get_term', [ $sitepress, 'get_term_adjust_id' ] );
			remove_filter( 'terms_clauses', [ $sitepress, 'terms_clauses' ] );
			$languages      = apply_filters( 'wpml_active_languages', null, 'orderby=id&order=desc' );
			$language_codes = [];
			foreach ( $languages as $language ) {
				$language_codes[] = $language['code'];
			}
		}

		return $language_codes;
	}

	/**
	 * Check term lang
	 *
	 * @param int    $term_id  Term id.
	 * @param string $taxonomy Taxonomy of term.
	 *
	 * @return bool
	 */
	public function check_term_language( $term_id, $taxonomy ) {
		if ( empty( $this->terms_language_codes ) ) {
			return false;
		}

		$language_code = apply_filters(
			'wpml_element_language_code',
			null,
			[
				'element_id'   => $term_id,
				'element_type' => $taxonomy,
			]
		);
		if ( ! in_array( $language_code, $this->terms_language_codes, true ) ) {
			return true;
		}
		do_action( 'wpml_switch_language', $language_code );

		return false;
	}

	/**
	 * Get default wpml language.
	 *
	 * @return bool|mixed
	 */
	public function get_default_language() {
		return $this->sitepress->get_default_language();
	}

	/**
	 * Reset current WPML language to default.
	 */
	public function reset_to_default_language() {
		if ( $this->is_wpml_active() ) {
			do_action( 'wpml_switch_language', $this->get_default_language() );
		}
	}

	/**
	 * Get list of categories by language
	 *
	 * @return array
	 */
	public function get_categories() {
		if ( $this->is_wpml_active() ) {
			$new_lang = 'en';
			$this->sitepress->switch_lang( $new_lang );

			return get_categories();
		} else {
			return get_categories();
		}
	}

}
