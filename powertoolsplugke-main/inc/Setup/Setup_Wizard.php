<?php
declare( strict_types=1 );

namespace PowerPlug\Setup;

use PowerPlug\Core\Bootable;

defined( 'ABSPATH' ) || exit;

/**
 * Premium ThemeForest-style onboarding wizard.
 *
 * Steps: Welcome -> Install Plugins -> Activate -> Import Demo -> Finish.
 * Renders on a dedicated hidden admin page and redirects on first activation.
 */
final class Setup_Wizard implements Bootable {

	private const OPTION_DONE = 'powerplug_wizard_complete';

	public function boot(): void {
		add_action( 'admin_menu', [ $this, 'register_page' ] );
		add_action( 'admin_init', [ $this, 'maybe_redirect' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'assets' ] );
		add_action( 'after_switch_theme', [ $this, 'flag_fresh_activation' ] );
		add_action( 'wp_ajax_powerplug_import_demo', [ $this, 'ajax_import_demo' ] );
		add_action( 'wp_ajax_powerplug_finish_wizard', [ $this, 'ajax_finish' ] );
		add_action( 'admin_notices', [ $this, 'launch_notice' ] );
	}

	public function flag_fresh_activation(): void {
		set_transient( 'powerplug_activation_redirect', 1, 60 );
	}

	public function maybe_redirect(): void {
		if ( ! get_transient( 'powerplug_activation_redirect' ) ) {
			return;
		}
		delete_transient( 'powerplug_activation_redirect' );
		if ( ! isset( $_GET['activate-multi'] ) && current_user_can( 'manage_options' ) ) {
			wp_safe_redirect( admin_url( 'themes.php?page=powerplug-setup' ) );
			exit;
		}
	}

	public function launch_notice(): void {
		if ( get_option( self::OPTION_DONE ) ) {
			return;
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( $screen && 'appearance_page_powerplug-setup' === $screen->id ) {
			return;
		}
		$url = admin_url( 'themes.php?page=powerplug-setup' );
		echo '<div class="notice notice-info is-dismissible"><p><strong>PowerPlug</strong> &mdash; ' .
			esc_html__( 'Finish setup: install the required plugins and import demo content.', 'powerplug' ) .
			' <a class="button button-primary" href="' . esc_url( $url ) . '">' .
			esc_html__( 'Launch Setup Wizard', 'powerplug' ) . '</a></p></div>';
	}

	public function register_page(): void {
		add_submenu_page(
			'themes.php',
			__( 'PowerPlug Setup', 'powerplug' ),
			__( 'PowerPlug Setup', 'powerplug' ),
			'manage_options',
			'powerplug-setup',
			[ $this, 'render' ]
		);
	}

	public function assets( string $hook ): void {
		if ( 'appearance_page_powerplug-setup' !== $hook ) {
			return;
		}
		wp_enqueue_style( 'powerplug-wizard', POWERPLUG_URI . 'assets/css/wizard.css', [], POWERPLUG_VERSION );
		wp_enqueue_script( 'powerplug-wizard', POWERPLUG_URI . 'assets/js/wizard.js', [ 'wp-i18n' ], POWERPLUG_VERSION, true );
		wp_localize_script(
			'powerplug-wizard',
			'PowerPlugWizard',
			[
				'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'powerplug_wizard' ),
				'plugins'  => Plugin_Installer::manifest(),
				'finishUrl'=> admin_url( 'admin.php?page=powerplug-dashboard' ),
			]
		);
	}

	public function render(): void {
		require POWERPLUG_DIR . 'inc/Setup/views/wizard.php';
	}

	/**
	 * Import bundled demo content (products, categories, menus, Elementor
	 * templates and policy pages) from a WXR/JSON manifest.
	 */
	public function ajax_import_demo(): void {
		check_ajax_referer( 'powerplug_wizard', 'nonce' );
		if ( ! current_user_can( 'import' ) ) {
			wp_send_json_error( [ 'message' => __( 'Insufficient permissions.', 'powerplug' ) ], 403 );
		}
		$step = isset( $_POST['part'] ) ? sanitize_key( wp_unslash( $_POST['part'] ) ) : 'all';

		$importer = new Demo_Importer();
		$result   = $importer->import( $step );
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( [ 'message' => $result->get_error_message() ] );
		}
		wp_send_json_success( $result );
	}

	public function ajax_finish(): void {
		check_ajax_referer( 'powerplug_wizard', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [], 403 );
		}
		update_option( self::OPTION_DONE, true );
		wp_send_json_success();
	}
}
