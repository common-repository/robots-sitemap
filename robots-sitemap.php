<?php
/**
 *
 * @link              /
 * @since             1.0.0
 * @package           Robots_Sitemap
 *
 * @wordpress-plugin
 * Plugin Name:         Robots & Sitemap
 * Plugin URI:          /
 * Description:         Simply and fast edit your robots.txt and sitemap.
 * Version:             1.3.0
 * Author:              VO3DA Team
 * Author URI:          vo3da.tech
 * License:             GPL-2.0+
 * License URI:         http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:         robots-sitemap
 * Domain Path:         /languages/
 */


use Robots_Sitemap\Core\Main;


if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Default options.
 */
const CUSTOM_SITEMAP_DEFAULT_OPTIONS = [
	'home_prioriti'        => 1,
	'home_frequencies'     => 'daily',
	'home_enable'          => 1,
	'page_prioriti'        => 0.6,
	'page_frequencies'     => 'weekly',
	'page_enable'          => 1,
	'post_prioriti'        => 0.6,
	'post_frequencies'     => 'monthly',
	'post_enable'          => 1,
	'category_prioriti'    => 0.8,
	'category_frequencies' => 'daily',
	'category_enable'      => 1,
	'fake_enable'          => 0,
];

/**
 * Path to the plugin dir.
 */
define( 'VO3DA_RS_PATH', dirname( __FILE__ ) );
define( 'VO3DA_RS_URL', plugin_dir_url( __FILE__ ) );
require_once constant( 'VO3DA_RS_PATH' ) . '/core/libs/vo3da-functions.php';
require_once constant( 'VO3DA_RS_PATH' ) . '/vendor/autoload.php';

$main = new Main();
