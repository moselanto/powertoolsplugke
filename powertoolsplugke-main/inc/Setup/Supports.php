<?php
declare( strict_types=1 );

namespace PowerPlug\Setup;

use PowerPlug\Core\Bootable;

defined( 'ABSPATH' ) || exit;

/**
 * Declares theme feature support (FSE, Woo, media, editor).
 */
final class Supports implements Bootable {

	public function boot(): void {
		add_action( 'after_setup_theme', [ $this, 'register' ] );
	}

	public function register(): void {
		add_theme_support( 'title-tag' );
		add_theme_support( 'post-thumbnails' );
		add_theme_support( 'html5', [ 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script', 'navigation-widgets' ] );
		add_theme_support( 'custom-logo', [ 'height' => 60, 'width' => 200, 'flex-height' => true, 'flex-width' => true ] );
		add_theme_support( 'responsive-embeds' );
		add_theme_support( 'align-wide' );
		add_theme_support( 'editor-styles' );
		add_theme_support( 'wp-block-styles' );

		// WooCommerce.
		add_theme_support( 'woocommerce', [
			'thumbnail_image_width' => 600,
			'single_image_width'    => 1200,
			'product_grid'          => [ 'default_columns' => 4, 'min_columns' => 2, 'max_columns' => 5 ],
		] );
		add_theme_support( 'wc-product-gallery-zoom' );
		add_theme_support( 'wc-product-gallery-lightbox' );
		add_theme_support( 'wc-product-gallery-slider' );

		register_nav_menus( [
			'primary'       => __( 'Primary Menu', 'powerplug' ),
			'mega'          => __( 'Mega Menu', 'powerplug' ),
			'hero-categories' => __( 'Hero Categories', 'powerplug' ),
			'mobile-bottom' => __( 'Mobile Bottom Navigation', 'powerplug' ),
			'footer'        => __( 'Footer Menu', 'powerplug' ),
		] );
	}
}
