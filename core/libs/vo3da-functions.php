<?php
/**
 * Custom autoload function.
 *
 * @since      1.0.0
 *
 * @package    Code_Inserter\Core\Libs
 */

namespace Robots_Sitemap\Core\Libs;

use WP_Filesystem_Direct;

/**
 * Path to cache directory
 */
if ( ! defined( 'CACHE_DIR' ) ) {
	define( 'CACHE_DIR', WP_CONTENT_DIR . '/cache/' );
}


/**
 * Class Autoload_Functions
 *
 * @package Code_Inserter\Core\Libs
 */
class Vo3da_Functions {

	/**
	 * Returns the domains of the current site
	 *
	 * @param int $site_id Current site id.
	 *
	 * @return array
	 */
	public static function get_mirrors( $site_id ) {
		$mirrors = false;
		if ( false === $mirrors ) {
			global $wpdb;
			// TODO: тут приходит неправильная таблица и зеркала не отдаются
			$domain_mapping_table = wp_cache_get( 'vo3da_domain_mapping_table' );
			if ( false === $domain_mapping_table ) {
				// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
				$domain_mapping_table = $wpdb->get_var(
					$wpdb->prepare(
						'SHOW TABLES LIKE %s',
						$wpdb->dmtable
					)
				);
				// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery.
				wp_cache_add( 'vo3da_domain_mapping_table', $domain_mapping_table );
			}

			if ( ! empty( $domain_mapping_table ) && 'wp_domain_mapping' === $domain_mapping_table ) {
				// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery

				if ( true === SUBDOMAIN_INSTALL ) {
					$domains = $wpdb->get_results(
						$wpdb->prepare(
							"SELECT DISTINCT REPLACE( domain, 'www.', '' ) as domain FROM $wpdb->dmtable WHERE blog_id = %d ORDER BY domain ASC",
							$site_id
						)
					);
				} elseif ( false === SUBDOMAIN_INSTALL ) {
					$domains = $wpdb->get_results( $wpdb->prepare( 'SELECT DISTINCT domain as domain FROM ' . $wpdb->blogs . ' WHERE blog_id = %d ORDER BY domain ASC', $site_id ) );
				}
				// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery.
				$mirrors = [];
				if ( ! empty( $domains ) ) {
					foreach ( $domains as $domain ) {
						array_push( $mirrors, $domain->domain );
					}
				}
				wp_cache_add( 'vo3da_site_mirrors', $mirrors );
			} else {
				$protocols = [ 'http://', 'https://' ];
				$site_url  = get_site_url( get_current_blog_id() );
				$mirrors[] = str_replace( $protocols, '', $site_url );
				wp_cache_add( 'vo3da_site_mirrors', $mirrors, 'https' );
			}
		}

		return $mirrors;
	}

	/**
	 * Create a database if necessary
	 *
	 * @param string $database_name Name database.
	 * @param string $sql           SQL for creating database.
	 *
	 * @return bool
	 */
	public static function maybe_create_table( $database_name, $sql ) {
		if ( ! function_exists( 'maybe_create_table' ) ) {
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		}

		return maybe_create_table( $database_name, $sql );
	}

	/**
	 * Create instance WP_Filesystem
	 *
	 *
	 * phpcs:disable WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
	 */
	static function WP_Filesystem() {
		//phpcs:enable WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
		global $wp_filesystem;
		if ( null === $wp_filesystem ) {
			if ( ! class_exists( 'WP_Filesystem_Base' ) || ! function_exists( 'WP_Filesystem' ) ) {
				require_once ABSPATH . 'wp-admin/includes/file.php';
				require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php';
				require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php';
			}
			WP_Filesystem();
		}

		return new WP_Filesystem_Direct( null );
	}

	/**
	 * Check active plugin or no
	 *
	 * @param string $plugin plugin folder and main file.
	 *
	 * @return bool
	 */
	public static function is_plugin_active( $plugin ) {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		return is_plugin_active( $plugin );
	}

}
