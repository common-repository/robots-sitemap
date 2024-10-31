<?php
/**
 * The factory of sitemap. This file controls behavior.
 *
 * @package Robots_Sitemap\Front\Sitemap;
 */

namespace Robots_Sitemap\Core\Sitemap;

use Robots_Sitemap\Core\Db\Sitemap_DB;
use Robots_Sitemap\Core\Libs\Vo3da_Functions;

/**
 * Class Sitemap_Factory
 *
 * @package Robots_Sitemap\Front\Sitemap
 */
class Sitemap_Factory {

	/**
	 * Sitemap options
	 *
	 * @var array
	 */
	private $options;
	/**
	 * Object for work with DB
	 *
	 * @var Sitemap_DB
	 */
	public $sitemap_db;
	/**
	 * Cache url
	 *
	 * @var string
	 */
	private $domain_cache_url;

	/**
	 * Sitemap_Factory constructor.
	 *
	 * @param array $options Custom sitemap options.
	 */
	public function __construct( $options ) {
		$this->options          = $options;
		$this->sitemap_db       = new Sitemap_DB( $this->options );
		$this->domain_cache_url = WP_CONTENT_DIR . '/uploads/robots-sitemap/sitemap/' . get_current_blog_id() . '/';
	}

	/**
	 * Start function for generation sitemap
	 *
	 * @param string      $name    Name of sitemap file.
	 * @param string      $type    Type of sitemap.
	 * @param string|null $context Post_type name or taxonomy name.
	 *
	 * @return void
	 */
	private function check_sitemap_action( $name, $type, $context = null ) {

		$file  = $this->domain_cache_url . $name . '.xml';
		$clear = filter_input( INPUT_POST, 'clear', FILTER_VALIDATE_BOOLEAN );

		if ( ! $clear ) {
			if ( ! $this->check_sitemap_uri( $name ) ) {
				return;
			}
		}

		if ( ! file_exists( $file ) ) {
			$this->create_sitemap_file( $file, $type, $context );
		}

		if ( ! $clear ) {
			if ( file_exists( $file ) ) {
				$this->render( $file );
			}
		}
	}

	/**
	 * Check if exist sitemap name in uri
	 *
	 * @param string $name Name of sitemap.
	 *
	 * @return bool
	 */
	private function check_sitemap_uri( $name ) {

		$site_url = get_site_url();

		$http_status     = ! empty( $_SERVER['HTTPS'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTPS'] ) ) : '';
		$http_host       = ! empty( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : '';
		$http_reques_uri = ! empty( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';

		$actual_link = ( isset( $http_status ) && 'on' === $http_status ? 'https' : 'http' ) . "://$http_host$http_reques_uri";

		$sitemap_link = $site_url . '/' . $name . '.xml';

		if ( substr( $actual_link, mb_strlen( $actual_link ) - mb_strlen( $sitemap_link ) ) === $sitemap_link ) {
			return true;
		}

		return false;
	}

	/**
	 * Sitemaps main generator
	 */
	public function generate() {
		if ( ! empty( $this->options['sitemapnews_enable'] ) && ! empty( $this->options['sitemapnews_cat'] ) ) {
			$this->check_sitemap_action( 'sitemap-news', 'news', $this->options['sitemapnews_cat'] );
		}

		if ( ! empty( $this->options['sitemapimg_enable'] ) && ! empty( $this->options['separation_enable'] ) ) {
			$this->check_sitemap_action( 'sitemapimages', 'image' );
		}

		if ( ! empty( $this->options['separation_enable'] ) ) {
			$this->check_sitemap_action( 'sitemap_main', 'main' );
			$post_types = $this->sitemap_db->get_active_post_types();
			if ( ! empty( $post_types ) ) {
				foreach ( $post_types as $post_type ) {
					$this->check_sitemap_action( $post_type . '-sitemap', 'post_type', $post_type );
				}
			}
			$taxonomies = $this->sitemap_db->get_active_taxonomies();
			if ( ! empty( $taxonomies ) ) {
				foreach ( $taxonomies as $taxonomy ) {
					$this->check_sitemap_action( $taxonomy . '-sitemap', 'taxonomy', $taxonomy );
				}
			}
		} else {
			$this->check_sitemap_action( 'sitemap', 'single' );
		}
	}

	/**
	 * Function return the instance of Sitemap type
	 *
	 * @param string      $file    Path to cache file.
	 * @param string      $type    Type of sitemap.
	 * @param string|null $context Post type name of taxonomy name.
	 *
	 * @return void
	 */
	private function create_sitemap_file( $file, $type, $context = null ) {
		if ( 'image' === $type ) {
			$sitemap = new Sitemap_Image( $this->sitemap_db, $this->options, $file, $type );
		} elseif ( 'single' === $type ) {
			$sitemap = new Sitemap_Single( $this->sitemap_db, $this->options, $file, $type );
		} elseif ( 'post_type' === $type ) {
			$sitemap = new Sitemap_Post_Type( $this->sitemap_db, $this->options, $file, $type, $context );
		} elseif ( 'news' === $type ) {
			$sitemap = new Sitemap_News( $this->sitemap_db, $this->options, $file, $type );
		} elseif ( 'main' === $type ) {
			$sitemap = new Sitemap_Main( $this->sitemap_db, $this->options, $file, $type );
		} elseif ( 'taxonomy' === $type ) {
			$sitemap = new Sitemap_Taxonomy( $this->sitemap_db, $this->options, $file, $type, $context );
		}

		if ( ! empty( $sitemap ) ) {
			$sitemap->template();
		}
	}

	/**
	 * Search url in text and change it to current site url
	 *
	 * @param string $xml_string XML string of sitemap.
	 *
	 * @return string
	 */
	private function replace_sitemap_urls( $xml_string ) {
		//TODO тут посмотреть что сделать, я так понимаю эта штука заменяла домены если много зеркал у сайта.
		//TODO Из-за нее неправильно отображается урл уже на фронте в хмл-файле. Не забывай чистить кеш в таблице опшнов и сами файлы удалять хмл
		if ( ! empty( $_SERVER['HTTP_HOST'] ) ) {
			$host = sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) );
		} else {
			$host = wp_parse_url( get_site_url(), PHP_URL_HOST );
		}
		if ( $host ) {
			$xml_string = $this->replace_mirror( $xml_string, $host );
		}

		return $xml_string;
	}

	/**
	 * Sorting and replace mirrors
	 *
	 * @param string $xml  Xml sitemap template.
	 * @param string $host Host for replacing.
	 *
	 * @return string
	 */
	private function replace_mirror( $xml, $host ) {
		$mirrors          = Vo3da_Functions::get_mirrors( get_current_blog_id() );
		$current_protocol = is_ssl() ? 'https://' : 'http://';
		$xml              = str_replace( [ 'http://' . $host . '/', 'https://' . $host . '/' ], $current_protocol . $host . '/', $xml );

		if ( count( $mirrors ) <= 1 ) {
			return $xml;
		}

		usort(
			$mirrors,
			function ( $a, $b ) {
				return strlen( $b ) - strlen( $a );
			}
		);
		foreach ( $mirrors as $mirror ) {
			if ( $mirror === $host ) {
				continue;
			}

		}

		$xml = preg_replace('/(<loc>https?:\/\/.*?\/)/', '<loc>' . $current_protocol . $host . '/', $xml, -1);
		$xml = preg_replace('/(href="https?:\/\/.*?\/)/', 'href="' . $current_protocol . $host . '/', $xml, -1);

		return $xml;
	}

	/**
	 * Print a sitemap from file
	 *
	 * @param string $file Path to file that must be render.
	 *
	 * @return void
	 */
	private function render( $file ) {
		$filemanager = Vo3da_Functions::WP_Filesystem();
		$xml_string  = $filemanager->get_contents( $file );
		$xml_string  = $this->replace_sitemap_urls( $xml_string );
		$xml         = simplexml_load_string( $xml_string );
		header( 'HTTP/1.1 200 OK', true, 200 );
		// Prevent the search engines from indexing the XML Sitemap.
		header( 'X-Robots-Tag: index, follow', true );
		header( 'Content-Type: text/xml' );

		// Make the browser cache this file properly.
		$expires = YEAR_IN_SECONDS;
		header( 'Pragma: public' );
		header( 'Cache-Control: maxage=' . $expires );
		header( 'Expires: ' . gmdate( 'D, d M Y H:i:s', ( time() + $expires ) ) . ' GMT' );
		//phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $xml->asXML();

		exit;
	}

}