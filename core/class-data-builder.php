<?php
/**
 * The default data builder.
 * Contains some variables and objects for the plugin work.
 *
 * @since   1.0.0
 * @package Robots_Sitemap\Core;
 */

namespace Robots_Sitemap\Core;

use Robots_Sitemap\Core\Db\Sitemap_DB;
use Robots_Sitemap\Core\Sitemap\Sitemap_File_Manager;
use Robots_Sitemap\Core\Protection;

/**
 * Class Data_Builder
 */
class Data_Builder {

	/**
	 * Plugin name
	 *
	 * @var string
	 */
	public $plugin_name;
	/**
	 * Plugin slug
	 *
	 * @var string
	 */
	public $plugin_slug;
	/**
	 * Current version
	 *
	 * @var string
	 */
	public $version;
	/**
	 * Plugins settings
	 *
	 * @var array
	 */
	public $options;
	/**
	 * Robots settings
	 *
	 * @var array
	 */
	public $robots_options;
	/**
	 * Instance of class for working with DB.
	 *
	 * @var Sitemap_DB
	 */
	public $sitemap_db;
	/**
	 * Instance of file manager class. Sitemaps files storage.
	 *
	 * @var Sitemap_File_Manager
	 */
	public $sitemap_file_manager;

	/**
	 * Instance of protection class. Protect sitemaps from fake bots.
	 *
	 * @var Protection
	 */
	public $protection;

	/**
	 * Data_Builder constructor.
	 *
	 * @param array $plugin_info Array of plugin meta information.
	 * @param array $options Array of plugin settings.
	 * @param array $robots_options Array of robots settings.
	 */
	public function __construct( $plugin_info, $options, $robots_options ) {
		$this->plugin_name          = $plugin_info['name'];
		$this->plugin_slug          = $plugin_info['slug'];
		$this->version              = $plugin_info['version'];
		$this->options              = $options;
		$this->robots_options       = $robots_options;
		$this->sitemap_db           = new Sitemap_DB( $options );
		$this->sitemap_file_manager = new Sitemap_File_Manager( $this->options, $this->sitemap_db );
		$this->protection           = new Protection( $options );
	}

}