<?php
declare( strict_types=1 );

namespace PowerPlug\Seo;

use PowerPlug\Core\Bootable;

defined( 'ABSPATH' ) || exit;

/**
 * JSON-LD structured data generator.
 *
 * Emits Organization + WebSite (with SearchAction) sitewide and Product +
 * BreadcrumbList on product pages. Deliberately yields to Yoast/Rank Math when
 * either is active to avoid duplicate graphs.
 */
final class Schema implements Bootable {

	public function boot(): void {
		add_action( 'wp_head', [ $this, 'output' ], 5 );
	}

	private function seo_plugin_active(): bool {
		return defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' );
	}

	public function output(): void {
		if ( true === (bool) apply_filters( 'powerplug_disable_schema', false ) ) {
			return;
		}
		$graph   = [ $this->organization(), $this->website(), $this->store() ];
		$has_seo = $this->seo_plugin_active();

		if ( $has_seo === false && function_exists( 'is_product' ) && is_product() ) {
			$product = $this->product_schema();
			if ( $product ) {
				$graph[] = $product;
			}
			$graph[] = $this->breadcrumbs();
		} elseif ( $has_seo === false && ( ( function_exists( 'is_product_category' ) && is_product_category() ) || ( function_exists( 'is_shop' ) && is_shop() ) ) ) {
			$graph[] = $this->breadcrumbs();
		}

		$data = [ '@context' => 'https://schema.org', '@graph' => array_values( array_filter( $graph ) ) ];
		echo '<script type="application/ld+json">' . wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
	}

	private function organization(): array {
		return [
			'@type'       => 'Organization',
			'@id'         => home_url( '/#organization' ),
			'name'        => get_bloginfo( 'name' ),
			'url'         => home_url( '/' ),
			'logo'        => $this->logo_url(),
			'telephone'   => get_option( 'powerplug_phone', '+254708777192' ),
			'email'       => get_option( 'powerplug_email', 'info@powertoolsplugke.co.ke' ),
			'address'     => [
				'@type'           => 'PostalAddress',
				'streetAddress'   => get_option( 'powerplug_street', 'Magomano House, 1st Floor, Room 10D, Tom Mboya Street' ),
				'addressLocality' => get_option( 'powerplug_city', 'Nairobi' ),
				'addressCountry'  => 'KE',
			],
			'contactPoint' => [
				'@type'             => 'ContactPoint',
				'telephone'         => get_option( 'powerplug_phone', '+254708777192' ),
				'contactType'       => 'customer service',
				'areaServed'        => 'KE',
				'availableLanguage' => [ 'en', 'sw' ],
			],
			'sameAs'       => $this->social_links(),
		];
	}

	private function website(): array {
		return [
			'@type'           => 'WebSite',
			'@id'             => home_url( '/#website' ),
			'url'             => home_url( '/' ),
			'name'            => get_bloginfo( 'name' ),
			'potentialAction' => [
				'@type'       => 'SearchAction',
				'target'      => home_url( '/?s={search_term_string}' ),
				'query-input' => 'required name=search_term_string',
			],
		];
	}

	private function product_schema(): ?array {
		global $product;
		if ( ! $product instanceof \WC_Product ) {
			return null;
		}
		$brand = (string) get_post_meta( $product->get_id(), '_powerplug_brand', true );
		if ( strlen( $brand ) === 0 ) {
			$attr = (string) $product->get_attribute( 'brand' );
			$brand = strlen( $attr ) > 0 ? $attr : (string) $product->get_attribute( 'pa_brand' );
		}
		$gtin = (string) get_post_meta( $product->get_id(), '_powerplug_gtin', true );
		$mpn  = (string) get_post_meta( $product->get_id(), '_powerplug_mpn', true );
		if ( strlen( $mpn ) === 0 ) {
			$mpn = (string) $product->get_sku();
		}

		$offers = $this->build_offers( $product );

		$schema = [
			'@type'       => 'Product',
			'@id'         => get_permalink( $product->get_id() ) . '#product',
			'name'        => $product->get_name(),
			'description' => wp_strip_all_tags( $product->get_short_description() ?: $product->get_description() ),
			'sku'         => $product->get_sku(),
			'image'       => $this->product_images( $product ),
			'offers'      => $offers,
		];
		if ( strlen( $brand ) > 0 ) {
			$schema['brand'] = [ '@type' => 'Brand', 'name' => $brand ];
		}
		if ( strlen( $gtin ) > 0 ) {
			$schema['gtin'] = $gtin;
		}
		if ( strlen( $mpn ) > 0 ) {
			$schema['mpn'] = $mpn;
		}
		if ( $product->get_review_count() > 0 ) {
			$schema['aggregateRating'] = [
				'@type'       => 'AggregateRating',
				'ratingValue' => $product->get_average_rating(),
				'reviewCount' => $product->get_review_count(),
			];
		}
		return $schema;
	}

	private function build_offers( \WC_Product $product ): array {
		$currency = get_woocommerce_currency();
		$avail    = $product->is_in_stock() ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock';
		$url      = get_permalink( $product->get_id() );
		if ( $product->is_type( 'variable' ) ) {
			$prices = $product->get_variation_prices( true );
			$list   = ( isset( $prices['price'] ) && is_array( $prices['price'] ) ) ? array_values( $prices['price'] ) : [];
			if ( count( $list ) > 0 ) {
				return [
					'@type'         => 'AggregateOffer',
					'priceCurrency' => $currency,
					'lowPrice'      => (string) min( $list ),
					'highPrice'     => (string) max( $list ),
					'offerCount'    => count( $list ),
					'availability'  => $avail,
					'url'           => $url,
				];
			}
		}
		$offer = [
			'@type'           => 'Offer',
			'priceCurrency'   => $currency,
			'price'           => $product->get_price(),
			'priceValidUntil' => gmdate( 'Y-m-d', strtotime( '+1 year' ) ),
			'itemCondition'   => 'https://schema.org/NewCondition',
			'availability'    => $avail,
			'url'             => $url,
			'seller'          => [ '@id' => home_url( '/#organization' ) ],
			'hasMerchantReturnPolicy' => $this->return_policy(),
		];
		$pp_sd = $this->shipping_details();
		if ( count( $pp_sd ) > 0 ) {
			$offer['shippingDetails'] = $pp_sd;
		}
		return $offer;
	}

	private function crumb( int $pos, string $name, string $url ): array {
		return [ '@type' => 'ListItem', 'position' => $pos, 'name' => $name, 'item' => $url ];
	}

	private function term_chain( \WP_Term $term, int &$pos, array &$items ): void {
		$ancestors = array_reverse( get_ancestors( $term->term_id, 'product_cat' ) );
		foreach ( $ancestors as $aid ) {
			$anc = get_term( (int) $aid, 'product_cat' );
			if ( $anc instanceof \WP_Term ) {
				$link = get_term_link( $anc );
				if ( is_wp_error( $link ) === false ) {
					$items[] = $this->crumb( $pos++, $anc->name, (string) $link );
				}
			}
		}
		$link = get_term_link( $term );
		if ( is_wp_error( $link ) === false ) {
			$items[] = $this->crumb( $pos++, $term->name, (string) $link );
		}
	}

	private function breadcrumbs(): array {
		$pos      = 1;
		$items    = [];
		$shop_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' );
		$items[]  = $this->crumb( $pos++, __( 'Home', 'powerplug' ), home_url( '/' ) );
		$items[]  = $this->crumb( $pos++, __( 'Shop', 'powerplug' ), (string) $shop_url );

		if ( function_exists( 'is_product' ) && is_product() ) {
			$terms = get_the_terms( get_the_ID(), 'product_cat' );
			if ( is_array( $terms ) && count( $terms ) > 0 ) {
				$this->term_chain( $terms[0], $pos, $items );
			}
			$items[] = $this->crumb( $pos++, get_the_title(), (string) get_permalink() );
		} elseif ( function_exists( 'is_product_category' ) && is_product_category() ) {
			$term = get_queried_object();
			if ( $term instanceof \WP_Term ) {
				$this->term_chain( $term, $pos, $items );
			}
		}

		return [
			'@type'           => 'BreadcrumbList',
			'@id'             => home_url( add_query_arg( null, null ) ) . '#breadcrumb',
			'itemListElement' => $items,
		];
	}

	private function logo_url(): string {
		$id = (int) get_theme_mod( 'custom_logo' );
		return $id ? (string) wp_get_attachment_url( $id ) : '';
	}

	/** @return array<int,string> */
	private function social_links(): array {
		$links = apply_filters( 'powerplug_social_profiles', [] );
		return is_array( $links ) ? array_values( array_filter( array_map( 'esc_url_raw', $links ) ) ) : [];
	}

	/** @return array<int,string> */
	private function product_images( \WC_Product $product ): array {
		$ids  = array_merge( [ $product->get_image_id() ], $product->get_gallery_image_ids() );
		$urls = [];
		foreach ( $ids as $iid ) {
			$u = wp_get_attachment_image_url( (int) $iid, 'full' );
			if ( is_string( $u ) && strlen( $u ) > 0 ) {
				$urls[] = $u;
			}
		}
		if ( count( $urls ) === 0 && function_exists( 'wc_placeholder_img_src' ) ) {
			$ph = wc_placeholder_img_src( 'full' );
			if ( is_string( $ph ) ) {
				$urls[] = $ph;
			}
		}
		return $urls;
	}

	private function store(): array {
		$hours = apply_filters( 'powerplug_opening_hours', [
			[ 'days' => [ 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday' ], 'opens' => '09:00', 'closes' => '17:00' ],
		] );
		$spec = [];
		foreach ( (array) $hours as $h ) {
			$spec[] = [
				'@type'     => 'OpeningHoursSpecification',
				'dayOfWeek' => $h['days'],
				'opens'     => $h['opens'],
				'closes'    => $h['closes'],
			];
		}
		return [
			'@type'                     => 'Store',
			'@id'                       => home_url( '/#store' ),
			'name'                      => get_bloginfo( 'name' ),
			'image'                     => $this->logo_url(),
			'url'                       => home_url( '/' ),
			'telephone'                 => get_option( 'powerplug_phone', '+254708777192' ),
			'priceRange'                => '$$',
			'currenciesAccepted'        => function_exists( 'get_woocommerce_currency' ) ? get_woocommerce_currency() : 'KES',
			'address'                   => [
				'@type'           => 'PostalAddress',
				'streetAddress'   => get_option( 'powerplug_street', 'Magomano House, 1st Floor, Room 10D, Tom Mboya Street' ),
				'addressLocality' => get_option( 'powerplug_city', 'Nairobi' ),
				'addressCountry'  => 'KE',
			],
			'openingHoursSpecification' => $spec,
		];
	}

	/** @return array<string,mixed> */
	private function return_policy(): array {
		return apply_filters( 'powerplug_return_policy', [
			'@type'                => 'MerchantReturnPolicy',
			'applicableCountry'    => 'KE',
			'returnPolicyCategory' => 'https://schema.org/MerchantReturnFiniteReturnWindow',
			'merchantReturnDays'   => 7,
			'returnMethod'         => 'https://schema.org/ReturnByMail',
			'returnFees'           => 'https://schema.org/FreeReturn',
		] );
	}

	/** @return array<string,mixed> */
	private function shipping_details(): array {
		// Only advertise a shipping rate in structured data when a flat rate is explicitly configured.
		// Emitting value 0 by default asserts FREE shipping to Google Merchant Center, which we must avoid.
		$rate = trim( (string) get_option( 'powerplug_flat_shipping', '' ) );
		if ( '' === $rate ) {
			return [];
		}
		$currency = function_exists( 'get_woocommerce_currency' ) ? get_woocommerce_currency() : 'KES';
		return apply_filters( 'powerplug_shipping_details', [
			'@type'               => 'OfferShippingDetails',
			'shippingRate'        => [
				'@type'    => 'MonetaryAmount',
				'value'    => $rate,
				'currency' => $currency,
			],
			'shippingDestination' => [
				'@type'          => 'DefinedRegion',
				'addressCountry' => 'KE',
			],
			'deliveryTime'        => [
				'@type'        => 'ShippingDeliveryTime',
				'handlingTime' => [ '@type' => 'QuantitativeValue', 'minValue' => 0, 'maxValue' => 1, 'unitCode' => 'DAY' ],
				'transitTime'  => [ '@type' => 'QuantitativeValue', 'minValue' => 1, 'maxValue' => 3, 'unitCode' => 'DAY' ],
			],
		] );
	}
}
