<?php
declare( strict_types=1 );

namespace PowerPlug\Security;

use PowerPlug\Core\Bootable;

defined( 'ABSPATH' ) || exit;

/**
 * Baseline OWASP security headers + hardening. Edge WAF/bot mitigation is
 * handled by Cloudflare and host-level IDS (Imunify); this covers header
 * hygiene, information-disclosure reduction, pingback/XML-RPC amplification
 * vectors, and username enumeration.
 */
final class Headers implements Bootable {

	public function boot(): void {
		add_filter( 'wp_headers', [ $this, 'headers' ] );
		remove_action( 'wp_head', 'wp_generator' );
		add_filter( 'the_generator', '__return_empty_string' );
		add_filter( 'xmlrpc_enabled', '__return_false' );

		// Reduce information disclosure in <head>.
		remove_action( 'wp_head', 'rsd_link' );
		remove_action( 'wp_head', 'wlwmanifest_link' );
		remove_action( 'wp_head', 'wp_shortlink_wp_head' );
		remove_action( 'wp_head', 'adjacent_posts_rel_link_wp_head' );

		// Neutralise pingback vectors (DDoS amplification + comment spam).
		add_filter( 'xmlrpc_methods', [ $this, 'strip_pingback' ] );
		add_filter( 'wp_headers', [ $this, 'drop_pingback_header' ], 11 );
		add_action( 'pre_ping', [ $this, 'block_self_ping' ] );

		// Block username enumeration (?author=N and the REST users route for guests).
		add_action( 'template_redirect', [ $this, 'block_author_scan' ] );
		add_filter( 'rest_endpoints', [ $this, 'restrict_user_endpoints' ] );
	}

	/**
	 * @param array<string,string> $headers
	 * @return array<string,string>
	 */
	public function headers( array $headers ): array {
		$headers['X-Content-Type-Options']    = 'nosniff';
		$headers['X-Frame-Options']           = 'SAMEORIGIN';
		$headers['Referrer-Policy']           = 'strict-origin-when-cross-origin';
		$headers['Permissions-Policy']        = 'geolocation=(), microphone=(), camera=()';
		$headers['Strict-Transport-Security'] = 'max-age=31536000; includeSubDomains';
		return $headers;
	}

	/**
	 * @param array<string,mixed> $methods
	 * @return array<string,mixed>
	 */
	public function strip_pingback( array $methods ): array {
		unset( $methods['pingback.ping'], $methods['pingback.extensions.getPingbacks'] );
		return $methods;
	}

	/**
	 * @param array<string,string> $headers
	 * @return array<string,string>
	 */
	public function drop_pingback_header( array $headers ): array {
		if ( isset( $headers['X-Pingback'] ) ) {
			unset( $headers['X-Pingback'] );
		}
		return $headers;
	}

	/**
	 * @param array<int,string> $links
	 */
	public function block_self_ping( &$links ): void {
		$home = home_url();
		foreach ( array_keys( (array) $links ) as $key ) {
			if ( is_string( $links[ $key ] ) && strpos( $links[ $key ], $home ) === 0 ) {
				unset( $links[ $key ] );
			}
		}
	}

	public function block_author_scan(): void {
		if ( is_admin() ) {
			return;
		}
		if ( isset( $_GET['author'] ) && is_user_logged_in() === false ) {
			wp_safe_redirect( home_url( '/' ), 301 );
			exit;
		}
	}

	/**
	 * @param array<string,mixed> $endpoints
	 * @return array<string,mixed>
	 */
	public function restrict_user_endpoints( array $endpoints ): array {
		if ( is_user_logged_in() ) {
			return $endpoints;
		}
		foreach ( array_keys( $endpoints ) as $route ) {
			if ( is_string( $route ) && strpos( $route, '/wp/v2/users' ) === 0 ) {
				unset( $endpoints[ $route ] );
			}
		}
		return $endpoints;
	}
}
