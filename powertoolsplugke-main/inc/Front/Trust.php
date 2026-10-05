<?php
declare( strict_types=1 );

namespace PowerPlug\Front;

use PowerPlug\Core\Bootable;
use PowerPlug\Customizer\Customizer;

defined( 'ABSPATH' ) || exit;

/**
 * Trust and compliance building blocks, exposed as shortcodes so they can be
 * placed on any page (About Us, Contact, Warranty, home, etc.):
 *   [pp_trust_badges]  - row of assurance badges
 *   [pp_why_buy]       - "Why buy from Power Tools Plug Kenya" reasons grid
 *   [pp_store_gallery] - real photos of the physical shop
 *   [pp_store_map]     - Google map and address for the physical shop
 * Copy is truthful and pulls the address from the Customizer so nothing drifts
 * out of date. No product or brand claims are hardcoded here.
 */
final class Trust implements Bootable {

	public function boot(): void {
		add_shortcode( 'pp_trust_badges', array( $this, 'trust_badges' ) );
		add_shortcode( 'pp_why_buy', array( $this, 'why_buy' ) );
		add_shortcode( 'pp_store_gallery', array( $this, 'store_gallery' ) );
		add_shortcode( 'pp_store_map', array( $this, 'store_map' ) );
		add_shortcode( 'pp_faq', array( $this, 'faq' ) );
		add_shortcode( 'pp_business_info', array( $this, 'business_info' ) );
	}

	private static function check(): string {
		return '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 12"></polyline></svg>';
	}

	public function trust_badges( $atts = array() ): string {
		$badges = array(
			__( 'Warranty where applicable', 'powerplug' ),
			__( 'Secure SSL Checkout', 'powerplug' ),
			__( 'Fast Countrywide Delivery', 'powerplug' ),
			__( 'Pay on Delivery', 'powerplug' ),
			__( 'M-Pesa Accepted', 'powerplug' ),
			__( 'Physical Shop in Nairobi', 'powerplug' ),
		);
		$out = '<div class="pp-trust-badges">';
		foreach ( $badges as $label ) {
			$out .= '<span class="pp-trust-badge"><span class="pp-trust-badge__ic" aria-hidden="true">' . self::check() . '</span><span class="pp-trust-badge__t">' . esc_html( $label ) . '</span></span>';
		}
		$out .= '</div>';
		return $out;
	}

	public function why_buy( $atts = array() ): string {
		$a     = is_array( $atts ) ? $atts : array();
		$title = isset( $a['title'] ) ? (string) $a['title'] : __( 'Why buy from Power Tools Plug Kenya', 'powerplug' );
		$reasons = array(
			array( __( 'Warranty where applicable', 'powerplug' ), __( 'Branded tools and equipment, with manufacturer warranty where available. Terms vary by product.', 'powerplug' ) ),
			array( __( 'A real shop you can visit', 'powerplug' ), __( 'Come see stock in person at Magomano House, Tom Mboya Street, Nairobi. We are a physical business, not only a website.', 'powerplug' ) ),
			array( __( 'Pay on delivery', 'powerplug' ), __( 'Inspect your order first, then pay by M-Pesa or cash on delivery. Your money stays safe until you receive your item.', 'powerplug' ) ),
			array( __( 'Countrywide delivery', 'powerplug' ), __( 'We deliver countrywide, typically within 1 to 5 business days.', 'powerplug' ) ),
			array( __( 'Expert advice', 'powerplug' ), __( 'Talk to real tool specialists before and after you buy. We help you choose the right tool for the job.', 'powerplug' ) ),
			array( __( 'Trusted across Kenya', 'powerplug' ), __( 'Contractors, technicians, institutions, hardware stores, farmers and homeowners buy from us countrywide.', 'powerplug' ) ),
		);
		$out = '<section class="pp-whybuy"><h2 class="pp-whybuy__title">' . esc_html( $title ) . '</h2><div class="pp-whybuy__grid">';
		foreach ( $reasons as $r ) {
			$out .= '<div class="pp-whybuy__item"><span class="pp-whybuy__ic" aria-hidden="true">' . self::check() . '</span><div class="pp-whybuy__body"><h3>' . esc_html( $r[0] ) . '</h3><p>' . esc_html( $r[1] ) . '</p></div></div>';
		}
		$out .= '</div></section>';
		return $out;
	}

	public function store_gallery( $atts = array() ): string {
		$uri = get_template_directory_uri() . '/assets/img/store/';
		$dir = get_template_directory() . '/assets/img/store/';
		$shots = array(
			array( 'pp-shop-1', __( 'Inside our shop: power tools in stock', 'powerplug' ) ),
			array( 'pp-shop-2', __( 'Our shelves at Magomano House, Tom Mboya Street, Nairobi', 'powerplug' ) ),
		);
		$out = '<div class="pp-store-gallery">';
		foreach ( $shots as $s ) {
			if ( file_exists( $dir . $s[0] . '.jpg' ) ) {
				$out .= '<figure class="pp-store-shot"><picture>';
				$out .= '<source type="image/webp" srcset="' . esc_url( $uri . $s[0] . '.webp' ) . '">';
				$out .= '<img src="' . esc_url( $uri . $s[0] . '.jpg' ) . '" alt="' . esc_attr( $s[1] ) . '" loading="lazy" width="680" height="510">';
				$out .= '</picture><figcaption>' . esc_html( $s[1] ) . '</figcaption></figure>';
			}
		}
		$out .= '</div>';
		return $out;
	}

	public function store_map( $atts = array() ): string {
		$addr = trim( (string) Customizer::val( 'pp_address' ) );
		if ( '' === $addr ) {
			$addr = 'Magomano House, 1st Floor, Room 10D, Tom Mboya Street, Nairobi';
		}
		$q     = rawurlencode( $addr );
		$embed = 'https://www.google.com/maps?q=' . $q . '&output=embed';
		$dir   = 'https://www.google.com/maps/dir/?api=1&destination=' . $q;
		$out  = '<div class="pp-store-map">';
		$out .= '<div class="pp-store-map__frame"><iframe title="' . esc_attr__( 'Our shop location on Google Maps', 'powerplug' ) . '" src="' . esc_url( $embed ) . '" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe></div>';
		$out .= '<div class="pp-store-map__meta"><p class="pp-store-map__addr">' . esc_html( $addr ) . '</p>';
		$out .= '<a class="pp-store-map__btn" href="' . esc_url( $dir ) . '" target="_blank" rel="noopener">' . esc_html__( 'Get directions', 'powerplug' ) . '</a></div>';
		$out .= '</div>';
		return $out;
	}

	public function faq( $atts = array() ): string {
		if ( false === class_exists( Home::class ) ) {
			return '';
		}
		ob_start();
		Home::faq();
		return (string) ob_get_clean();
	}

	public function business_info( $atts = array() ): string {
		$name = (string) get_bloginfo( 'name' );
		$addr = trim( (string) Customizer::val( 'pp_address' ) );
		if ( '' === $addr ) {
			$addr = 'Magomano House, 1st Floor, Room 10D, Tom Mboya Street, Nairobi CBD';
		}
		$phone = trim( (string) Customizer::val( 'pp_phone' ) );
		$email = trim( (string) Customizer::val( 'pp_email' ) );
		$hours = trim( (string) Customizer::val( 'pp_hours' ) );
		$rows = array(
			array( __( 'Business name', 'powerplug' ), $name ),
			array( __( 'Legal status', 'powerplug' ), __( 'Registered business operating in Kenya', 'powerplug' ) ),
			array( __( 'Physical shop', 'powerplug' ), $addr ),
		);
		if ( strlen( $phone ) > 0 ) {
			$rows[] = array( __( 'Phone', 'powerplug' ), $phone );
		}
		if ( strlen( $email ) > 0 ) {
			$rows[] = array( __( 'Email', 'powerplug' ), $email );
		}
		if ( strlen( $hours ) > 0 ) {
			$rows[] = array( __( 'Opening hours', 'powerplug' ), $hours );
		}
		$rows[] = array( __( 'Delivery', 'powerplug' ), __( 'Nationwide delivery across Kenya', 'powerplug' ) );
		$rows[] = array( __( 'Payment', 'powerplug' ), __( 'M-Pesa and Pay on Delivery', 'powerplug' ) );
		$out = '<section class="pp-bizinfo"><h2 class="pp-bizinfo__title">' . esc_html__( 'Business Information', 'powerplug' ) . '</h2><dl class="pp-bizinfo__list">';
		foreach ( $rows as $r ) {
			$out .= '<div class="pp-bizinfo__row"><dt>' . esc_html( $r[0] ) . '</dt><dd>' . esc_html( $r[1] ) . '</dd></div>';
		}
		$out .= '</dl></section>';
		return $out;
	}
}
