<?php
declare( strict_types=1 );

namespace PowerPlug\Setup;

use PowerPlug\Core\Bootable;

defined( 'ABSPATH' ) || exit;

/**
 * Registers widget areas used by the header/footer builders.
 */
final class Menus implements Bootable {

	public function boot(): void {
		add_action( 'widgets_init', [ $this, 'sidebars' ] );
	}

	public function sidebars(): void {
		foreach ( [ 'footer-1', 'footer-2', 'footer-3', 'footer-4', 'shop-filters' ] as $id ) {
			register_sidebar( [
				'name'          => ucwords( str_replace( '-', ' ', $id ) ),
				'id'            => $id,
				'before_widget' => '<section id="%1$s" class="widget %2$s">',
				'after_widget'  => '</section>',
				'before_title'  => '<h3 class="widget-title">',
				'after_title'   => '</h3>',
			] );
		}
	}
}
