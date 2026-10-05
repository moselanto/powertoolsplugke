<?php
declare( strict_types=1 );

namespace PowerPlug\Seo;

use PowerPlug\Core\Bootable;

defined( 'ABSPATH' ) || exit;

/**
 * Open Graph + Twitter Card fallbacks (only when no SEO plugin is present).
 */
final class Meta implements Bootable {

	public function boot(): void {
		add_action( 'wp_head', [ $this, 'social' ], 6 );
	}

	public function social(): void {
		$has_seo_plugin = defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) || defined( 'SEOPRESS_VERSION' ) || defined( 'AIOSEO_VERSION' );
		$force_meta     = (bool) apply_filters( 'powerplug_force_meta', false );
		if ( $has_seo_plugin && false === $force_meta ) {
			return;
		}
		$desc = '';
		if ( is_singular() ) {
			$obj = get_queried_object();
			if ( $obj instanceof \WP_Post ) {
				$desc = has_excerpt( $obj ) ? get_the_excerpt( $obj ) : (string) $obj->post_content;
			}
		}
		if ( strlen( $desc ) === 0 ) {
			$desc = (string) get_bloginfo( 'description' );
		}
		$desc = trim( (string) preg_replace( '/\s+/', ' ', wp_strip_all_tags( $desc ) ) );
		if ( strlen( $desc ) > 160 ) {
			$desc = substr( $desc, 0, 157 ) . '...';
		}
		if ( strlen( $desc ) > 0 ) {
			printf( '<meta name="description" content="%s" />' . "\n", esc_attr( $desc ) );
		}
		$title = wp_get_document_title();
		$url   = home_url( add_query_arg( null, null ) );
		$img   = has_post_thumbnail() ? get_the_post_thumbnail_url( null, 'large' ) : '';

		printf( '<meta property="og:site_name" content="%s" />' . "\n", esc_attr( get_bloginfo( 'name' ) ) );
		printf( '<meta property="og:title" content="%s" />' . "\n", esc_attr( $title ) );
		printf( '<meta property="og:type" content="%s" />' . "\n", ( function_exists( 'is_product' ) && is_product() ) ? 'product' : 'website' );
		printf( '<meta property="og:url" content="%s" />' . "\n", esc_url( $url ) );
		if ( $img ) {
			printf( '<meta property="og:image" content="%s" />' . "\n", esc_url( $img ) );
		}
		echo '<meta name="twitter:card" content="summary_large_image" />' . "\n";

		// Canonical for archives/shop; WordPress core already emits it on singular content.
		$canon = '';
		if ( function_exists( 'is_shop' ) && is_shop() && function_exists( 'wc_get_page_permalink' ) ) {
			$canon = (string) wc_get_page_permalink( 'shop' );
		} elseif ( is_category() || is_tag() || is_tax() ) {
			$term = get_queried_object();
			if ( $term instanceof \WP_Term ) {
				$tl    = get_term_link( $term );
				$canon = is_wp_error( $tl ) ? '' : (string) $tl;
			}
		}
		if ( strlen( $canon ) > 0 ) {
			printf( '<link rel="canonical" href="%s" />' . "\n", esc_url( $canon ) );
		}
	}
}
