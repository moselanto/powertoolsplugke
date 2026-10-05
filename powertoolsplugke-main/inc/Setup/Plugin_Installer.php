<?php
declare( strict_types=1 );

namespace PowerPlug\Setup;

use PowerPlug\Core\Bootable;

defined( 'ABSPATH' ) || exit;

/**
 * Required / recommended plugin manifest + one-click installer.
 *
 * Uses the WordPress Plugins API (plugins_api) and Plugin_Upgrader so plugins
 * install directly from the wordpress.org repository. Premium/commercial plugins
 * are listed as "recommended" with a source URL the site owner supplies.
 */
final class Plugin_Installer implements Bootable {

	public function boot(): void {
		add_action( 'wp_ajax_powerplug_install_plugin', [ $this, 'ajax_install' ] );
	}

	/**
	 * Canonical plugin manifest consumed by the Setup Wizard UI.
	 *
	 * @return array<int, array{slug:string,name:string,required:bool,type:string,source?:string}>
	 */
	public static function manifest(): array {
		return [
			[ 'slug' => 'woocommerce',              'name' => 'WooCommerce',                 'required' => true,  'type' => 'repo' ],
			[ 'slug' => 'elementor',                'name' => 'Elementor',                   'required' => true,  'type' => 'repo' ],
			[ 'slug' => 'contact-form-7',           'name' => 'Contact Form 7',              'required' => false, 'type' => 'repo' ],
			[ 'slug' => 'seo-by-rank-math',         'name' => 'Rank Math SEO',               'required' => false, 'type' => 'repo' ],
			[ 'slug' => 'redis-cache',              'name' => 'Redis Object Cache',          'required' => false, 'type' => 'repo' ],
			[ 'slug' => 'seraphinite-accelerator',   'name' => 'Seraphinite Accelerator (cache & optimize)', 'required' => false, 'type' => 'repo' ],
			[ 'slug' => 'ewww-image-optimizer',     'name' => 'Image Optimizer (EWWW)',      'required' => false, 'type' => 'repo' ],
			[ 'slug' => 'google-listings-and-ads',  'name' => 'Google for WooCommerce',      'required' => false, 'type' => 'repo' ],
			[ 'slug' => 'advanced-custom-fields',   'name' => 'Advanced Custom Fields',      'required' => false, 'type' => 'repo' ],
			[ 'slug' => 'safe-svg',                 'name' => 'Safe SVG',                    'required' => false, 'type' => 'repo' ],
			[ 'slug' => 'woo-variation-swatches',   'name' => 'Variation Swatches',          'required' => false, 'type' => 'repo' ],
			[ 'slug' => 'yith-woocommerce-ajax-search',      'name' => 'AJAX Product Search', 'required' => false, 'type' => 'repo' ],
			[ 'slug' => 'yith-woocommerce-ajax-navigation',  'name' => 'AJAX Filters',        'required' => false, 'type' => 'repo' ],
			[ 'slug' => 'yith-woocommerce-wishlist',         'name' => 'Wishlist',            'required' => false, 'type' => 'repo' ],
			[ 'slug' => 'wp-mail-smtp',             'name' => 'WP Mail SMTP',                'required' => false, 'type' => 'repo' ],
			[ 'slug' => 'updraftplus',              'name' => 'UpdraftPlus Backups',         'required' => false, 'type' => 'repo' ],
			[ 'slug' => 'wordfence',                'name' => 'Wordfence Security',          'required' => false, 'type' => 'repo' ],
		];
	}

	public function ajax_install(): void {
		check_ajax_referer( 'powerplug_wizard', 'nonce' );
		if ( ! current_user_can( 'install_plugins' ) ) {
			wp_send_json_error( [ 'message' => __( 'Insufficient permissions.', 'powerplug' ) ], 403 );
		}

		$slug = isset( $_POST['slug'] ) ? sanitize_key( wp_unslash( $_POST['slug'] ) ) : '';
		if ( '' === $slug ) {
			wp_send_json_error( [ 'message' => __( 'Missing plugin slug.', 'powerplug' ) ], 400 );
		}

		$result = $this->install_and_activate( $slug );
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( [ 'message' => $result->get_error_message() ] );
		}
		wp_send_json_success( [ 'slug' => $slug, 'status' => 'active' ] );
	}

	/**
	 * Install from wp.org (if needed) and activate.
	 *
	 * @return true|\WP_Error
	 */
	public function install_and_activate( string $slug ) {
		// Bundled or custom plugins are not on wordpress.org; skip gracefully so the wizard continues.
		foreach ( self::manifest() as $pp_entry ) {
			if ( $pp_entry['slug'] === $slug ) {
				$pp_type = isset( $pp_entry['type'] ) ? $pp_entry['type'] : 'repo';
				if ( 'repo' === $pp_type ) {
					break;
				}
				return true;
			}
		}
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/misc.php';

		$installed = $this->find_installed_plugin_file( $slug );

		if ( null === $installed ) {
			$api = plugins_api( 'plugin_information', [ 'slug' => $slug, 'fields' => [ 'sections' => false ] ] );
			if ( is_wp_error( $api ) ) {
				return $api;
			}
			$upgrader = new \Plugin_Upgrader( new \WP_Ajax_Upgrader_Skin() );
			$ok       = $upgrader->install( $api->download_link );
			if ( is_wp_error( $ok ) ) {
				return $ok;
			}
			$installed = $this->find_installed_plugin_file( $slug );
		}

		if ( null === $installed ) {
			return new \WP_Error( 'not_found', __( 'Plugin could not be located after install.', 'powerplug' ) );
		}

		$activate = activate_plugin( $installed );
		return is_wp_error( $activate ) ? $activate : true;
	}

	private function find_installed_plugin_file( string $slug ): ?string {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		foreach ( get_plugins() as $file => $data ) {
			if ( str_starts_with( $file, $slug . '/' ) || $file === $slug . '.php' ) {
				return $file;
			}
		}
		return null;
	}
}
