<?php

namespace Robots_Sitemap\Core\Sitemap;

use Robots_Sitemap\Core\Db\Sitemap_DB;
use Robots_Sitemap\Core\Libs\Vo3da_Functions;
use WP_Filesystem_Direct;

/**
 * Class Sitemap_File_Manager
 */
class Sitemap_File_Manager {

	/**
	 * WP_Filesystem_Direct object
	 *
	 * @var WP_Filesystem_Direct
	 */
	private $filesystem;
	/**
	 * Cache folder path
	 *
	 * @var string
	 */
	private $plugin_dir;
	/**
	 * News sitemap url
	 *
	 * @var string
	 */
	private $news_url;
	/**
	 * Sitemap DB instance
	 *
	 * @var Sitemap_Db
	 */
	public $sitemap_db;
	/**
	 * Array of plugin options.
	 *
	 * @var array
	 */
	private $options;
	/**
	 * Cache blog's folder path
	 *
	 * @var string
	 */
	private $site_plugin_dir;
	/**
	 * Instance of Sitemap_Factory
	 *
	 * @var Sitemap_Factory
	 */
	public $sitemap_factory;

	/**
	 * Sitemap_Clear constructor.
	 *
	 * @param array      $options    Array of plugin options.
	 * @param Sitemap_DB $sitemap_db Sitemap DB instance.
	 */
	public function __construct( $options, Sitemap_DB $sitemap_db ) {
		$this->filesystem = Vo3da_Functions::WP_Filesystem();

		$this->options         = $options;
		$this->plugin_dir      = $this->filesystem->wp_content_dir() . 'uploads/robots-sitemap/';
		$this->site_plugin_dir = $this->plugin_dir . '/sitemap/' . get_current_blog_id() . '/';
		$this->news_url        = $this->site_plugin_dir . 'sitemap-news.xml';
		$this->sitemap_db      = $sitemap_db;
		$this->sitemap_factory = new Sitemap_Factory( $this->options );
		$this->create_folders();
	}

	/**
	 * Executes methods depending on settings
	 */
	public function sitemap_manager() {
		if ( empty( $this->options['sitemap_disable'] ) ) {
			$this->sitemap_factory->generate();
		} else {
			$this->clear( null, true );
		}
	}

	/**
	 * Create caching folders
	 */
	private function create_folders() {
		if ( ! $this->filesystem->exists( $this->site_plugin_dir ) ) {
			wp_mkdir_p( $this->site_plugin_dir );
		}
	}

	/**
	 * Delete sitemap news file
	 */
	public function clear_news() {
		if ( $this->filesystem->exists( $this->news_url ) ) {
			$this->filesystem->delete( $this->news_url );
		}
	}

	/**
	 * Clear all cached sitemaps
	 */
	public function clear_cache() {
		$post_types   = $this->sitemap_db->get_active_post_types();
		$post_types[] = 'attachment';
		$taxonomies   = $this->sitemap_db->get_active_taxonomies();

		if ( ! empty( $post_types ) ) {
			$this->clear_options_cache( $post_types );
		}

		if ( ! empty( $taxonomies ) ) {
			$this->clear_options_cache( $taxonomies );
		}

		$this->clear_options_cache( [ 'home' ] );

		if ( ! empty( $this->options['sitemapnews_cat'] ) ) {
			wp_cache_delete( 'category_' . $this->options['sitemapnews_cat'] . '_sitemap_items' );
		}
		$this->clear( null, true );
		$this->sitemap_manager();
	}

	/**
	 * Clear the sitemap
	 *
	 * @param null $object Sitemap name file.
	 * @param bool $full   Is full delete.
	 */
	public function clear( $object = null, $full = false ) {
		$sitemap = $object;

		if ( ! empty( $object ) ) {
			if ( ! empty( $this->options['separation_enable'] ) ) {
				$sitemap = array_merge( $sitemap, [ 'sitemapimages.xml', 'sitemap_main.xml' ] );
			} else {
				$sitemap = [ 'sitemap.xml', 'sitemapimages.xml', 'sitemap-news.xml' ];
			}
		}

		if ( ! empty( $this->options['sitemap_disable'] ) || $full ) {
			unset( $sitemap );
		}

		foreach ( glob( rtrim( $this->site_plugin_dir, '/' ) . '/*' ) as $folder ) {

			if ( 'file' === filetype( $folder ) ) {
				if ( ! empty( $sitemap ) ) {
					if ( in_array( basename( $folder ), $sitemap, true ) ) {
						$this->filesystem->delete( $folder );
					}
				} else {
					$this->filesystem->delete( $folder );
				}
			} elseif ( 'dir' === filetype( $folder ) ) {
				foreach ( glob( rtrim( $folder, '/' ) . '/*' ) as $path ) {
					if ( ! empty( $sitemap ) ) {
						if ( in_array( basename( $path ), $sitemap, true ) ) {
							$this->filesystem->delete( $path );
						}
					} else {
						$this->filesystem->delete( $path );
					}
				}
			}
		}
	}

	/**
	 * Array of the sitemaps that must be cleared.
	 *
	 * @param array $sitemaps array of sitemaps.
	 */
	public function clear_options_cache( $sitemaps ) {

		if ( empty( $sitemaps ) ) {
			return;
		}

		foreach ( $sitemaps as $sitemap ) {
			delete_option( $sitemap . '_sitemap_items' );
		}
	}

}