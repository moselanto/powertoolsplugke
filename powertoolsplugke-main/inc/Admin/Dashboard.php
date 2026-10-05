<?php
declare( strict_types=1 );

namespace PowerPlug\Admin;

use PowerPlug\Core\Bootable;

defined( 'ABSPATH' ) || exit;

/**
 * PowerPlug control-center admin page: health scores + quick links.
 * Real metrics are supplied by the Merchant Compliance Inspector plugin via the
 * `powerplug_health_scores` filter; the theme renders whatever is available.
 */
final class Dashboard implements Bootable {

	public function boot(): void {
		add_action( 'admin_menu', [ $this, 'menu' ] );
	}

	public function menu(): void {
		add_menu_page(
			__( 'PowerPlug', 'powerplug' ),
			__( 'PowerPlug', 'powerplug' ),
			'manage_options',
			'powerplug-dashboard',
			[ $this, 'render' ],
			'dashicons-superhero',
			3
		);
	}

	public function render(): void {
		/**
		 * @var array<string,int> $scores health scores 0-100 keyed by area
		 */
		$scores = apply_filters( 'powerplug_health_scores', [
			'merchant' => 0,
			'seo'      => 0,
			'speed'    => 0,
			'security' => 0,
		] );

		echo '<div class="wrap"><h1>' . esc_html__( 'PowerPlug Control Center', 'powerplug' ) . '</h1>';
		echo '<div class="pp-cards" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px;margin-top:20px">';
		foreach ( $scores as $label => $value ) {
			printf(
				'<div class="pp-card" style="background:#fff;border:1px solid #E4E8EC;border-radius:12px;padding:20px"><div style="font-size:32px;font-weight:700">%d</div><div style="text-transform:capitalize;color:#5A636C">%s %s</div></div>',
				(int) $value,
				esc_html( $label ),
				esc_html__( 'health', 'powerplug' )
			);
		}
		echo '</div>';
		echo '<p style="margin-top:20px"><a class="button button-primary" href="' . esc_url( admin_url( 'admin.php?page=powerplug-setup' ) ) . '">' . esc_html__( 'Re-run Setup Wizard', 'powerplug' ) . '</a> ';
		echo '<a class="button" href="' . esc_url( admin_url( 'admin.php?page=merchant-compliance-inspector' ) ) . '">' . esc_html__( 'Open Merchant Compliance Inspector', 'powerplug' ) . '</a></p>';
		echo '</div>';
	}
}
