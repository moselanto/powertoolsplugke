<?php
/**
 * PowerPlug Pro Child - functions.
 *
 * @package PowerPlugChild
 */

defined( 'ABSPATH' ) || exit;

/**
 * Enqueue the parent stylesheet, then the child stylesheet.
 */
add_action(
	'wp_enqueue_scripts',
	static function (): void {
		$pp_child_ver = wp_get_theme()->get( 'Version' );
		wp_enqueue_style( 'powerplug-parent', get_template_directory_uri() . '/style.css', [], null );
		wp_enqueue_style( 'powerplug-child', get_stylesheet_uri(), [ 'powerplug-parent' ], $pp_child_ver );
	},
	30
);

/**
 * Example: override store contact details without touching the parent.
 */
add_filter(
	'powerplug_business_details',
	static function ( array $details ): array {
		// $details['phone'] = '+254 700 000000';
		return $details;
	}
);
