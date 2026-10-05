<?php
declare( strict_types=1 );

namespace PowerPlug\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Asset pipeline: versioned, deferred, critical-CSS aware.
 */
final class Assets implements Bootable {

	public function boot(): void {
		add_action( 'wp_enqueue_scripts', [ $this, 'front' ], 20 );
		add_filter( 'script_loader_tag', [ $this, 'defer' ], 10, 3 );
	}

	public function front(): void {
		$ver = POWERPLUG_VERSION;

		wp_enqueue_style( 'powerplug-main', POWERPLUG_URI . 'assets/css/main.css', [], $ver );
		wp_style_add_data( 'powerplug-main', 'rtl', 'replace' );

		wp_enqueue_script( 'powerplug-app', POWERPLUG_URI . 'assets/js/app.js', [], $ver, true );
		wp_localize_script(
			'powerplug-app',
			'PowerPlug',
			[
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'powerplug' ),
				'restUrl' => esc_url_raw( rest_url() ),
				'wcAjax'  => class_exists( '\WC_AJAX' ) ? \WC_AJAX::get_endpoint( 'add_to_cart' ) : '',
			]
		);
	}

	/**
	 * Defer all theme JavaScript to keep the main thread free (INP/TBT).
	 */
	public function defer( string $tag, string $handle, string $src ): string {
		if ( in_array( $handle, [ 'powerplug-app' ], true ) ) {
			return str_replace( ' src', ' defer src', $tag );
		}
		return $tag;
	}
}
