<?php

namespace Robots_Sitemap\Core\Robots;

use Robots_Sitemap\Core\Libs\Vo3da_Functions;
use WP_Filesystem_Direct;

/**
 * Class for work with robots.txt file
 *
 * Class Robots_File
 */
class Robots_File {

	/**
	 * Directory for current site.
	 *
	 * @var string
	 */
	private $dir;

	/**
	 * Path to robots.txt file
	 *
	 * @var string
	 */
	private $path;

	/**
	 * WP_Filesystem_Direct object
	 *
	 * @var WP_Filesystem_Direct
	 */
	private $filesystem;

	/**
	 * Custom_Robots_File constructor.
	 *
	 * @param string $domain Domain with which the class will work.
	 */
	public function __construct( $domain ) {
		$this->filesystem = Vo3da_Functions::WP_Filesystem();
		$this->dir        = $this->filesystem->wp_content_dir() . 'uploads/robots-sitemap/robots/' . get_current_blog_id() . '/';
		$this->path       = $this->dir . $this->sanitize_domain( $domain ) . '.txt';
	}

	/**
	 * Delete www from domain
	 *
	 * @param string $domain Not cleared domain.
	 *
	 * @return string
	 */
	private function sanitize_domain( $domain ) {
		return preg_replace( [ '/www./', '`/`' ], [ '', '_' ], $domain );
	}

	/**
	 * Create folder for all robots in this site
	 */
	private function create_folder() {
		if ( ! file_exists( $this->dir ) ) {
			wp_mkdir_p( $this->dir );
		}
	}

	/**
	 * Create robots
	 */
	public function create() {

		$this->create_folder();
		if ( ! file_exists( $this->path ) ) {
			$this->filesystem->put_contents( $this->path, '' );
		}

	}

	/**
	 * Content this robots file
	 *
	 * @return false|string
	 */
	public function content() {
		if ( ! file_exists( $this->path ) ) {
			$this->create();
		}

		return $this->filesystem->get_contents( $this->path );
	}

	/**
	 * Update this robots file
	 *
	 * @param string $content New content for robots file.
	 */
	public function update( $content ) {
		if ( ! file_exists( $this->path ) ) {
			$this->create();
		}
		$this->filesystem->put_contents( $this->path, $content );
	}

	/**
	 * Replace this robots file
	 *
	 * @param string $search  What to replace.
	 * @param string $replace New content.
	 */
	public function replace( $search, $replace ) {
		$content = str_replace( $search, $replace, $this->content() );
		$this->update( $content );
	}

	/**
	 * Get options for robots for domain.
	 *
	 * @param string $domain For which domain to get options.
	 *
	 * @return array|mixed
	 */
	public function get_options( $domain ) {
		$options = get_option( 'vo3da_robots_options' );

		return $options[ $domain ] ? $options[ $domain ] : [];
	}

	/**
	 * Get all options for robots.
	 *
	 * @return array|false
	 */
	public function get_robots_options() {
		return get_option( 'vo3da_robots_options' );
	}

}