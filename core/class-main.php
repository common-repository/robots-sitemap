<?php
/**
 * The main plugin file for register all hooks and default settings.
 *
 * @since   1.0.0
 * @package Robots_Sitemap\Core;
 */

namespace Robots_Sitemap\Core;

use Robots_Sitemap\Admin\Admin;
use Robots_Sitemap\Core\Libs\Vo3da_Functions;
use Robots_Sitemap\Front\Front;

/**
 * Class Main
 *
 * @package Robots_Sitemap\Core
 */
class Main {

	/**
	 * Name, slug and version of this plugin
	 *
	 * @var array
	 */
	private $plugin_info;
	/**
	 * The options of this plugin.
	 *
	 * @var array
	 */
	private $options;
	/**
	 * Robots settings.
	 *
	 * @var array
	 */
	private $robots_options;
	/**
	 * Contains some variables and objects for the plugin work.
	 *
	 * @var Data_Builder
	 */
	private $builder;

	/**
	 * Contains wp-filesystem object.
	 */
	private $filesystem;

	/**
	 * Main constructor.
	 *
	 */
	public function __construct() {

		$this->plugin_info = [
			'name'           => 'Robots & Sitemap',
			'slug'           => 'vo3da-robots-sitemap',
			'i18n_slug'      => 'vo3da-robots-sitemap',
			'options'        => 'custom_sitemap_options',
			'robots_options' => 'vo3da_robots_options',
			'version'        => '1.3.0',
		];

		$options              = get_option( $this->plugin_info['options'], [] );
		$robots_options       = get_option( $this->plugin_info['robots_options'], [] );
		$this->options        = ! empty( $options ) ? $options : CUSTOM_SITEMAP_DEFAULT_OPTIONS;
		$this->robots_options = $robots_options;
		$this->builder        = new Data_Builder( $this->plugin_info, $this->options, $this->robots_options );
		$this->filesystem     = Vo3da_Functions::WP_Filesystem();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();
		$this->migration();
		$this->migration_files();
	}

	/**
	 * Register all of the hooks related to the admin area functionality of the plugin.
	 */
	private function define_admin_hooks() {

		$admin = new Admin( $this->builder );
		$admin->hooks();
	}

	/**
	 * Register all of the hooks related to the front-end area functionality of the plugin.
	 */
	private function define_public_hooks() {

		$front = new Front( $this->builder );
		$front->hooks();
	}

	/**
	 * Delete cron options from DB.
	 */
	private function migration() {
		if ( isset( $this->options['cron'] ) ) {
			unset( $this->options['cron'] );
			update_option( $this->plugin_info['options'], $this->options );
		}
	}

	/**
	 * Migration files to new folders.
	 */
	private function migration_files() {
		$path             = $this->filesystem->wp_content_dir() . 'uploads/robots-sitemap/';
		$new_sitemap_path = $path . 'sitemap/';
		$new_robots_path  = $path . 'robots/';
		$old_sitemap_path = $this->filesystem->wp_content_dir() . 'cache/robots-sitemap/';
		$old_robots_path  = $this->filesystem->abspath() . 'robots/';

		if ( $this->filesystem->exists( $old_robots_path ) ) {
			$this->filesystem->move( $old_robots_path, $new_robots_path );
			$this->filesystem->rmdir( $old_robots_path, true );
		}

		if ( $this->filesystem->exists( $old_sitemap_path ) ) {
			$this->filesystem->move( $old_sitemap_path, $new_sitemap_path );
			$this->filesystem->rmdir( $old_sitemap_path, true );
		}

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Plugin_Name_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new I18n( $this->plugin_info['i18n_slug'] );
		$plugin_i18n->hooks();
	}

}
