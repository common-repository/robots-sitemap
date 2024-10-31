<?php
/**
 * Abstract Sitemap
 *
 * @since      1.0.0
 *
 * @package    Robots_Sitemap\Front\Sitemap
 */

namespace Robots_Sitemap\Core\Sitemap;

use Robots_Sitemap\Core\Db\Sitemap_DB;
use Robots_Sitemap\Core\Libs\Vo3da_Functions;

/**
 * Class Sitemap
 *
 * @package Robots_Sitemap\Front\Sitemap
 */
abstract class Sitemap {

	/**
	 * Plugin settings
	 *
	 * @var array
	 */
	protected $options;
	/**
	 * Filename
	 *
	 * @var string
	 */
	protected $filename;
	/**
	 * Type
	 *
	 * @var string
	 */
	protected $type;
	/**
	 * Post type or taxonomy name
	 *
	 * @var string
	 */
	protected $name;
	/**
	 * Header link name
	 *
	 * @var string
	 */
	protected $link;
	/**
	 * Template for images
	 *
	 * @var string
	 */
	protected $image;
	/**
	 * Header part of sitemap template
	 *
	 * @var string
	 */
	protected $template;
	/**
	 * Instance of Sitemap DB.
	 *
	 * @var Sitemap_DB
	 */
	protected $sitemap_db;

	/**
	 * Sitemap_Single constructor.
	 *
	 * @param Sitemap_DB $sitemap_db Instance of Sitemap Db.
	 * @param array      $options    Plugin settings.
	 * @param string     $filename   Filename.
	 * @param string     $type       Type.
	 * @param string     $name       Post type or taxonomy name.
	 */
	public function __construct( Sitemap_DB $sitemap_db, $options, $filename, $type, $name = '' ) {
		$this->sitemap_db = $sitemap_db;
		$this->options    = $options;
		$this->filename   = $filename;
		$this->type       = $type;
		$this->name       = $name;
		$this->link       = $this->get_image_type();
		$stylesheet_url   = $this->get_stylesheet_url( $this->link );

		$this->image    = ( ! empty( $this->options['img_enable'] ) ) ? ' xmlns:image="http://www.google.com/schemas/sitemap-image/1.1"' : '';
		$this->template = '<?xml version="1.0" encoding="UTF-8"?><?xml-stylesheet type="text/xsl" href="' . $stylesheet_url . '"?>';
	}

	/**
	 * Get header link name
	 *
	 * @return string
	 */
	private function get_image_type() {
		$link = 'main';
		if ( empty( $this->options['separation_enable'] ) ) {
			$link = 'single';
		}
		if ( ! empty( $this->options['sitemapimg_enable'] ) && 'image' === $this->type ) {
			$link = 'images';
		}
		if ( 'news' === $this->type ) {
			$link = 'news';
		}

		return $link;
	}

	/**
	 * Array of data that can be rendered
	 *
	 * @return array
	 */
	abstract protected function data();

	/**
	 * Return the header of sitemap
	 *
	 * @return string
	 */
	protected function header() {
		$template = $this->template;

		$template .= '<urlset xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd" xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"' . $this->image . '>';

		return $template;
	}

	/**
	 * Contains a footer of sitemap
	 *
	 * @return string
	 */
	protected function footer() {
		return '</urlset>';
	}

	/**
	 * Return the string of sitemap template's item
	 *
	 * @param array $item An array of sitemap template's item.
	 *
	 * @return string
	 */
	protected function item_template( $item ) {
		$xml = '<url>';

		$xml .= '<loc>' . apply_filters( 'sitemap_url', $item['url'] ) . '</loc>';
		if ( ! empty( $this->options['img_enable'] ) && ! empty( $item['img'] ) ) {
			$xml .= '<image:image><image:loc>' . $item['img'] . '</image:loc></image:image>';
		}
		$xml .= '<lastmod>' . apply_filters( 'sitemap_date_format', $item['last_modify'] ) . '</lastmod>';
		$xml .= '<changefreq>' . $item['frequencies'] . '</changefreq>';
		$xml .= '<priority>' . $item['prioriti'] . '</priority>';
		$xml .= '</url>';

		return $xml;
	}

	/**
	 * Generate template
	 *
	 * @return void
	 */
	public function template() {
		$data = $this->data();

		$template = $this->header();

		if ( $data ) {
			foreach ( $data as $item ) {
				$template .= $this->item_template( $item );
			}
		}

		$template .= $this->footer();
		$this->save( $template );
	}

	/**
	 * Save sitemap file
	 *
	 * @param string $content Content.
	 */
	private function save( $content ) {
		$filesystem = Vo3da_Functions::WP_Filesystem();
		$filesystem->put_contents( $this->filename, $content );
	}

	/**
	 * Geneate relative (http\https) url for xml stylesheet.
	 *
	 * @param string $link current xml.
	 *
	 * @return string
	 */
	private function get_stylesheet_url( string $link ) {
		return plugins_url( 'front/template-xml/' . $this->link . '-sitemap.xml', dirname( __FILE__, 2 ) );
	}

}
