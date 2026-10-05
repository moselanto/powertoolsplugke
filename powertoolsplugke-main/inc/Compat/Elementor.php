<?php
declare( strict_types=1 );

namespace PowerPlug\Compat;

use PowerPlug\Core\Bootable;

defined( 'ABSPATH' ) || exit;

/**
 * Elementor + Elementor Pro foundation.
 *
 * Registers Theme Builder locations, hands colour/typography control to the
 * PowerPlug Pro design tokens so Elementor content inherits the theme look,
 * and applies lean performance defaults (DOM optimiser, external CSS,
 * disabled default schemes).
 */
final class Elementor implements Bootable {

	public function boot(): void {
		add_action( 'after_setup_theme', [ $this, 'content_width' ] );
		add_action( 'elementor/theme/register_locations', [ $this, 'locations' ] );
		add_action( 'admin_init', [ $this, 'maybe_defaults' ] );
	}

	private function active(): bool {
		return did_action( 'elementor/loaded' ) > 0 || class_exists( '\Elementor\Plugin' );
	}

	public function content_width(): void {
		if ( isset( $GLOBALS['content_width'] ) === false ) {
			$GLOBALS['content_width'] = 1320;
		}
	}

	/**
	 * Let Elementor Pro Theme Builder own header/footer/single/archive.
	 *
	 * @param mixed $manager Elementor locations manager.
	 */
	public function locations( $manager ): void {
		if ( is_object( $manager ) && method_exists( $manager, 'register_all_core_locations' ) ) {
			$manager->register_all_core_locations();
		}
	}

	/**
	 * Apply performance-friendly Elementor defaults once, when Elementor is present.
	 */
	public function maybe_defaults(): void {
		if ( get_option( 'powerplug_elementor_tuned' ) === 'yes' ) {
			return;
		}
		if ( $this->active() === false ) {
			return;
		}
		update_option( 'elementor_disable_color_schemes', 'yes' );
		update_option( 'elementor_disable_typography_schemes', 'yes' );
		update_option( 'elementor_optimized_dom_output', 'enabled' );
		update_option( 'elementor_css_print_method', 'external' );
		update_option( 'powerplug_elementor_tuned', 'yes' );
	}
}
