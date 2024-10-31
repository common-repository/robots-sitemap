<?php
/**
 * Term caching
 *
 * @since   1.0.0
 * @package Robots_Sitemap\Core\Cache;
 */

namespace Robots_Sitemap\Core\Cache;

use Robots_Sitemap\Core\Db\Term\Sitemap_Term_Item;
use Exception;
use WP_Error;
use WP_Term;

/**
 * Class Sitemap_Cache_Term
 *
 * @package Robots_Sitemap\Core\Cache
 */
class Sitemap_Cache_Term extends Sitemap_Cache {

	/**
	 * Term ID
	 *
	 * @var int
	 */
	private $id;
	/**
	 * Term taxonomy
	 *
	 * @var string
	 */
	private $taxonomy;
	/**
	 * Plugins options
	 *
	 * @var array
	 */
	protected $options;
	/**
	 * WP_Term object
	 *
	 * @var array|WP_Error|WP_Term|null
	 */
	private $term;

	/**
	 * Sitemap_Cache_Term constructor.
	 *
	 * @param int    $id Term ID.
	 * @param string $taxonomy Term taxonomy.
	 * @param array  $options Plugins options.
	 *
	 * @throws Exception Exception.
	 */
	public function __construct( $id, $taxonomy, $options ) {
		$this->id       = $id;
		$this->taxonomy = $taxonomy;
		$this->options  = $options;
		//TODO оставить на время теста. Чтобы видеть кейс когда ломается
		try {
			$this->term = $this->create_cache_term();
		} catch ( Exception $e ) {
			wp_die( $e->getMessage() );
		}
		parent::__construct( $id, $options );
		$this->repository_item = new Sitemap_Term_Item( $this->term, $this->options );
	}

	private function create_cache_term() {
		$term = get_term( $this->id, $this->taxonomy );
		if ( null === $term ) {
			throw new Exception( 'Cannot create object with such params: ' . $this->id . ' ' . $this->taxonomy );
		}

		return $term;
	}

	/**
	 * Update existing cache
	 *
	 * @throws Exception Exception.
	 */
	protected function update() {
		$cache = $this->get_cache_data();
		if ( null !== $this->term ) {
			if ( $this->check_index() ) {
				if ( empty( $cache ) ) {
					$this->factory->generate();
					exit();
				}
				$cache[ $this->id ] = $this->prepare_data();
				$this->set_cache_data( $cache );
			} else {
				$this->delete( $cache );
			}
		} else {
			$this->delete( $cache );
		}
	}

	/**
	 * Get taxonomy cache
	 *
	 * @return array
	 */
	protected function get_cache_data() {
		return get_option( $this->taxonomy . '_sitemap_items', [] );
	}

	/**
	 * Set taxonomy cache
	 *
	 * @param array $cache Cache data.
	 *
	 * @return void
	 */
	protected function set_cache_data( $cache ) {
		update_option( $this->taxonomy . '_sitemap_items', $cache, false );
	}

	/**
	 * Whether caching is allowed for current term
	 *
	 * @return bool
	 */
	public function check_index() {
		if ( null !== $this->repository_item ) {
			return $this->repository_item->check_index( $this->id, $this->taxonomy );
		} else {
			return false;
		}
	}

	/**
	 * Formatting data for cache
	 *
	 * @return array
	 * @throws Exception Exception.
	 */
	public function prepare_data() {
		if ( null !== $this->repository_item ) {
			return $this->repository_item->item_fields();
		} else {
			return [];
		}
	}

}

