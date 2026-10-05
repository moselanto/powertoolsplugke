<?php
/**
 * Setup Wizard shell. Steps are progressively enhanced by assets/js/wizard.js.
 *
 * @package PowerPlug
 */
defined( 'ABSPATH' ) || exit;
?>
<div class="pp-wizard" id="pp-wizard">
	<header class="pp-wizard__head">
		<h1>PowerPlug Setup</h1>
		<ol class="pp-wizard__steps" aria-label="<?php esc_attr_e( 'Setup steps', 'powerplug' ); ?>">
			<li data-step="1" class="is-active"><?php esc_html_e( 'Welcome', 'powerplug' ); ?></li>
			<li data-step="2"><?php esc_html_e( 'Plugins', 'powerplug' ); ?></li>
			<li data-step="3"><?php esc_html_e( 'Activate', 'powerplug' ); ?></li>
			<li data-step="4"><?php esc_html_e( 'Demo Content', 'powerplug' ); ?></li>
			<li data-step="5"><?php esc_html_e( 'Finish', 'powerplug' ); ?></li>
		</ol>
	</header>

	<section class="pp-wizard__panel" data-panel="1" aria-hidden="false">
		<h2><?php esc_html_e( 'Welcome to PowerPlug', 'powerplug' ); ?></h2>
		<p><?php esc_html_e( 'This wizard installs the plugins your store needs, activates them, and imports professional demo content tailored for a power-tools retailer. You can re-run it any time from Appearance → PowerPlug Setup.', 'powerplug' ); ?></p>
		<button class="button button-primary pp-next"><?php esc_html_e( 'Let’s go', 'powerplug' ); ?></button>
	</section>

	<section class="pp-wizard__panel" data-panel="2" aria-hidden="true" hidden>
		<h2><?php esc_html_e( 'Install Required Plugins', 'powerplug' ); ?></h2>
		<ul class="pp-plugin-list" id="pp-plugin-list"></ul>
		<button class="button button-primary pp-install-all"><?php esc_html_e( 'Install &amp; Activate All', 'powerplug' ); ?></button>
		<button class="button pp-next"><?php esc_html_e( 'Skip', 'powerplug' ); ?></button>
	</section>

	<section class="pp-wizard__panel" data-panel="3" aria-hidden="true" hidden>
		<h2><?php esc_html_e( 'Activating', 'powerplug' ); ?></h2>
		<p id="pp-activate-log" role="status" aria-live="polite"></p>
		<button class="button button-primary pp-next"><?php esc_html_e( 'Continue', 'powerplug' ); ?></button>
	</section>

	<section class="pp-wizard__panel" data-panel="4" aria-hidden="true" hidden>
		<h2><?php esc_html_e( 'Import Demo Content', 'powerplug' ); ?></h2>
		<p><?php esc_html_e( 'Imports categories, sample products, pages (About, Contact, Policies, FAQs) and the primary menu. Safe to re-run.', 'powerplug' ); ?></p>
		<button class="button button-primary pp-import"><?php esc_html_e( 'Import Demo', 'powerplug' ); ?></button>
		<p id="pp-import-log" role="status" aria-live="polite"></p>
		<button class="button pp-next"><?php esc_html_e( 'Continue', 'powerplug' ); ?></button>
	</section>

	<section class="pp-wizard__panel" data-panel="5" aria-hidden="true" hidden>
		<h2><?php esc_html_e( 'Your store is ready', 'powerplug' ); ?></h2>
		<p><?php esc_html_e( 'Next: verify your business details, review Merchant Compliance, and connect payments (M-Pesa, cards).', 'powerplug' ); ?></p>
		<a class="button button-primary" id="pp-finish" href="#"><?php esc_html_e( 'Go to Dashboard', 'powerplug' ); ?></a>
	</section>
</div>
