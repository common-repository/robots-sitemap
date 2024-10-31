<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @since      1.0.0
 *
 * @package    Robots_Sitemap\Front
 */

namespace Robots_Sitemap\Front;

use Robots_Sitemap\Core\Cache\Sitemap_Cache_Factory;
use Robots_Sitemap\Core\Data_Builder;
use Robots_Sitemap\Core\Robots\Robots_File;
use Robots_Sitemap\Core\Libs\Vo3da_Functions;
use DateTime;
use Exception;
use WP_Post;

/**
 * Class Front
 *
 * @package Robots_Sitemap\Front
 */
class Front {

	/**
	 * Contains some variables and objects for the plugin work.
	 *
	 * @var Data_Builder
	 */
	private $builder;

	/**
	 * Current domain
	 *
	 * @var string
	 */
	private $current_domain;

	/**
	 * Front constructor.
	 *
	 * @param Data_Builder $builder Contains some variables and objects for the plugin work.
	 */
	public function __construct( Data_Builder $builder ) {
		$this->builder        = $builder;
		$mirrors              = Vo3da_Functions::get_mirrors( get_current_blog_id() );
		$this->current_domain = str_replace( [ 'http://', 'https://' ], '', get_site_url( get_current_blog_id() ) );
	}

	/**
	 * Frontend hooks
	 */
	public function hooks() {
		add_action( 'wp', [ $this, 'generate_sitemap' ] );

		/**
		 * Update sitemap
		 */

		add_filter( 'wp_sitemaps_enabled', '__return_false' );

		add_action( 'created_term', [ $this, 'update_term' ], 10, 3 );
		add_action( 'delete_term', [ $this, 'delete_term' ], 10, 3 );
		add_action( 'edited_term', [ $this, 'update_term' ], 10, 3 );

		add_action( 'save_post', [ $this, 'update_post_type' ], 10, 2 );
		add_action( 'delete_post', [ $this, 'delete_post_type' ] );
		add_action( 'wp_trash_post', [ $this, 'delete_post_type' ] );

		add_action( 'edit_attachment', [ $this, 'update_attachment' ] );
		add_action( 'delete_attachment', [ $this, 'delete_attachment' ] );

		add_action( 'update_option_custom_sitemap_options', [ $this, 'update_all_sitemaps' ], 10, 2 );
		add_action( 'update_option_seo_ultimate_module_meta', [ $this, 'update_all_sitemaps' ], 10, 2 );

		add_action( 'wp_ajax_vo3da_sitemap_clear_cache', [ $this, 'ajax_clear_cache' ] );
		add_filter( 'sitemap_date_format', [ $this, 'date_format' ] );

		/**
		 * Robots hooks
		 */

		$disable_robots = isset( $this->builder->robots_options[ $this->current_domain ]['disable_robots'] ) ? $this->builder->robots_options[ $this->current_domain ]['disable_robots'] : false;

		if ( ! $disable_robots ) {
			add_action( 'wp', [ $this, 'robots_protection' ] );
			add_filter( 'robots_txt', [ $this, 'show_robots' ], 10, 2 );
		}

	}

	/**
	 * Update images sitemap
	 *
	 * @param int $post_id Post ID.
	 *
	 * @throws Exception Exception.
	 */
	public function update_attachment( $post_id ) {
		$this->update_attachment_cache( $post_id );

		$this->builder->sitemap_file_manager->clear( [ 'sitemapimages.xml' ] );
	}

	/**
	 * Delete images sitemap
	 *
	 * @param int $post_id Post ID.
	 *
	 * @throws Exception Exception.
	 */
	public function delete_attachment( $post_id ) {
		$this->update_attachment_cache( $post_id, 'delete' );

		$this->builder->sitemap_file_manager->clear( [ 'sitemapimages.xml' ] );
	}

	/**
	 * Update post type sitemap
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Instance of WP_Post.
	 *
	 * @throws Exception Exception.
	 * @since 1.0.0
	 * add_action('update_post', 'update_post_type');
	 */
	public function update_post_type( $post_id, WP_Post $post ) {

		if ( 'publish' === $post->post_status ) {
			$this->update_post_cache( $post_id );
			$this->builder->sitemap_file_manager->clear(
				[
					get_post_type( $post_id ) . '-sitemap.xml',
					'sitemap-news.xml',
				]
			);
		}
	}

	/**
	 * Delete post type sitemap
	 *
	 * @param int $post_id Post ID.
	 *
	 * @throws Exception Exception.
	 * @since 1.0.0
	 *
	 * add_action('delete_post', 'delete_post_type');
	 * add_action('wp_trash_post', 'delete_post_type');
	 */
	public function delete_post_type( $post_id ) {
		$this->update_post_cache( $post_id, 'delete' );
		$this->builder->sitemap_file_manager->clear(
			[
				get_post_type( $post_id ) . '-sitemap.xml',
				'sitemap-news.xml',
			]
		);
	}

	/**
	 * Update term hook.
	 *
	 * @param int    $term_id  Term ID.
	 * @param int    $tt_id    Term taxonomy ID.
	 * @param string $taxonomy Taxonomy.
	 *
	 * @throws Exception Invalid DateTime.
	 * @since 1.0.0
	 *
	 * add_action('edit_attachment', 'update_term');
	 */
	public function update_term( $term_id, $tt_id, $taxonomy ) {
		$this->update_term_cache( $term_id, $taxonomy );
		$this->builder->sitemap_file_manager->clear( [ $taxonomy . '-sitemap.xml' ] );
	}

	/**
	 * Delete term hook
	 *
	 * @param int    $term_id  Term ID.
	 * @param int    $tt_id    Term taxonomy ID.
	 * @param string $taxonomy Taxonomy.
	 *
	 * @throws Exception Exception.
	 *
	 * @since 1.0.0
	 * add_action('delete_term', 'delete_term');
	 */
	public function delete_term( $term_id, $tt_id, $taxonomy ) {
		$this->update_term_cache( $term_id, $taxonomy, 'delete' );
		$this->builder->sitemap_file_manager->clear( [ $taxonomy . '-sitemap.xml' ] );
	}

	/**
	 * Update post cache
	 *
	 * @param int    $post_id Post ID.
	 * @param string $action  Action.
	 *
	 * @throws Exception Exception.
	 */
	private function update_post_cache( $post_id, $action = 'update' ) {

		$post_type     = get_post_type( $post_id );
		$cache_factory = new Sitemap_Cache_Factory( $post_id, $post_type, 'post', $action, $this->builder->options );
		$cache_factory->init();

	}

	/**
	 * Update attachment cache
	 *
	 * @param int    $post_id Post ID.
	 * @param string $action  Action.
	 *
	 * @throws Exception Exception.
	 */
	private function update_attachment_cache( $post_id, $action = 'update' ) {
		$cache_factory = new Sitemap_Cache_Factory( $post_id, 'attachment', 'attachment', $action, $this->builder->options );
		$cache_factory->init();
	}

	/**
	 * Update term cache
	 *
	 * @param int    $term_id  Term ID.
	 * @param string $taxonomy Taxonomy of term.
	 * @param string $action   Action.
	 *
	 * @throws Exception Exception.
	 */
	private function update_term_cache( $term_id, $taxonomy, $action = 'update' ) {
		$cache_factory = new Sitemap_Cache_Factory( $term_id, $taxonomy, 'term', $action, $this->builder->options );
		$cache_factory->init();
	}

	/**
	 * Delete all sitemaps
	 *
	 * @param mixed $old_value Current sitemap settings.
	 * @param mixed $options   New sitemap settings.
	 *
	 * @since 1.0.0
	 *
	 * add_action('update_option_custom_sitemap_options', 'update_all_sitemaps');
	 * add_action('update_option_seo_ultimate_module_meta', 'update_all_sitemaps');
	 * add_action('wp_ajax_clear_options_cache', 'update_all_sitemaps');
	 */
	public function update_all_sitemaps( $old_value, $options ) {
		$options_fields = array_diff_assoc( (array) $options, (array) $old_value );
		if ( empty( $options_fields ) ) {
			$options_fields = [];
		}
		$sitemaps = $this->search_update_options( $options_fields );
		$this->builder->sitemap_file_manager->clear_options_cache( $sitemaps );
		$this->builder->sitemap_file_manager->clear( null, true );

	}

	/**
	 * Ajax handler for clear cache.
	 */
	public function ajax_clear_cache() {
		$this->builder->sitemap_file_manager->clear( null, true );
		wp_send_json_success();
	}

	/**
	 * Return formatted date
	 *
	 * @param string $last_modify Time that must be changed.
	 *
	 * @return string
	 * @throws Exception Exception.
	 * @since 1.0.0.
	 *
	 * add_filters('sitemap_date_format', 'date_format');
	 */
	public function date_format( $last_modify ) {

		$last_modify = new DateTime( $last_modify );

		return ( ! empty( $this->builder->options['sitemap_dateformat'] ) && 'long' === $this->builder->options['sitemap_dateformat'] ) ? $last_modify->format( 'c' ) :
			$last_modify->format( 'Y-m-d' );
	}

	/**
	 * Returns the names of the sitemaps to clear the cache while saving options
	 *
	 * @param array $options Updated options.
	 *
	 * @return array
	 */
	private function search_update_options( $options ) {
		$data     = [];
		$fields   = array_keys( $options );
		$settings = [
			'_prioriti',
			'_frequencies',
			'_enable',
		];

		foreach ( $fields as $field ) {
			foreach ( $settings as $setting ) {
				if ( false !== strpos( $field, $setting ) ) {
					$data[] = str_replace( $setting, '', $field );
				}
			}
		}

		return $data;
	}

	/**
	 * Generate sitemap on hook
	 *
	 * @since 1.0.0
	 *
	 *  add_action('wp', 'generate_sitemap');
	 */
	public function generate_sitemap() {
		$fake_enabled = isset( $this->builder->options['fake_enable'] ) ? $this->builder->options['fake_enable'] : false;
		$is_sitemap   = $this->check_sitemap();
		if ( $is_sitemap && $fake_enabled && $this->builder->protection->is_fake_bot() ) {
			global $wp_query;
			$wp_query->set_404();
			status_header( 404 );
		} else {
			$this->builder->sitemap_file_manager->sitemap_manager();
		}

	}

	/**
	 * Checks if a request has been sent to sitemap
	 *
	 * @return bool
	 */
	public function check_sitemap() {
		$request = filter_var( isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '', FILTER_SANITIZE_STRING );

		$request_arr = array_values( array_diff( explode( '/', $request ), [ '' ] ) );

		preg_match( '/sitemap.xml|(sitemap_main.xml)/', $request_arr[ count( $request_arr ) - 1 ], $matches );
		if ( ! empty( $matches ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Сheck fake bots and hides the robots file from them if necessary.
	 *
	 * Use: add_action('wp', 'robots_protection');
	 */
	public function robots_protection() {
		$fake_enabled = isset( $this->builder->robots_options[ $this->current_domain ]['fake_enable'] ) ? $this->builder->robots_options[ $this->current_domain ]['fake_enable'] : false;

		if ( $this->check_robots_uri() ) {
			if ( $fake_enabled && $this->builder->protection->is_fake_bot() ) {
				global $wp_query;
				$wp_query->set_404();
				status_header( 404 );
			} else {
				remove_action( 'do_robots', 'do_robots' );
				status_header( 200 );
				do_robots();
				die();
			}
		}
	}

	/**
	 * Inserts robots from a file for the current domain.
	 *
	 * @param string $output Robots content.
	 *
	 * @return string
	 *
	 * Use: add_filter( 'robots_txt', [ $this, 'show_robots' ], 10, 2 );
	 */
	public function show_robots( $output ) {
		$robots = new Robots_File( $this->current_domain );
		$output = $robots->content();

		return $output;
	}

	/**
	 * Check if exist robots in uri
	 *
	 * @return bool
	 */
	private function check_robots_uri() {

		$site_url = get_site_url();

		$http_status     = ! empty( $_SERVER['HTTPS'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTPS'] ) ) : '';
		$http_host       = ! empty( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : '';
		$http_reques_uri = ! empty( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';

		$actual_link = ( isset( $http_status ) && 'on' === $http_status ? 'https' : 'http' ) . "://$http_host$http_reques_uri";

		$robots_link = $site_url . '/robots.txt';

		if ( substr( $actual_link, mb_strlen( $actual_link ) - mb_strlen( $robots_link ) ) === $robots_link ) {
			return true;
		}

		return false;
	}

}
