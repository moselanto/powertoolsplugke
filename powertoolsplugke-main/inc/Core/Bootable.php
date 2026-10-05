<?php
declare( strict_types=1 );

namespace PowerPlug\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Contract implemented by every self-registering feature module.
 */
interface Bootable {
	/**
	 * Register hooks / filters for this module.
	 */
	public function boot(): void;
}
