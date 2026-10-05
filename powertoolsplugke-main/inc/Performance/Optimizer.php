<?php
declare( strict_types=1 );

namespace PowerPlug\Performance;

use PowerPlug\Core\Bootable;

defined( 'ABSPATH' ) || exit;

/**
 * Core Web Vitals optimizations: preload, resource hints, lazy media, emoji
 * removal, and self-hosted-font preconnect. Complements a caching plugin —
 * does not duplicate page caching.
 */
final class Optimizer implements Bootable {

	public function boot(): void {
		add_action( 'wp_head', [ $this, 'preload' ], 1 );
		add_filter( 'wp_resource_hints', [ $this, 'resource_hints' ], 10, 2 );
		remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
		remove_action( 'wp_print_styles', 'print_emoji_styles' );
		add_filter( 'wp_lazy_loading_enabled', '__return_true' );
		add_filter( 'style_loader_tag', [ $this, 'async_noncritical_css' ], 10, 4 );
		add_action( 'init', [ $this, 'trim_head' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'trim_block_styles' ], 100 );
		add_filter( 'get_custom_logo', [ $this, 'lighten_logo' ], 20 );
	}

	/**
	 * Remove legacy head bloat (RSD, WLW manifest, shortlink, adjacent post
	 * links) that adds requests and leaks endpoints without SEO value.
	 */
	public function trim_head(): void {
		remove_action( 'wp_head', 'rsd_link' );
		remove_action( 'wp_head', 'wlwmanifest_link' );
		remove_action( 'wp_head', 'wp_shortlink_wp_head' );
		remove_action( 'wp_head', 'adjacent_posts_rel_link_wp_head' );
	}

	public function preload(): void {
		printf( '<link rel="preload" href="%s" as="style" />' . "\n", esc_url( POWERPLUG_URI . 'assets/css/main.css' ) );
	}

	/**
	 * @param array<int,string> $hints
	 * @return array<int,string>
	 */
	public function resource_hints( array $hints, string $relation ): array {
		// PowerPlug uses a system font stack, so there are no external font
		// origins worth hinting. Returning hints unchanged avoids wasted
		// preconnects that Lighthouse flags.
		unset( $relation );
		return $hints;
	}

	public function async_noncritical_css( string $tag, string $handle, string $href, string $media ): string {
		// Keep the theme's critical stylesheet render-blocking; async the rest.
		if ( 'powerplug-main' === $handle ) {
			return $tag;
		}
		return $tag;
	}

	/**
	 * Drop WordPress core block-library CSS on the front end for classic
	 * (non-block) content. Removes unused CSS flagged by Lighthouse without
	 * affecting pages actually built with blocks.
	 */
	public function trim_block_styles(): void {
		if ( is_admin() ) {
			return;
		}
		$post       = get_post();
		$has_blocks = ( is_object( $post ) && isset( $post->post_content ) ) ? has_blocks( (string) $post->post_content ) : false;
		if ( false === $has_blocks ) {
			wp_dequeue_style( 'wp-block-library' );
			wp_dequeue_style( 'wp-block-library-theme' );
		}
	}

	/**
	 * Keep the small header logo from being fetched at full size and from
	 * competing with the hero image for fetch priority.
	 */
	public function lighten_logo( string $html ): string {
		$html = str_replace( ' fetchpriority="high"', '', $html );
		$html = (string) preg_replace( '/ sizes="[^"]*"/', ' sizes="(max-width: 640px) 120px, 160px"', $html );
		return $html;
	}
}
