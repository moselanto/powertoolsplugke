<?php
declare( strict_types=1 );

namespace PowerPlug\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Central service container / bootstrapper.
 *
 * Follows a lightweight dependency-injection pattern: every feature module is a
 * class implementing Bootable::boot(). Adding a feature = registering it here.
 */
final class Theme {

	/**
	 * Feature modules, resolved lazily.
	 *
	 * @return array<int, class-string>
	 */
	private function modules(): array {
		return [
			\PowerPlug\Core\Assets::class,
			\PowerPlug\Setup\Supports::class,
			\PowerPlug\Setup\Menus::class,
			\PowerPlug\Setup\Setup_Wizard::class,
			\PowerPlug\Setup\Plugin_Installer::class,
			\PowerPlug\Woo\WooCommerce::class,
			\PowerPlug\Woo\QuickView::class,
			\PowerPlug\Woo\Filters::class,
			\PowerPlug\Woo\Product_Identifiers::class,
			\PowerPlug\Seo\Schema::class,
			\PowerPlug\Seo\Meta::class,
			\PowerPlug\Performance\Optimizer::class,
			\PowerPlug\Security\Headers::class,
			\PowerPlug\Admin\Dashboard::class,
			\PowerPlug\Admin\ProductList::class,
			\PowerPlug\Compat\Elementor::class,
			\PowerPlug\Customizer\Customizer::class,
			\PowerPlug\Search\AjaxSearch::class,
			\PowerPlug\Front\LandingPage::class,
			\PowerPlug\Front\Trust::class,
		];
	}

	public function boot(): void {
		load_theme_textdomain( 'powerplug', POWERPLUG_DIR . 'languages' );

		foreach ( $this->modules() as $module ) {
			if ( class_exists( $module ) ) {
				$instance = new $module();
				if ( $instance instanceof Bootable ) {
					$instance->boot();
				}
			}
		}
	}
}
