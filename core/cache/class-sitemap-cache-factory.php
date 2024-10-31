<?php
/**
 * Cache Factory. Manages caching classes.
 *
 * @since   1.0.0
 * @package Robots_Sitemap\Core\Cache;
 */

namespace Robots_Sitemap\Core\Cache;

use Exception;

/**
 * Class Sitemap_Cache_Factory
 *
 * @package Robots_Sitemap\Core\Cache
 */
class Sitemap_Cache_Factory {

	/**
	 * ID of post or term.
	 *
	 * @var int
	 */
	private $id;
	/**
	 * Post type name of taxonomy name.
	 *
	 * @var string
	 */
	private $name;
	/**
	 * Type of object post, term, attachment, news.
	 *
	 * @var string
	 */
	private $object;
	/**
	 * Cache method to be executed
	 *
	 * @var string
	 */
	private $method;
	/**
	 * Plugin settings
	 *
	 * @var array
	 */
	private $options;

	/**
	 * Sitemap_Cache constructor.
	 *
	 * @param int    $id      ID of post or term.
	 * @param string $name    Post type name of taxonomy name.
	 * @param string $object  Type of object post, term, attachment, news.
	 * @param string $method  Cache method to be executed.
	 * @param array  $options Plugin options.
	 */
	public function __construct( $id, $name, $object, $method, $options ) {
		$this->id      = $id;
		$this->name    = $name;
		$this->object  = $object;
		$this->method  = $method;
		$this->options = $options;
	}

	/**
	 * Cache initialization
	 *
	 * @throws Exception Exception.
	 */
	public function init() {
		if ( 'post' === $this->object ) {
			$cache = new Sitemap_Cache_Post( $this->id, $this->name, $this->method, $this->options );
		} elseif ( 'attachment' === $this->object ) {
			$cache = new Sitemap_Cache_Attachment( $this->id, $this->options );
		} elseif ( 'news' === $this->object ) {
			$cache = new Sitemap_Cache_News( $this->id, $this->name, $this->options );
		} elseif ( 'term' === $this->object && 'nav_menu' !== $this->name ) {
			$cache = new Sitemap_Cache_Term( $this->id, $this->name, $this->options );
		}
		if ( isset( $cache ) ) {
			$cache->execute( $this->method );
		}
	}

}
