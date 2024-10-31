<?php
/**
 * Repository of Sitemap Term.
 * Retrieves content from database for list of sitemap's terms.
 *
 * @package Robots_Sitemap\Core\Db\Term;
 * @since   1.0.0
 */

namespace Robots_Sitemap\Core\Db\Term;

use Robots_Sitemap\Core\Db\Sitemap_DB;
use Exception;
use wpdb;

/**
 * Class Sitemap_Term_Item_Repository
 */
class Sitemap_Term_Item_Repository extends Sitemap_DB {

	/**
	 * Sitemap_Term_Item_Repository constructor.
	 *
	 * @param array $options Sitemap settings.
	 */
	public function __construct( $options ) {
		parent::__construct( $options );
	}

	/**
	 * Get all terms from options cache or db.
	 *
	 * @return array
	 * @throws Exception Exception.
	 */
	public function get_all_terms() {
		$data       = [];
		$taxonomies = $this->get_active_taxonomies();
		if ( ! empty( $taxonomies ) ) {
			foreach ( $taxonomies as $taxonomy ) {
				$cache = get_option( $taxonomy . '_sitemap_items', [] );
				if ( empty( $cache ) ) {
					$data += $this->get_all_terms_by_taxonomy( $taxonomy );
				} else {
					$data += $cache;
				}
			}
		}

		return $data;
	}

	/**
	 * Return an array of terms by taxonomy
	 *
	 * @param string $taxonomy Taxonomy name.
	 *
	 * @return array
	 * @throws Exception Exception.
	 */
	public function get_all_terms_by_taxonomy( $taxonomy ) {

		$data  = [];
		$cache = get_option( $taxonomy . '_sitemap_items', [] );

		if ( ! empty( $cache ) ) {
			return $cache;
		}

		//phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		$sql = "
				SELECT tax.term_id, tax.taxonomy FROM {$this->db->term_taxonomy} as tax
			    LEFT JOIN {$this->db->terms} AS term ON (tax.term_id = term.term_id)
				LEFT JOIN {$this->db->term_relationships} as term_rel ON (tax.term_id = term_rel.term_taxonomy_id)
				WHERE taxonomy = '%s'
				";

		$sql .= 'category' === $taxonomy ? " AND term.slug NOT IN ( 'без-рубрики', 'bez-rubriki', 'bez-kategorii', 'uncategorized' )" : '';

		$terms = $this->db->get_results( $this->db->prepare( $sql, $taxonomy ) );
		//phpcs:enable WordPress.DB.PreparedSQL.NotPrepared
		if ( ! empty( $terms ) ) {
			foreach ( $terms as $term_item ) {
				$term = get_term( $term_item->term_id, $taxonomy );
				if ( null !== $term ) {
					$instance = new Sitemap_Term_Item( $term, $this->options );
					$item     = $instance->item();
					if ( ! empty( $item ) ) {
						$data[ $term_item->term_id ] = $item;
					}
				}
			}
		}
		$this->wpml->reset_to_default_language();

		if ( ! empty( $data ) ) {
			$cache = $data;
			update_option( $taxonomy . '_sitemap_items', $cache, false );
		}

		return $cache;
	}

}
