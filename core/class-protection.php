<?php
/**
 * Class Protection for hide robots and sitemap from fake bots.
 *
 * @since   1.0.0
 * @package Robots_Sitemap\Core;
 */

namespace Robots_Sitemap\Core;

/**
 * Class WPML for multilanguage processing.
 *
 * @package Robots_Sitemap\Core
 */
class Protection {

	/**
	 * Array of plugin settings
	 *
	 * @var array
	 */
	public $options;
	/**
	 * Current user IP address
	 *
	 * @var string
	 */
	public $user_ip;
	/**
	 * Current user host by IP
	 *
	 * @var string
	 */
	public $user_host;
	/**
	 * Current user-agent
	 *
	 * @var string
	 */
	public $user_agent;
	/**
	 * Check for google bot
	 *
	 * @var bool
	 */
	private $is_googlebot;
	/**
	 * Check for google bot
	 *
	 * @var bool
	 */
	private $is_yandexbot;

	/**
	 * Protection constructor.
	 *
	 * @param array $options Array of plugin settings.
	 */
	public function __construct( $options ) {

		$this->options      = $options;
		$this->user_ip      = $this->user_ip();
		$this->user_host    = $this->user_host();
		$this->user_agent   = $this->user_agent();
		$this->is_googlebot = $this->is_googlebot();
		$this->is_yandexbot = $this->is_yandexbot();
	}

	/**
	 * @return bool
	 */
	private function is_googlebot() {
		return (bool) preg_match( '/(Googlebot|Mediapartners-Google|AdsBot-Google)/', $this->user_agent );
	}

	/**
	 * @return bool
	 */
	private function is_yandexbot() {
		return (bool) preg_match( '/(YandexBot|YandexImages|YandexWebmaster|YandexMedia|YandexBlogs|YandexDirect|YandexCatalog)/', $this->user_agent );
	}

	/**
	 * @return bool
	 */
	private function bot_host_verify() {
		if ( ! $this->user_host ) {
			return false;
		}

		return (bool) preg_match( '/\.((?:google(?:bot)?|yandex)\.(?:com|ru|net|org))$/', $this->user_host );
	}

	/**
	 * DNS Lookup
	 *
	 * @return bool
	 */
	private function is_dns_lookup() {
		return gethostbyname( $this->user_host ) === $this->user_ip;
	}

	private function verify_bot() {
		if ( ! $this->bot_host_verify() || ! $this->is_dns_lookup() ) {
			return false;
		}

		return true;

	}

	/**
	 * Check for fake bot.
	 *
	 * @return bool
	 */
	public function is_fake_bot() {
		return ( ( $this->is_yandexbot || $this->is_googlebot ) && ! $this->verify_bot() );
	}

	/**
	 * User IP
	 *
	 * @return string
	 */
	private function user_ip() {
		//phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		//phpcs:disable WordPress.Security.ValidatedSanitizedInput.MissingUnslash
		if ( ! empty( $this->user_ip ) ) {
			return $this->user_ip;
		} elseif ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
			return stripslashes( $_SERVER['HTTP_CLIENT_IP'] );
		} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			return stripslashes( $_SERVER['HTTP_X_FORWARDED_FOR'] );
		} elseif ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
			return stripslashes( $_SERVER['REMOTE_ADDR'] );
		} else {
			return '';
		}
		//phpcs:enable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		//phpcs:enable WordPress.Security.ValidatedSanitizedInput.MissingUnslash
	}

	private function user_host() {
		$host = gethostbyaddr( $this->user_ip() );

		return $host ? $host : false;
	}

	/**
	 * Get current user-agent
	 *
	 * @return string user agent.
	 */
	public function user_agent() {
		return isset( $_SERVER['HTTP_USER_AGENT'] ) ? filter_var( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ), FILTER_SANITIZE_STRING ) : '';
	}

}
