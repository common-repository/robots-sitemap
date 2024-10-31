<?php
/**
 * The admin area functionality of the plugin.
 *
 * @since      1.0.0
 *
 * @package    Robots_Sitemap
 * @subpackage Robots_Sitemap/Admin
 */

namespace Robots_Sitemap\Admin;

use Robots_Sitemap\Core\Libs\Vo3da_Functions;
use Robots_Sitemap\Core\Data_Builder;
use Robots_Sitemap\Core\Db\Post\Sitemap_Post_Item;
use Robots_Sitemap\Core\Db\Term\Sitemap_Term_Item;
use Robots_Sitemap\Core\WPML;
use Robots_Sitemap\Core\Robots\Robots_File;
use Exception;
use WP_Post;
use WP_Term;

/**
 * The admin-specific functionality of the plugin.
 *
 * @package Robots_Sitemap\Admin
 */
class Admin {

	/**
	 * Contains some variables and objects for the plugin work.
	 *
	 * @var Data_Builder
	 */
	private $builder;
	/**
	 * Cache URL
	 *
	 * @var string
	 */
	private $cache_url;
	/**
	 * Frequencies settings
	 *
	 * @var array
	 */
	private $frequencies;
	/**
	 * Instance of sitemap WPML class
	 *
	 * @var WPML
	 */
	private $wpml;
	/**
	 * Instance of sitemap WPML class
	 *
	 * @var WPML
	 */
	public $bots_list;
	/**
	 * Mirrors multisite
	 *
	 * @var array
	 */
	public $mirrors;
	/**
	 * Global wpdb
	 *
	 * @var $wpdb
	 */
	private $db;

	/**
	 * Instance of Robots_File class.
	 *
	 * @var Robots_File
	 */
	public $robots;

	/**
	 * Current domain.
	 *
	 * @var string
	 */
	public $current_domain;
	/**
	 * Robots settings.
	 *
	 * @var array
	 */
	public $robots_options;

	/**
	 * Admin constructor.
	 *
	 * @param Data_Builder $builder Contains some variables and objects for the plugin work.
	 */
	public function __construct( Data_Builder $builder ) {
		$this->builder        = $builder;
		$this->wpml           = new WPML();
		$this->frequencies    = [
			'always'  => __( 'Always', 'vo3da-robots-sitemap' ),
			'hourly'  => __( 'Hourly', 'vo3da-robots-sitemap' ),
			'daily'   => __( 'Daily', 'vo3da-robots-sitemap' ),
			'weekly'  => __( 'Weekly', 'vo3da-robots-sitemap' ),
			'monthly' => __( 'Monthly', 'vo3da-robots-sitemap' ),
			'yearly'  => __( 'Yearly', 'vo3da-robots-sitemap' ),
			'never'   => __( 'Never', 'vo3da-robots-sitemap' ),
		];
		$this->cache_url      = WP_CONTENT_DIR . '/uploads/robots-sitemap';
		$this->bots_list      = [ 'google', 'bing' ];
		$this->mirrors        = Vo3da_Functions::get_mirrors( get_current_blog_id() );
		$current_domain       = str_replace( [ 'http://', 'https://' ], '', get_site_url( get_current_blog_id() ) );
		$this->current_domain = $current_domain;
		$robots               = new Robots_File( $current_domain );
		$this->robots         = $robots;
		$this->robots_options = $robots->get_robots_options();
		global $wpdb;
		$this->db = $wpdb;

	}

	/**
	 * Admin hooks.
	 *
	 */
	public function hooks() {
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_styles' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );

		/**
		 * Page options
		 */
		add_action( 'admin_init', [ $this, 'register_setting' ] );
		add_action( 'admin_menu', [ $this, 'add_menu' ] );
		add_action( 'update_option_custom_sitemap_options', [ $this, 'clear_sitemap_news' ] );

		/**
		 * Posts metaboxes
		 */
		add_action( 'add_meta_boxes', [ $this, 'add_meta_boxes' ] );
		add_action( 'post_updated', [ $this, 'post_updated' ], 100000, 1 );

		/**
		 * Term metaboxes
		 */
		add_action( 'category_edit_form_fields', [ $this, 'add_term_meta_boxes' ] );
		add_action( 'post_tag_edit_form_fields', [ $this, 'add_term_meta_boxes' ] );

		add_action( 'edit_term', [ $this, 'save_term_meta' ], 10, 3 );

		/*
		 * Pinger for se bots.
		 */
		add_action( 'transition_post_status', [ $this, 'ping_post' ], 100, 3 );
		add_action( 'created_term', [ $this, 'ping_term' ], 10, 1 );

		/*
		 * Ajax actions
		 */
		add_action( 'wp_ajax_get_robots', [ $this, 'ajax_get_robots' ] );
		add_action( 'wp_ajax_vo3da_save_sitemap_options', [ $this, 'ajax_save_sitemap_options' ] );
		add_action( 'wp_ajax_vo3da_clear_sitemap_cache', [ $this, 'ajax_clear_sitemap_cache' ] );
		add_action( 'wp_ajax_vo3da_update_robots', [ $this, 'ajax_update_robots' ] );
		add_action( 'wp_ajax_vo3da_replace_robots', [ $this, 'ajax_replace_robots' ] );

	}

	/**
	 * Register the styles for the admin area.
	 *
	 * @since   1.0.0
	 *
	 * add_action('admin_enqueue_scripts', 'enqueue_styles');
	 */
	public function enqueue_styles() {
		global $current_screen;
		if ( stripos( $current_screen->base, $this->builder->plugin_slug ) ) {
			wp_enqueue_style( $this->builder->plugin_slug . '_datatables', plugin_dir_url( __FILE__ ) . 'assets/css/jquery.dataTables.min.css', [], $this->builder->version, 'all' );
			wp_enqueue_style( $this->builder->plugin_slug, plugin_dir_url( __FILE__ ) . 'assets/css/sitemap-robots.min.css', [], $this->builder->version, 'all' );
		}
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since   1.0.0
	 *
	 * add_action('admin_enqueue_scripts', 'enqueue_scripts');
	 */
	public function enqueue_scripts() {
		global $current_screen;
		if ( stripos( $current_screen->base, $this->builder->plugin_slug ) ) {
			wp_enqueue_script( $this->builder->plugin_slug . '_datatables', plugin_dir_url( __FILE__ ) . 'assets/js/jquery.dataTables.min.js', [ 'jquery' ], $this->builder->version, false );
			wp_enqueue_script( $this->builder->plugin_slug, plugin_dir_url( __FILE__ ) . 'assets/js/sitemap-robots.min.js', [
				'jquery',
				$this->builder->plugin_slug . '_datatables',
			], $this->builder->version, false );

		}
		if ( ! wp_script_is( 'vo3da-plugin-position-script' ) ) {
			wp_enqueue_script( 'vo3da-plugin-position-script', plugin_dir_url( __FILE__ ) . 'assets/js/vo3da-plugin-position.js', [ 'jquery' ], '1.0', true );
		}
	}

	/**
	 * Register settings for plugin options.
	 *
	 * @since    1.0.0
	 *
	 * add_action( 'admin_init', 'register_setting' );
	 */
	public function register_setting() {

		register_setting( $this->builder->plugin_slug, 'custom_sitemap_options' );

	}

	/**
	 * Nonce fields for page options
	 *
	 * @return false|string
	 */
	private function get_settings_filed() {
		ob_start();
		settings_fields( $this->builder->plugin_slug );

		return ob_get_clean();
	}

	/**
	 * Add plugin page in WordPress menu.
	 *
	 * @since 1.0.0
	 *
	 * add_action('admin_menu', 'add_menu');
	 */
	public function add_menu() {
		$parent_menu_name = 'VO3DA Plugins';
		$parent_menu_slug = 'vo3da-plugins';
		global $admin_page_hooks;
		if ( empty( $admin_page_hooks[ $parent_menu_slug ] ) ) {
			add_menu_page(
				$parent_menu_name,
				$parent_menu_name,
				'manage_options',
				$parent_menu_slug,
				'',
				'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iVVRGLTgiPz48c3ZnIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgeG1sbnM6eGxpbms9Imh0dHA6Ly93d3cudzMub3JnLzE5OTkveGxpbmsiIHdpZHRoPSIxOXB0IiBoZWlnaHQ9IjE3cHQiIHZpZXdCb3g9IjAgMCAxOSAxNyIgdmVyc2lvbj0iMS4xIj48ZyBpZD0ic3VyZmFjZTEiPjxwYXRoIHN0eWxlPSIgc3Ryb2tlOm5vbmU7ZmlsbC1ydWxlOm5vbnplcm87ZmlsbDojYTBhNWFhO2ZpbGwtb3BhY2l0eToxOyIgZD0iTSAxOC40NzI2NTYgMC45MTAxNTYgQyAxOC42NDQ1MzEgMC42MTMyODEgMTguODE2NDA2IDAuMzE2NDA2IDE4Ljk5NjA5NCAwIEwgMTQuMzg2NzE5IDAgQyAxNC4yOTY4NzUgMCAxNC4yMDcwMzEgMCAxNC4xMTcxODggMCBDIDE0LjA4NTkzOCAtMC4wMDM5MDYyNSAxNC4wNTQ2ODggMC4wMTE3MTg4IDE0LjAzOTA2MiAwLjAzOTA2MjUgQyAxMy44Nzg5MDYgMC4zMTY0MDYgMTMuNzIyNjU2IDAuNTkzNzUgMTMuNTYyNSAwLjg3MTA5NCBDIDEzLjQyNTc4MSAxLjA4OTg0NCAxMy4yOTI5NjkgMS4zMTI1IDEzLjE3NTc4MSAxLjUzOTA2MiBDIDEzLjA1NDY4OCAxLjczNDM3NSAxMi45Mzc1IDEuOTMzNTk0IDEyLjgyODEyNSAyLjE0MDYyNSBDIDEyLjY5NTMxMiAyLjM1MTU2MiAxMi41NzAzMTIgMi41NzAzMTIgMTIuNDUzMTI1IDIuNzkyOTY5IEMgMTIuMzI0MjE5IDMgMTIuMTk5MjE5IDMuMjA3MDMxIDEyLjA4OTg0NCAzLjQyMTg3NSBDIDExLjk4NDM3NSAzLjU4NTkzOCAxMS44ODY3MTkgMy43NSAxMS43OTY4NzUgMy45MjU3ODEgQyAxMS43MTQ4NDQgNC4wNTA3ODEgMTEuNjM2NzE5IDQuMTc5Njg4IDExLjU3MDMxMiA0LjMxNjQwNiBDIDExLjQ4MDQ2OSA0LjQ1MzEyNSAxMS4zOTg0MzggNC41OTM3NSAxMS4zMjQyMTkgNC43MzgyODEgQyAxMS4yMjY1NjIgNC44OTA2MjUgMTEuMTM2NzE5IDUuMDQ2ODc1IDExLjA1NDY4OCA1LjIxMDkzOCBDIDEwLjk2ODc1IDUuMzM1OTM4IDEwLjg5MDYyNSA1LjQ2ODc1IDEwLjgyNDIxOSA1LjYwNTQ2OSBDIDEwLjczNDM3NSA1Ljc0MjE4OCAxMC42NTIzNDQgNS44ODI4MTIgMTAuNTc4MTI1IDYuMDI3MzQ0IEMgMTAuNDk2MDk0IDYuMTU2MjUgMTAuNDE3OTY5IDYuMjg1MTU2IDEwLjM1MTU2MiA2LjQyMTg3NSBDIDEwLjI1IDYuNTc0MjE5IDEwLjE2MDE1NiA2LjczNDM3NSAxMC4wODIwMzEgNi44OTg0MzggQyAxMC4wMTE3MTkgNi45NzY1NjIgOS45NzI2NTYgNy4wNzgxMjUgOS45MTc5NjkgNy4xNjc5NjkgQyA5Ljg5MDYyNSA3LjIxMDkzOCA5Ljg3NSA3LjIxNDg0NCA5Ljg0Mzc1IDcuMTY3OTY5IEMgOS44MDg1OTQgNy4xMTMyODEgOS43ODkwNjIgNy4wNDI5NjkgOS43NDIxODggNi45OTYwOTQgQyA5LjY3MTg3NSA2Ljg1MTU2MiA5LjU5NzY1NiA2LjcxNDg0NCA5LjUxMTcxOSA2LjU4MjAzMSBDIDkuNDI1NzgxIDYuNDA2MjUgOS4zMjgxMjUgNi4yMzQzNzUgOS4yMjI2NTYgNi4wNjY0MDYgQyA5LjEzNjcxOSA1LjkzMzU5NCA5LjA4OTg0NCA1Ljc3NzM0NCA4Ljk5MjE4OCA1LjY1MjM0NCBDIDguOTIxODc1IDUuNSA4LjgzNTkzOCA1LjM1MTU2MiA4Ljc0NjA5NCA1LjIxMDkzOCBDIDguNjc5Njg4IDUuMDY2NDA2IDguNjAxNTYyIDQuOTI5Njg4IDguNTE1NjI1IDQuNzk2ODc1IEMgOC40Mjk2ODggNC42NjQwNjIgOC4zODI4MTIgNC41MTE3MTkgOC4yODkwNjIgNC4zODI4MTIgQyA4LjE5OTIxOSA0LjIzNDM3NSA4LjEzMjgxMiA0LjA3MDMxMiA4LjAzNTE1NiAzLjkyOTY4OCBDIDcuOTQxNDA2IDMuNzQ2MDk0IDcuODQzNzUgMy41NjI1IDcuNzM0Mzc1IDMuMzkwNjI1IEMgNy41OTc2NTYgMy4xMzI4MTIgNy40NjQ4NDQgMi44Nzg5MDYgNy4zMTI1IDIuNjMyODEyIEMgNy4yMTg3NSAyLjQ0NTMxMiA3LjExNzE4OCAyLjI2MTcxOSA3LjAwNzgxMiAyLjA4MjAzMSBDIDYuOTE0MDYyIDEuODk0NTMxIDYuODEyNSAxLjcwNzAzMSA2LjY5NTMxMiAxLjUzMTI1IEMgNi41ODIwMzEgMS4zMjgxMjUgNi40ODgyODEgMS4xMDU0NjkgNi4zNTU0NjkgMC45MTAxNTYgQyA2LjIwNzAzMSAwLjY0MDYyNSA2LjA1NDY4OCAwLjM2NzE4OCA1LjkxMDE1NiAwLjA5Mzc1IEMgNS44ODI4MTIgMC4wMzEyNSA1LjgyMDMxMiAtMC4wMDc4MTI1IDUuNzUzOTA2IDAgQyAzLjg3NSAwLjAwMzkwNjI1IDEuOTk2MDk0IDAuMDAzOTA2MjUgMC4xMTMyODEgMC4wMDM5MDYyNSBMIDAuMDAzOTA2MjUgMC4wMDM5MDYyNSBMIDAuNTQyOTY5IDAuOTUzMTI1IEMgMC42NDQ1MzEgMS4xNTYyNSAwLjc1NzgxMiAxLjM1MTU2MiAwLjg3MTA5NCAxLjU0Njg3NSBDIDAuOTcyNjU2IDEuNzM0Mzc1IDEuMDc0MjE5IDEuOTE3OTY5IDEuMTc5Njg4IDIuMTAxNTYyIEMgMS4yODUxNTYgMi4zMDQ2ODggMS4zOTQ1MzEgMi41MDM5MDYgMS41MTE3MTkgMi42OTUzMTIgQyAxLjY0MDYyNSAyLjk0NTMxMiAxLjc3NzM0NCAzLjE4NzUgMS45MjE4NzUgMy40Mjk2ODggQyAyLjAxMTcxOSAzLjYxMzI4MSAyLjExMzI4MSAzLjc5Mjk2OSAyLjIxODc1IDMuOTY4NzUgQyAyLjI4OTA2MiA0LjExNzE4OCAyLjM3MTA5NCA0LjI1NzgxMiAyLjQ1NzAzMSA0LjM5ODQzOCBMIDIuNjg3NSA0LjgxNjQwNiBMIDIuOTMzNTk0IDUuMjUzOTA2IEMgMy4wMDM5MDYgNS40MDIzNDQgMy4wODU5MzggNS41NDY4NzUgMy4xNzU3ODEgNS42ODc1IEMgMy4yNDIxODggNS44MjQyMTkgMy4zMTI1IDUuOTU3MDMxIDMuMzk0NTMxIDYuMDg1OTM4IEwgMy42Nzk2ODggNi42MDE1NjIgQyAzLjc1NzgxMiA2Ljc0NjA5NCAzLjgzMjAzMSA2Ljg5NDUzMSAzLjkyNTc4MSA3LjAzNTE1NiBDIDQuMDAzOTA2IDcuMTk5MjE5IDQuMDkzNzUgNy4zNTkzNzUgNC4xOTE0MDYgNy41MTE3MTkgQyA0LjI2OTUzMSA3LjY4MzU5NCA0LjM2NzE4OCA3Ljg0Mzc1IDQuNDYwOTM4IDguMDAzOTA2IEwgNC42NzE4NzUgOC4zODI4MTIgQyA0Ljc0NjA5NCA4LjUzMTI1IDQuODI4MTI1IDguNjc1NzgxIDQuOTE3OTY5IDguODE2NDA2IEwgNS4xNjQwNjIgOS4yNzM0MzggTCA1LjQ1MzEyNSA5Ljc4OTA2MiBDIDUuNTUwNzgxIDkuOTcyNjU2IDUuNjQ0NTMxIDEwLjE1NjI1IDUuNzU3ODEyIDEwLjMyODEyNSBDIDUuODI0MjE5IDEwLjQ2ODc1IDUuODk4NDM4IDEwLjYwOTM3NSA1Ljk4NDM3NSAxMC43NDIxODggQyA2LjA1MDc4MSAxMC44ODY3MTkgNi4xMjg5MDYgMTEuMDIzNDM4IDYuMjE0ODQ0IDExLjE1NjI1IEMgNi4yOTI5NjkgMTEuMzIwMzEyIDYuMzgyODEyIDExLjQ4MDQ2OSA2LjQ4NDM3NSAxMS42MzI4MTIgQyA2LjU1ODU5NCAxMS43ODkwNjIgNi42NDA2MjUgMTEuOTQxNDA2IDYuNzM0Mzc1IDEyLjA4NTkzOCBDIDYuNzk2ODc1IDEyLjIxODc1IDYuODY3MTg4IDEyLjM0Mzc1IDYuOTQxNDA2IDEyLjQ2NDg0NCBDIDcuMDE1NjI1IDEyLjYxNzE4OCA3LjA5NzY1NiAxMi43NjE3MTkgNy4xOTE0MDYgMTIuOTAyMzQ0IEMgNy4yNjk1MzEgMTMuMDcwMzEyIDcuMzU5Mzc1IDEzLjIzNDM3NSA3LjQ2MDkzOCAxMy4zOTQ1MzEgQyA3LjU1NDY4OCAxMy41ODIwMzEgNy42NjAxNTYgMTMuNzY5NTMxIDcuNzY1NjI1IDEzLjk0OTIxOSBDIDcuODU5Mzc1IDE0LjEzMjgxMiA3Ljk2MDkzOCAxNC4zMTY0MDYgOC4wNzQyMTkgMTQuNDg4MjgxIEMgOC4xNDQ1MzEgMTQuNjQ4NDM4IDguMjMwNDY5IDE0Ljc5Njg3NSA4LjMyMDMxMiAxNC45NDUzMTIgQyA4LjQxMDE1NiAxNS4xMjEwOTQgOC41MDM5MDYgMTUuMjkyOTY5IDguNjA5Mzc1IDE1LjQ2MDkzOCBDIDguNzIyNjU2IDE1LjY3OTY4OCA4LjgzOTg0NCAxNS45MDIzNDQgOC45NzI2NTYgMTYuMTEzMjgxIEMgOS4wMTE3MTkgMTYuMTg3NSA5LjA1MDc4MSAxNi4yNjU2MjUgOS4wODk4NDQgMTYuMzM5ODQ0IEMgOS4xOTkyMTkgMTYuNTM1MTU2IDkuMzA4NTk0IDE2LjczNDM3NSA5LjQyMTg3NSAxNi45MjU3ODEgQyA5LjQ4MDQ2OSAxNy4wMjczNDQgOS40OTIxODggMTcuMDIzNDM4IDkuNTQ2ODc1IDE2LjkyMTg3NSBDIDkuNTg1OTM4IDE2Ljg1MTU2MiA5LjYyNSAxNi43ODUxNTYgOS42NjQwNjIgMTYuNzE0ODQ0IEMgOS43Njk1MzEgMTYuNTE1NjI1IDkuODk0NTMxIDE2LjMyNDIxOSA5Ljk4ODI4MSAxNi4xMTMyODEgQyAxMC4xMjg5MDYgMTUuODk4NDM4IDEwLjI1IDE1LjY2Nzk2OSAxMC4zNzEwOTQgMTUuNDQxNDA2IEMgMTAuNDY4NzUgMTUuMjg5MDYyIDEwLjU1ODU5NCAxNS4xMjg5MDYgMTAuNjM2NzE5IDE0Ljk2NDg0NCBDIDEwLjcyMjY1NiAxNC44MDQ2ODggMTAuODMyMDMxIDE0LjY1NjI1IDEwLjkwNjI1IDE0LjQ4ODI4MSBMIDExLjIwNzAzMSAxMy45NTMxMjUgTCAxMS41MTU2MjUgMTMuMzk4NDM4IEMgMTEuNjA1NDY5IDEzLjIzNDM3NSAxMS43MDMxMjUgMTMuMDc0MjE5IDExLjc4NTE1NiAxMi45MDIzNDQgQyAxMS44NzUgMTIuNzYxNzE5IDExLjk1NzAzMSAxMi42MTcxODggMTIuMDMxMjUgMTIuNDY0ODQ0IEMgMTIuMTA1NDY5IDEyLjM1NTQ2OSAxMi4xNzE4NzUgMTIuMjM0Mzc1IDEyLjIyNjU2MiAxMi4xMTMyODEgQyAxMi4yOTY4NzUgMTIuMDI3MzQ0IDEyLjMzMjAzMSAxMS45MTc5NjkgMTIuMzk0NTMxIDExLjgyODEyNSBDIDEyLjQxMDE1NiAxMS44MDQ2ODggMTIuNDEwMTU2IDExLjc3MzQzOCAxMi4zOTQ1MzEgMTEuNzUzOTA2IEwgMTIuMjk2ODc1IDExLjU5Mzc1IEMgMTIuMjI2NTYyIDExLjQ0MTQwNiAxMi4xNDQ1MzEgMTEuMjk2ODc1IDEyLjA1NDY4OCAxMS4xNTYyNSBDIDExLjk2NDg0NCAxMS4wMDc4MTIgMTEuOTA2MjUgMTAuODM5ODQ0IDExLjgwMDc4MSAxMC42OTkyMTkgQyAxMS43MzQzNzUgMTAuNTU4NTk0IDExLjY1NjI1IDEwLjQxNzk2OSAxMS41NzQyMTkgMTAuMjg1MTU2IEMgMTEuNDg4MjgxIDEwLjEwNTQ2OSAxMS4zOTA2MjUgOS45MzM1OTQgMTEuMjg5MDYyIDkuNzY5NTMxIEMgMTEuMjAzMTI1IDkuNTkzNzUgMTEuMTA1NDY5IDkuNDIxODc1IDExLjAwMzkwNiA5LjI1MzkwNiBDIDEwLjkxNzk2OSA5LjEwMTU2MiAxMC44NDc2NTYgOC45NDE0MDYgMTAuNzUgOC43OTY4NzUgQyAxMC42NzU3ODEgOC42NDQ1MzEgMTAuNTkzNzUgOC40OTIxODggMTAuNSA4LjM0Mzc1IEMgMTAuNDQxNDA2IDguMjE0ODQ0IDEwLjM3MTA5NCA4LjA4OTg0NCAxMC4yODkwNjIgNy45Njg3NSBDIDEwLjIwMzEyNSA3LjgwODU5NCAxMC4xMjg5MDYgNy42NDA2MjUgMTAuMDI3MzQ0IDcuNDkyMTg4IEMgMTAuMDAzOTA2IDcuNDQxNDA2IDkuOTYwOTM4IDcuNDAyMzQ0IDkuOTY0ODQ0IDcuMzM5ODQ0IEMgOS45NzI2NTYgNy4zMzU5MzggOS45ODA0NjkgNy4zMzIwMzEgOS45OTIxODggNy4zMzIwMzEgQyAxMC40MjU3ODEgNy4zMjAzMTIgMTAuODYzMjgxIDcuMzA0Njg4IDExLjI5Njg3NSA3LjI5Njg3NSBDIDExLjgzOTg0NCA3LjI4OTA2MiAxMi4zNzg5MDYgNy4yNzM0MzggMTIuOTE3OTY5IDcuMjU3ODEyIEMgMTMuNTAzOTA2IDcuMjQyMTg4IDE0LjA4OTg0NCA3LjIzNDM3NSAxNC42NzU3ODEgNy4yMTQ4NDQgQyAxNC45NTMxMjUgNy4yMDcwMzEgMTQuOTUzMTI1IDcuMjEwOTM4IDE1LjA4MjAzMSA2Ljk1MzEyNSBDIDE1LjA4NTkzOCA2Ljk0NTMxMiAxNS4wODU5MzggNi45NDE0MDYgMTUuMDg5ODQ0IDYuOTMzNTk0IEMgMTUuMTc5Njg4IDYuNzg5MDYyIDE1LjI2NTYyNSA2LjYzNjcxOSAxNS4zMzk4NDQgNi40ODA0NjkgQyAxNS40MzM1OTQgNi4zMzk4NDQgMTUuNTE1NjI1IDYuMTk1MzEyIDE1LjU4OTg0NCA2LjA0Mjk2OSBMIDE1LjgxMjUgNS42NDg0MzggQyAxNS44OTQ1MzEgNS41MTk1MzEgMTUuOTY0ODQ0IDUuMzg2NzE5IDE2LjAzMTI1IDUuMjUzOTA2IEMgMTYuMTI4OTA2IDUuMTA1NDY5IDE2LjIxMDkzOCA0Ljk1MzEyNSAxNi4yOTI5NjkgNC43OTY4NzUgTCAxNi41MzkwNjIgNC4zNTkzNzUgTCAxNi43Njk1MzEgMy45NDE0MDYgTCAxNy4wMzkwNjIgMy40Njg3NSBDIDE3LjE1NjI1IDMuMjU3ODEyIDE3LjI4OTA2MiAzLjA1NDY4OCAxNy4zODY3MTkgMi44MzIwMzEgQyAxNy41MjczNDQgMi42MTcxODggMTcuNjUyMzQ0IDIuMzkwNjI1IDE3Ljc2NTYyNSAyLjE2MDE1NiBDIDE3Ljg4NjcxOSAxLjk3NjU2MiAxNy45OTYwOTQgMS43ODUxNTYgMTguMDg5ODQ0IDEuNTg1OTM4IEMgMTguMjI2NTYyIDEuMzY3MTg4IDE4LjM1OTM3NSAxLjE0NDUzMSAxOC40NzI2NTYgMC45MTAxNTYgWiBNIDE4LjQ3MjY1NiAwLjkxMDE1NiAiLz48L2c+PC9zdmc+'
			);
		}

		add_submenu_page(
			$parent_menu_slug,
			$this->builder->plugin_name,
			$this->builder->plugin_name,
			'manage_options',
			$this->builder->plugin_slug,
			[
				$this,
				'page_options',
			]
		);
	}

	/**
	 * Plugin page callback.
	 */
	public function page_options() {

		$fields      = [
			'home' => __( 'Home page', 'vo3da-robots-sitemap' ),
		];
		$post_types  = $this->get_settings_post_type();
		$fields      = array_merge( $fields, $this->builder->sitemap_db->get_post_types( true ), $this->builder->sitemap_db->get_taxonomies( true ) );
		$categories  = $this->wpml->get_categories();
		$clear_cache = filter_input( INPUT_GET, 'sitemap_cache_clear', FILTER_VALIDATE_BOOLEAN );
		$domains     = $this->mirrors;
		$protocol    = is_ssl() ? 'https://' : 'http://';
		if ( ! $clear_cache ) {
			$clear_cache_url = ! empty( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) . '&sitemap_cache_clear=true' : '';
		} else {
			$clear_cache_url = sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) );
		}
		$has_mirrors = count( $domains ) === 1 ? false : true;

		$options = [
			'domains'        => $domains,
			'current_domain' => $this->current_domain,
			'fields'         => $fields,
			'frequencies'    => $this->frequencies,
			'sitemap_name'   => $this->builder->options['separation_enable'] ? $protocol . $this->current_domain . '/sitemap_main.xml' : $protocol . $this->current_domain . '/sitemap.xml',
			'options'        => $this->builder->options,
			'categories'     => $categories,
			'nonce'          => wp_create_nonce( $this->builder->plugin_slug ),
			'settings_field' => $this->get_settings_filed(),
			'has_mirrors'    => $has_mirrors,
			'robots_content' => $this->robots->content(),
			'robots_options' => $this->robots->get_options( $this->current_domain ),
			'ping_logs'      => get_option( 'vo3da_ping_results', [] ),
		];

		require_once plugin_dir_path( __FILE__ ) . 'views/page-options.php';

	}

	/**
	 * Get post type list for settings
	 *
	 * @return array
	 */
	private function get_settings_post_type() {
		$post_types = [];
		if ( empty( $this->builder->options['sitemap_disable'] ) ) {
			foreach ( $this->builder->sitemap_db->get_active_post_types() as $post_type ) {
				if ( ! empty( $this->builder->options['sitemapimg_enable'] ) && $this->builder->sitemap_db->check_post_type( $post_type ) ) {
					$post_types[] = $post_type;
					break;
				}
			}
		}

		return $post_types;
	}

	/**
	 * Register metaboxes for post types
	 *
	 * @since    1.0.0
	 *
	 * add_action( 'add_meta_boxes', 'add_meta_boxes' );
	 */
	public function add_meta_boxes() {
		add_meta_box(
			$this->builder->plugin_name,
			__( 'Sitemap settings', 'vo3da-robots-sitemap' ),
			[ $this, 'metabox' ],
			$this->builder->sitemap_db->get_post_types()
		);
	}

	/**
	 * Callback for method add_meta_boxes
	 *
	 * @param WP_Post $post WP_Post object.
	 *
	 * @since    1.0.0
	 */
	public function metabox( WP_Post $post ) {
		$repository_item = new Sitemap_Post_Item( $post, $this->builder->options );
		$meta            = $repository_item->meta;
		$frequencies     = $this->frequencies;
		$nonce_field     = wp_nonce_field( $this->builder->plugin_slug, $this->builder->plugin_slug . '_nonce', false, false );

		require_once plugin_dir_path( __FILE__ ) . 'views/components/metabox.php';
	}

	/**
	 * Save postmeta
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return bool
	 * @since    1.0.0
	 *
	 * add_action( 'post_updated', 'post_updated' )
	 */
	public function post_updated( $post_id ) {
		$nonce = ! empty( $_POST[ $this->builder->plugin_slug . '_nonce' ] ) ? sanitize_text_field( wp_unslash( $_POST[ $this->builder->plugin_slug . '_nonce' ] ) ) : '';
		if ( empty( $nonce ) || ( ! wp_verify_nonce( $nonce, $this->builder->plugin_slug ) ) && wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
			return false;
		}
		$data = [
			'prioriti'    => ! empty( $_POST['prioriti'] ) ? sanitize_text_field( wp_unslash( $_POST['prioriti'] ) ) : '',
			'frequencies' => ! empty( $_POST['frequencies'] ) ? sanitize_text_field( wp_unslash( $_POST['frequencies'] ) ) : '',
			'excludeurl'  => ! empty( $_POST['excludeurl'] ) ? sanitize_text_field( wp_unslash( $_POST['excludeurl'] ) ) : '',
		];
		// Filter input and update. Without foreach.
		$fields = [ 'prioriti', 'frequencies', 'excludeurl' ];

		foreach ( $fields as $field ) {
			if ( ! empty( $data[ $field ] ) ) {
				update_post_meta( $post_id, $field, $data[ $field ] );
			} else {
				delete_post_meta( $post_id, $field );
			}
		}

	}

	/**
	 * Add metaboxes for terms
	 *
	 * @param WP_Term $term WP_Term object.
	 *
	 * @throws Exception Throw an Exception.
	 * @since    1.0.0
	 *
	 * add_action( '{$taxname}_edit_form_fields', 'add_term_meta_boxes' )
	 */
	public function add_term_meta_boxes( WP_Term $term ) {

		$repository_item = new Sitemap_Term_Item( $term, $this->builder->options );
		$meta            = $repository_item->meta;
		$frequencies     = $this->frequencies;
		$nonce_field     = wp_nonce_field( $this->builder->plugin_slug, $this->builder->plugin_slug . '_nonce', false, false );

		require_once plugin_dir_path( __FILE__ ) . 'views/components/term-metabox.php';
	}

	/**
	 * Save termmeta
	 *
	 * @param int    $term_id  Term ID.
	 * @param int    $tt_id    Term taxonomy ID.
	 * @param string $taxonomy Taxonomy slug.
	 *
	 * @return bool
	 * @since    1.0.0
	 *
	 * add_action( 'edit_term', 'save_term_meta', 10, 3 )
	 */
	public function save_term_meta( $term_id, $tt_id, $taxonomy ) {
		$nonce = filter_input( INPUT_POST, $this->builder->plugin_slug . '_nonce', FILTER_SANITIZE_STRING );
		if ( empty( $nonce ) || ( ! wp_verify_nonce( $nonce, $this->builder->plugin_slug ) ) && in_array( $taxonomy, $this->builder->sitemap_db->get_taxonomies(), true ) ) {
			return false;
		}
		$data   = [
			'prioriti'    => ! empty( $_POST['prioriti'] ) ? sanitize_text_field( wp_unslash( $_POST['prioriti'] ) ) : '',
			'frequencies' => ! empty( $_POST['frequencies'] ) ? sanitize_text_field( wp_unslash( $_POST['frequencies'] ) ) : '',
			'excludeurl'  => ! empty( $_POST['excludeurl'] ) ? sanitize_text_field( wp_unslash( $_POST['excludeurl'] ) ) : '',
		];
		$fields = [ 'prioriti', 'frequencies', 'excludeurl' ];
		foreach ( $fields as $field ) {
			if ( ! empty( $data[ $field ] ) ) {
				update_term_meta( $term_id, $field, $data[ $field ] );
			} else {
				delete_term_meta( $term_id, $field );
			}
		}

	}

	/**
	 * Delete news sitemap
	 *
	 * @since 1.0.0
	 *
	 * add_action('update_option_custom_sitemap_options', 'clear_sitemap_news');
	 */
	public function clear_sitemap_news() {
		$this->builder->sitemap_file_manager->clear_news();
	}

	/**
	 * Delete total cache
	 *
	 * @since 1.0.0
	 *
	 */
	public function clear_sitemap_cache() {
		$this->builder->sitemap_file_manager->clear_cache();
	}

	/**
	 * Ping search engines when sitemap changed.
	 *
	 * @param string  $se       search engines name.
	 * @param integer $interval interval to ping bots.
	 *
	 * @return int
	 */
	public function ping_bot( $se, $interval ) {

		$bots = [
			'google' => 'https://www.google.com/ping',
			'bing'   => 'https://www.bing.com/ping',
		];

		$site_url = site_url();
		$sitemap  = $this->builder->options['separation_enable'] ? 'sitemap_main' : 'sitemap';

		if ( array_key_exists( $se, $bots ) ) {

			$url = add_query_arg( 'sitemap', rawurlencode( trailingslashit( $site_url ) . $sitemap . '.xml' ), $bots[ $se ] );

			if ( false === get_transient( 'custom_sitemap_ping_' . $se . '_' . $sitemap ) ) {
				$response = wp_remote_request( $url );
				$code     = wp_remote_retrieve_response_code( $response );

				if ( 200 === $code ) {
					set_transient( 'custom_sitemap_ping_' . $se . '_' . $sitemap, '', $interval );
				} else {
					$code = 0;
				}
			} else {
				$code = 999;
			}

			return $code;
		}

		return 0;
	}

	/**
	 * Do pings, hooked to transition post status
	 *
	 * @param string  $new_status new post status.
	 * @param string  $old_status old post status.
	 * @param WP_Post $post       post object.
	 */
	public function ping_post( $new_status, $old_status, $post ) {

		if ( 'publish' === $old_status || 'publish' !== $new_status ) {
			return;
		}

		if ( ! $this->builder->options[ $post->post_type . '_enable' ] ) {
			return;
		}

		foreach ( $this->bots_list as $bot ) {
			$code = $this->ping_bot( $bot, 30 );
			$this->log_ping_result( 'post', $bot, $code, $post->post_title, $post->guid );
		}
	}

	/**
	 * Do pings, hooked to create term
	 *
	 * @param int $term_id id of created term.
	 */
	public function ping_term( $term_id ) {
		$term      = get_term( $term_id );
		$term_link = get_term_link( intval( $term_id ) );

		if ( ! $this->builder->options[ $term->taxonomy . '_enable' ] ) {
			return;
		}

		foreach ( $this->bots_list as $bot ) {
			$code = $this->ping_bot( $bot, 30 );
			$this->log_ping_result( 'term', $bot, $code, $term->name, $term_link );
		}
	}

	/**
	 * Log result of bot ping
	 *
	 * @param $type
	 * @param $bot
	 * @param $code
	 * @param $title
	 */
	public function log_ping_result( $type, $bot, $code, $title, $url ) {
		if ( $code === 999 ) {
			return;
		}

		$logs = get_option( 'vo3da_ping_results', [] );

		$data['date']   = time();
		$data['bot']    = $bot;
		$data['type']   = $type;
		$data['title']  = $title;
		$data['url']    = $url;
		$data['status'] = $code ? 'success' : 'error';

		if ( count( $logs ) >= 200 ) {
			array_shift( $logs );
		}

		array_unshift( $logs, $data );

		update_option( 'vo3da_ping_results', $logs, false );
	}

	/**
	 * Ajax for saving sitemap settings
	 */
	public function ajax_save_sitemap_options() {
		$nonce = filter_input( INPUT_POST, '_nonce', FILTER_SANITIZE_STRING );
		if ( wp_verify_nonce( $nonce, $this->builder->plugin_slug ) ) {
			$new_data = filter_input( INPUT_POST, 'form_data', FILTER_SANITIZE_STRING );
			parse_str( $new_data, $parsed_data );
			if ( is_array( $parsed_data ) && array_key_exists( 'custom_sitemap_options', $parsed_data ) ) {
				$options      = $parsed_data['custom_sitemap_options'];
				$protocol     = is_ssl() ? 'https://' : 'http://';
				$sitemap_name = ( 1 === intval( $options['separation_enable'] ) ) ? $protocol . $this->current_domain . '/sitemap_main.xml' : $protocol . $this->current_domain . '/sitemap.xml';
				update_option( 'custom_sitemap_options', $options );
				$return = [
					'message'      => esc_html__( 'Your sitemap has been updated!', 'vo3da-robots-sitemap' ),
					'caption'      => esc_html__( 'Success', 'vo3da-robots-sitemap' ),
					'status'       => 'success',
					'sitemap_name' => $sitemap_name,
				];
			}
		} else {
			$return = [
				'message' => esc_html__( 'Error, try reload page!', 'vo3da-robots-sitemap' ),
				'caption' => esc_html__( 'Error', 'vo3da-robots-sitemap' ),
				'status'  => 'error',
			];
		}
		$this->clear_sitemap_cache();

		wp_send_json( $return );
	}

	/**
	 * Ajax for clear sitemap cache.
	 */
	public function ajax_clear_sitemap_cache() {
		$nonce = filter_input( INPUT_POST, '_nonce', FILTER_SANITIZE_STRING );
		if ( wp_verify_nonce( $nonce, $this->builder->plugin_slug ) ) {
			$this->clear_sitemap_cache();
			$return = [
				'message' => esc_html__( 'Your sitemap cache has been cleared!', 'vo3da-robots-sitemap' ),
				'caption' => esc_html__( 'Success', 'vo3da-robots-sitemap' ),
				'status'  => 'success',
			];

		} else {
			$return = [
				'message' => esc_html__( 'Error, try reload page!', 'vo3da-robots-sitemap' ),
				'caption' => esc_html__( 'Error', 'vo3da-robots-sitemap' ),
				'status'  => 'error',
			];
		}
		wp_send_json( $return );
	}

	/**
	 * Ajax for update robots
	 */
	public function ajax_update_robots() {
		$nonce = filter_input( INPUT_POST, '_nonce', FILTER_SANITIZE_STRING );
		if ( wp_verify_nonce( $nonce, $this->builder->plugin_slug ) ) {
			$form_data = filter_input( INPUT_POST, 'form_data', FILTER_SANITIZE_STRING );
			parse_str( $form_data, $new_data );
			$robots_options = $this->robots_options;
			$fake_enable    = $new_data['robots_options']['fake_enable'] ? $new_data['robots_options']['fake_enable'] : 0;
			$disable_robots = $new_data['robots_options']['disable_robots'] ? $new_data['robots_options']['disable_robots'] : 0;
			if ( 0 !== strcmp( $new_data['robots_domain'], '*' ) ) {
				$robots                                       = new Robots_File( $new_data['robots_domain'] );
				$robots_options[ $new_data['robots_domain'] ] = [
					'fake_enable'    => $fake_enable,
					'disable_robots' => $disable_robots,
				];
				$robots->update( $new_data['content'] );
			} else {
				foreach ( $this->mirrors as $domain ) {
					$robots                    = new Robots_File( $domain );
					$robots_options[ $domain ] = [
						'fake_enable'    => $fake_enable,
						'disable_robots' => $disable_robots,
					];
					$robots->update( $new_data['content'] );
				}
			}
			update_option( 'vo3da_robots_options', $robots_options );
			$return = [
				'message' => esc_html__( 'Your robots.txt file has been updated!', 'vo3da-robots-sitemap' ),
				'caption' => esc_html__( 'Success', 'vo3da-robots-sitemap' ),
				'status'  => 'success',
			];
		} else {
			$return = [
				'message' => esc_html__( 'Error, try reload page!', 'vo3da-robots-sitemap' ),
				'caption' => esc_html__( 'Error', 'vo3da-robots-sitemap' ),
				'status'  => 'error',
			];
		}
		wp_send_json( $return );
	}

	/**
	 * Ajax for replace robots
	 */
	public function ajax_replace_robots() {
		$nonce = filter_input( INPUT_POST, '_nonce', FILTER_SANITIZE_STRING );
		if ( wp_verify_nonce( $nonce, $this->builder->plugin_slug ) ) {
			$form_data = filter_input( INPUT_POST, 'form_data', FILTER_SANITIZE_STRING );
			parse_str( $form_data, $new_data );
			if ( ! empty( $new_data['replacement_domains'] ) ) {
				if ( ! empty( $new_data['replace'] ) && ! ctype_space( $new_data['replace'] ) ) {
					$replacement_domains = $new_data['replacement_domains'];
					foreach ( $replacement_domains as $mirror ) {
						$robots = new Robots_File( $mirror );
						$robots->replace( $new_data['replace'], $new_data['replacement'] );
					}
					$return = [
						'message' => esc_html__( 'Your robots.txt file has been updated!', 'vo3da-robots-sitemap' ),
						'caption' => esc_html__( 'Success', 'vo3da-robots-sitemap' ),
						'status'  => 'success',
					];
				} else {
					$return = [
						'message' => esc_html__( 'Error, You did not specify what you want to replace!', 'vo3da-robots-sitemap' ),
						'caption' => esc_html__( 'Error', 'vo3da-robots-sitemap' ),
						'status'  => 'error',
					];
				}
			} else {
				$return = [
					'message' => esc_html__( "Error, you don't check domain!", 'vo3da-robots-sitemap' ),
					'caption' => esc_html__( 'Error', 'vo3da-robots-sitemap' ),
					'status'  => 'error',
				];
			}
		} else {
			$return = [
				'message' => esc_html__( 'Error, try reload page!', 'vo3da-robots-sitemap' ),
				'caption' => esc_html__( 'Error', 'vo3da-robots-sitemap' ),
				'status'  => 'error',
			];
		}
		wp_send_json( $return );
	}

	/**
	 * Ajax for getting robots content
	 */
	public function ajax_get_robots() {

		$domain      = filter_input( INPUT_POST, 'domain', FILTER_SANITIZE_STRING );
		$robots_link = is_ssl() ? 'https://' : 'http://';
		$robots_link .= $domain . '/robots.txt';

		$data = [
			'content'     => '',
			'fake_enable' => 0,
			'robots_link' => $robots_link,
		];
		if ( ! empty( $domain ) ) {
			$robots              = new Robots_File( $domain );
			$options             = $robots->get_options( $domain );
			$data['content']     = $robots->content();
			$data['fake_enable'] = $options['fake_enable'] ? $options['fake_enable'] : 0;
		}
		wp_send_json( $data );

	}

}
