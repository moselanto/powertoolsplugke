<?php
/**
 * PowerPlug theme bootstrap.
 *
 * @package PowerPlug
 * @author  Power Tools Plug Kenya
 * @license GPL-2.0-or-later
 *
 * Minimum: PHP 8.0, WordPress 6.4, WooCommerce 8.0.
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

if ( version_compare( PHP_VERSION, '8.0', '<' ) ) {
	add_action(
		'admin_notices',
		static function (): void {
			echo '<div class="notice notice-error"><p>' .
				esc_html( sprintf( __( 'PowerPlug requires PHP 8.0 or higher. This server runs PHP %s. In cPanel, open Select PHP Version (or MultiPHP Manager) and switch this site to PHP 8.1 or 8.2, then reload.', 'powerplug' ), PHP_VERSION ) ) .
				'</p></div>';
		}
	);
	return;
}

define( 'POWERPLUG_VERSION', '2.23.1' );
define( 'POWERPLUG_DIR', trailingslashit( get_template_directory() ) );
define( 'POWERPLUG_URI', trailingslashit( get_template_directory_uri() ) );

/**
 * PSR-4 style autoloader for the PowerPlug\ namespace.
 */
spl_autoload_register(
	static function ( string $class ): void {
		$prefix = 'PowerPlug\\';
		if ( ! str_starts_with( $class, $prefix ) ) {
			return;
		}
		$relative = substr( $class, strlen( $prefix ) );
		$path     = POWERPLUG_DIR . 'inc/' . str_replace( '\\', '/', $relative ) . '.php';
		if ( is_readable( $path ) ) {
			require $path;
		}
	}
);

// Boot the theme container.
add_action(
	'after_setup_theme',
	static function (): void {
		( new \PowerPlug\Core\Theme() )->boot();
	},
	5
);


/**
 * Flush PowerPlug catalogue caches when products, categories or reviews change.
 */
add_action( 'save_post_product', array( '\\PowerPlug\\Support\\Cache', 'flush' ) );
add_action( 'woocommerce_update_product', array( '\\PowerPlug\\Support\\Cache', 'flush' ) );
add_action( 'woocommerce_new_product', array( '\\PowerPlug\\Support\\Cache', 'flush' ) );
add_action( 'woocommerce_delete_product', array( '\\PowerPlug\\Support\\Cache', 'flush' ) );
add_action( 'created_product_cat', array( '\\PowerPlug\\Support\\Cache', 'flush' ) );
add_action( 'edited_product_cat', array( '\\PowerPlug\\Support\\Cache', 'flush' ) );
add_action( 'delete_product_cat', array( '\\PowerPlug\\Support\\Cache', 'flush' ) );
add_action( 'comment_post', array( '\\PowerPlug\\Support\\Cache', 'flush' ) );
add_action( 'edit_comment', array( '\\PowerPlug\\Support\\Cache', 'flush' ) );
add_action( 'wp_set_comment_status', array( '\\PowerPlug\\Support\\Cache', 'flush' ) );

/**
 * Homepage slider images (Appearance -> Customize -> PowerPlug Slider).
 */
add_action(
	'customize_register',
	static function ( $wp_customize ): void {
		$wp_customize->add_section( 'pp_slider', array( 'title' => __( 'PowerPlug Slider', 'powerplug' ), 'priority' => 30 ) );
		$uri = get_template_directory_uri();
		$defaults = array( 'pp_slide_1' => $uri . '/assets/img/slide-1.jpg', 'pp_slide_2' => $uri . '/assets/img/slide-2.jpg', 'pp_slide_3' => $uri . '/assets/img/slide-3.jpg', 'pp_slide_4' => '', 'pp_slide_5' => '', 'pp_slide_6' => '' );
		$i = 1;
		foreach ( $defaults as $key => $default_url ) {
			$wp_customize->add_setting( $key, array( 'default' => $default_url, 'sanitize_callback' => 'esc_url_raw', 'transport' => 'refresh' ) );
			$wp_customize->add_control( new \WP_Customize_Image_Control( $wp_customize, $key, array( 'label' => sprintf( __( 'Slide %d image', 'powerplug' ), $i ), 'section' => 'pp_slider' ) ) );
			$i = $i + 1;
		}
	}
);


/**
 * PowerPlug starter content installer. Runs once on theme activation.
 */
function powerplug_run_starter_setup(): void {
	if ( get_option( 'powerplug_starter_v6' ) === 'done' ) {
		return;
	}
	$dir = get_template_directory();

	// 1) Logo -> custom_logo.
	$logo = $dir . '/assets/img/brand-logo.png';
	if ( file_exists( $logo ) && get_theme_mod( 'custom_logo' ) === false ) {
		$upload = wp_upload_bits( 'power-tools-plug-logo.png', null, (string) file_get_contents( $logo ) );
		if ( empty( $upload['error'] ) ) {
			$file  = $upload['file'];
			$ftype = wp_check_filetype( $file, null );
			$attach_id = wp_insert_attachment(
				array(
					'post_mime_type' => $ftype['type'],
					'post_title'     => 'Power Tools Plug logo',
					'post_status'    => 'inherit',
				),
				$file
			);
			require_once ABSPATH . 'wp-admin/includes/image.php';
			$meta = wp_generate_attachment_metadata( $attach_id, $file );
			wp_update_attachment_metadata( $attach_id, $meta );
			set_theme_mod( 'custom_logo', $attach_id );
		}
	}

	// 2) Policy, about and contact pages.
	$page_ids = array();
	$manifest_path = $dir . '/starter/pages/manifest.json';
	if ( file_exists( $manifest_path ) ) {
		$manifest = json_decode( (string) file_get_contents( $manifest_path ), true );
		if ( is_array( $manifest ) ) {
			foreach ( $manifest as $slug => $title ) {
				$html = '';
				$html_path = $dir . '/starter/pages/' . $slug . '.html';
				if ( file_exists( $html_path ) ) {
					$html = (string) file_get_contents( $html_path );
				}
				$existing = get_page_by_path( (string) $slug );
				if ( $existing === null ) {
					$new_id = wp_insert_post(
						array(
							'post_title'   => wp_strip_all_tags( (string) $title ),
							'post_name'    => (string) $slug,
							'post_content' => $html,
							'post_status'  => 'publish',
							'post_type'    => 'page',
						)
					);
					if ( is_int( $new_id ) && $new_id > 0 ) {
						$page_ids[ $slug ] = $new_id;
					}
				} else {
					$page_ids[ $slug ] = (int) $existing->ID;
					if ( strlen( $html ) > 0 ) {
						wp_update_post( array( 'ID' => (int) $existing->ID, 'post_content' => $html, 'post_status' => 'publish' ) );
					}
				}
			}
		}
	}
	if ( isset( $page_ids['privacy-policy'] ) ) {
		update_option( 'wp_page_for_privacy_policy', $page_ids['privacy-policy'] );
	}

	// 3) Footer menu.
	$menu = wp_get_nav_menu_object( 'Footer' );
	$menu_id = ( $menu === false ) ? wp_create_nav_menu( 'Footer' ) : (int) $menu->term_id;
	if ( is_int( $menu_id ) && $menu_id > 0 ) {
		$order = array( 'about-us', 'return-refund-policy', 'shipping-delivery-policy', 'terms-conditions', 'privacy-policy', 'contact-us' );
		$items = wp_get_nav_menu_items( $menu_id );
		$item_count = is_array( $items ) ? count( $items ) : 0;
		if ( $item_count === 0 ) {
			foreach ( $order as $slug ) {
				if ( isset( $page_ids[ $slug ] ) ) {
					wp_update_nav_menu_item(
						$menu_id,
						0,
						array(
							'menu-item-title'     => get_the_title( $page_ids[ $slug ] ),
							'menu-item-object'    => 'page',
							'menu-item-object-id' => $page_ids[ $slug ],
							'menu-item-type'      => 'post_type',
							'menu-item-status'    => 'publish',
						)
					);
				}
			}
		}
		$locations = get_theme_mod( 'nav_menu_locations' );
		if ( is_array( $locations ) === false ) {
			$locations = array();
		}
		$locations['footer'] = $menu_id;
		set_theme_mod( 'nav_menu_locations', $locations );
	}

	// 4) WooCommerce store basics.
	if ( class_exists( 'WooCommerce' ) ) {
		update_option( 'woocommerce_currency', 'KES' );
		update_option( 'woocommerce_store_address', 'Magomano House, 1st Floor, Room 10D' );
		update_option( 'woocommerce_store_address_2', 'Tom Mboya Street' );
		update_option( 'woocommerce_store_city', 'Nairobi' );
		update_option( 'woocommerce_default_country', 'KE' );
		update_option( 'woocommerce_store_postcode', '00100' );
		update_option( 'woocommerce_email_from_address', 'info@powertoolsplugke.co.ke' );
		update_option( 'woocommerce_enable_ajax_add_to_cart', 'yes' );
		update_option( 'woocommerce_cart_redirect_after_add', 'no' );
		if ( function_exists( 'wc_create_pages' ) ) {
			wc_create_pages();
		}
		update_option( 'woocommerce_enable_guest_checkout', 'yes' );
		update_option( 'woocommerce_enable_checkout_login_reminder', 'yes' );
		$cod = get_option( 'woocommerce_cod_settings', array() );
		if ( is_array( $cod ) === false ) {
			$cod = array();
		}
		$cod['enabled']     = 'yes';
		$cod['title']       = 'Pay on Delivery';
		$cod['description'] = 'Pay with cash or M-Pesa when your order arrives.';
		update_option( 'woocommerce_cod_settings', $cod );
	}

	// 5) Static front page so the site stops defaulting to latest posts.
	$home_page = get_page_by_path( 'home' );
	if ( $home_page === null ) {
		$home_id = wp_insert_post( array( 'post_title' => 'Home', 'post_name' => 'home', 'post_content' => '', 'post_status' => 'publish', 'post_type' => 'page' ) );
	} else {
		$home_id = (int) $home_page->ID;
	}
	if ( is_int( $home_id ) && $home_id > 0 ) {
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $home_id );
		$news_page = get_page_by_path( 'news' );
		if ( $news_page === null ) {
			$news_id = wp_insert_post( array( 'post_title' => 'News', 'post_name' => 'news', 'post_content' => '', 'post_status' => 'publish', 'post_type' => 'page' ) );
			if ( is_int( $news_id ) && $news_id > 0 ) {
				update_option( 'page_for_posts', $news_id );
			}
		}
	}

	update_option( 'powerplug_starter_v6', 'done' );
}
add_action( 'after_switch_theme', 'powerplug_run_starter_setup' );
add_action( 'admin_init', 'powerplug_run_starter_setup' );

/**
 * One-time admin notice pointing at the bundled product CSV.
 */
add_action(
	'admin_init',
	static function (): void {
		if ( isset( $_GET['powerplug_dismiss_products'] ) && current_user_can( 'manage_options' ) ) {
			update_option( 'powerplug_products_notice_dismissed', 'yes' );
		}
	}
);
add_action(
	'admin_notices',
	static function (): void {
		if ( get_option( 'powerplug_products_notice_dismissed' ) === 'yes' ) {
			return;
		}
		if ( current_user_can( 'manage_options' ) === false ) {
			return;
		}
		$importer = admin_url( 'edit.php?post_type=product&page=product_importer' );
		$dismiss  = esc_url( add_query_arg( 'powerplug_dismiss_products', '1' ) );
		echo '<div class="notice notice-info"><p><strong>PowerPlug:</strong> ';
		echo esc_html__( 'Your cleaned catalogue (555 products) ships with the theme at wp-content/themes/powerplug/starter/products.csv. Import it with the WooCommerce product importer and choose Update existing products.', 'powerplug' );
		echo ' <a class="button button-primary" href="' . esc_url( $importer ) . '">' . esc_html__( 'Open product importer', 'powerplug' ) . '</a> ';
		echo '<a href="' . $dismiss . '">' . esc_html__( 'Dismiss', 'powerplug' ) . '</a></p></div>';
	}
);


/**
 * Live header cart count via WooCommerce fragments.
 */
add_filter(
	'woocommerce_add_to_cart_fragments',
	static function ( $fragments ) {
		if ( function_exists( 'WC' ) && WC()->cart ) {
			ob_start();
			echo '<span class="pp-cart__count">' . esc_html( (string) WC()->cart->get_cart_contents_count() ) . '</span>';
			$fragments['span.pp-cart__count'] = ob_get_clean();
		}
		return $fragments;
	}
);

/**
 * Load the WooCommerce ajax add-to-cart handler on the homepage loop.
 */
add_action(
	'wp_enqueue_scripts',
	static function (): void {
		if ( is_front_page() && function_exists( 'is_shop' ) ) {
			wp_enqueue_script( 'wc-add-to-cart' );
		}
	}
);


/**
 * LocalBusiness + Organization JSON-LD (SEO + Merchant Center trust signals).
 */
add_action(
	'wp_head',
	static function (): void {
		if ( is_admin() ) {
			return;
		}
		$data = array(
			'@context'           => 'https://schema.org',
			'@type'              => 'HardwareStore',
			'name'               => 'Power Tools Plug',
			'image'              => get_template_directory_uri() . '/assets/img/brand-logo.png',
			'url'                => home_url( '/' ),
			'telephone'          => '+254708777192',
			'email'              => 'info@powertoolsplugke.co.ke',
			'priceRange'         => 'KES',
			'currenciesAccepted' => 'KES',
			'paymentAccepted'    => 'M-Pesa, Cash on Delivery',
			'openingHours'       => 'Mo-Sa 09:00-17:00',
			'areaServed'         => 'KE',
			'address'            => array(
				'@type'           => 'PostalAddress',
				'streetAddress'   => 'Magomano House, 1st Floor, Room 10D, Tom Mboya Street',
				'addressLocality' => 'Nairobi',
				'addressCountry'  => 'KE',
			),
		);
		echo '<script type="application/ld+json">' . wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
	},
	2
);

/**
 * Preload the first hero slide for a faster LCP on the homepage.
 */
add_action(
	'wp_head',
	static function (): void {
		if ( is_front_page() === false ) {
			return;
		}
		$uri   = get_template_directory_uri();
		$slide = get_theme_mod( 'pp_slide_1', $uri . '/assets/img/slide-1.jpg' );
		if ( is_string( $slide ) && $slide !== '' ) {
			echo '<link rel="preload" as="image" href="' . esc_url( $slide ) . '" fetchpriority="high" />' . "\n";
		}
	},
	1
);

/**
 * Output Customizer brand colors as CSS variables so the whole theme retints.
 */
add_action(
	'wp_head',
	static function (): void {
		$brand = sanitize_hex_color( (string) get_theme_mod( 'pp_brand_color', '#268655' ) );
		$ink   = sanitize_hex_color( (string) get_theme_mod( 'pp_ink_color', '#111418' ) );
		$css   = '';
		if ( $brand ) {
			$css .= '--pp-brand:' . $brand . ';';
		}
		if ( $ink ) {
			$css .= '--pp-ink:' . $ink . ';';
		}
		if ( $css !== '' ) {
			echo '<style id="powerplug-brand-vars">:root{' . esc_html( $css ) . '}</style>' . "\n";
		}
	},
	20
);


/**
 * Fallback primary navigation.
 *
 * When no menu is assigned to the "primary" location, wp_nav_menu() would
 * otherwise output nothing, leaving the top navigation bar as an empty blank
 * strip. This renders a sensible default (Home, Shop, top product categories,
 * About, Contact) so the header is always balanced and usable.
 *
 * @param array<string,mixed> $args wp_nav_menu args.
 */
function powerplug_primary_menu_fallback( $args = array() ): string {
	$args     = is_array( $args ) ? $args : (array) $args;
	$menu_id  = isset( $args['menu_id'] ) ? (string) $args['menu_id'] : 'pp-primary-menu';
	$menu_cls = isset( $args['menu_class'] ) ? (string) $args['menu_class'] : 'pp-primary__menu';
	$shop     = function_exists( 'wc_get_page_permalink' ) ? (string) wc_get_page_permalink( 'shop' ) : home_url( '/shop/' );

	$items   = array();
	$items[] = array( home_url( '/' ), __( 'Home', 'powerplug' ) );
	$items[] = array( $shop, __( 'Shop', 'powerplug' ) );

	if ( function_exists( 'get_terms' ) && taxonomy_exists( 'product_cat' ) ) {
		$terms = \PowerPlug\Support\Cache::remember(
			'pp_navfallback_cats_6',
			6 * HOUR_IN_SECONDS,
			static function () {
				$r = get_terms(
					array(
						'taxonomy'   => 'product_cat',
						'hide_empty' => true,
						'number'     => 6,
						'orderby'    => 'count',
						'order'      => 'DESC',
						'exclude'    => array( (int) get_option( 'default_product_cat' ) ),
					)
				);
				return is_array( $r ) ? $r : array();
			}
		);
		if ( is_array( $terms ) ) {
			foreach ( $terms as $pp_t ) {
				$pp_link = get_term_link( $pp_t );
				if ( is_wp_error( $pp_link ) === false ) {
					$items[] = array( (string) $pp_link, (string) $pp_t->name );
				}
			}
		}
	}

	$items[] = array( home_url( '/about-us/' ), __( 'About Us', 'powerplug' ) );
	$items[] = array( home_url( '/contact-us/' ), __( 'Contact', 'powerplug' ) );

	$html = '<ul id="' . esc_attr( $menu_id ) . '" class="' . esc_attr( $menu_cls ) . '">';
	foreach ( $items as $it ) {
		$html .= '<li class="menu-item"><a href="' . esc_url( $it[0] ) . '">' . esc_html( (string) $it[1] ) . '</a></li>';
	}
	$html .= '</ul>';

	$echo = ( ! array_key_exists( 'echo', $args ) ) || ! empty( $args['echo'] );
	if ( $echo ) {
		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built with esc_* above.
		return '';
	}
	return $html;
}

/**
 * One-time refresh of the Shipping & Delivery Policy page content.
 *
 * Updates only that single page from the bundled starter HTML, so manual edits
 * to other pages are never touched. Bump the option key to push a new revision.
 */
add_action(
	'admin_init',
	static function (): void {
		if ( get_option( 'powerplug_shipping_policy_v4' ) === 'done' ) {
			return;
		}
		$html_path = get_template_directory() . '/starter/pages/shipping-delivery-policy.html';
		if ( ! file_exists( $html_path ) ) {
			return;
		}
		$html = (string) file_get_contents( $html_path );
		if ( '' === trim( $html ) ) {
			return;
		}
		$page = get_page_by_path( 'shipping-delivery-policy' );
		if ( $page instanceof \WP_Post ) {
			wp_update_post(
				array(
					'ID'           => (int) $page->ID,
					'post_content' => $html,
					'post_status'  => 'publish',
				)
			);
		} else {
			wp_insert_post(
				array(
					'post_title'   => 'Shipping & Delivery Policy',
					'post_name'    => 'shipping-delivery-policy',
					'post_content' => $html,
					'post_status'  => 'publish',
					'post_type'    => 'page',
				)
			);
		}
		update_option( 'powerplug_shipping_policy_v4', 'done' );
	}
);

/**
 * One-time refresh of the About, Terms and Return & Refund policy pages.
 *
 * Republishes these three pages from the corrected bundled starter HTML so the
 * updated warranty wording goes live. Only these pages are touched; other pages
 * and manual edits elsewhere are never affected. Bump the option key to push a
 * new revision.
 */
add_action(
	'admin_init',
	static function (): void {
		if ( get_option( 'powerplug_policy_copy_v1' ) === 'done' ) {
			return;
		}
		$pp_slugs = array( 'about-us', 'terms-conditions', 'return-refund-policy' );
		$pp_base  = get_template_directory() . '/starter/pages/';
		foreach ( $pp_slugs as $pp_slug ) {
			$pp_path = $pp_base . $pp_slug . '.html';
			if ( ! file_exists( $pp_path ) ) {
				continue;
			}
			$pp_html = (string) file_get_contents( $pp_path );
			if ( '' === trim( $pp_html ) ) {
				continue;
			}
			$pp_page = get_page_by_path( $pp_slug );
			if ( $pp_page instanceof \WP_Post ) {
				wp_update_post(
					array(
						'ID'           => (int) $pp_page->ID,
						'post_content' => $pp_html,
						'post_status'  => 'publish',
					)
				);
			}
		}
		update_option( 'powerplug_policy_copy_v1', 'done' );
	}
);
