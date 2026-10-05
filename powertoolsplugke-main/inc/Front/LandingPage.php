<?php
declare( strict_types=1 );

namespace PowerPlug\Front;

use PowerPlug\Core\Bootable;
use PowerPlug\Customizer\Customizer;

defined( 'ABSPATH' ) || exit;

/**
 * Landing Page (Meta Ads) module + renderer.
 *
 * Powers the "Landing Page — Category (Ads)" page template. It pulls live
 * WooCommerce products from a chosen category so prices, stock and images are
 * always real, and drives conversions through the real cart/checkout (COD) and
 * a pre-filled WhatsApp order.
 *
 * Conversion mechanics are honest by design: no fabricated reviews, star
 * counts, resetting countdowns or fake scarcity — this keeps the page within
 * Google Merchant Center and Meta advertising policies.
 */
final class LandingPage implements Bootable {

	private const TEMPLATE = 'template-lp-category.php';

	/** URL prefix used by every funnel, e.g. /lp-water-pumps/. */
	private const PREFIX = 'lp-';

	/** Category slug for a funnel URL that has no published page (virtual funnel). */
	private static string $virtual_slug = '';

	/** The part after "lp-" in the current funnel URL (used for hero images). */
	private static string $funnel_key = '';

	/** Whether the current request was already inspected. */
	private static bool $routed = false;

	/** Existing landing page (any slug) whose saved settings apply to a virtual funnel. */
	private static int $settings_page_id = 0;

	public function boot(): void {
		add_action( 'wp_enqueue_scripts', [ $this, 'assets' ], 30 );
		// Funnel routing: /lp-{category}/ always renders the landing template, even when
		// the page is missing, still a draft, or was saved without the Ads template.
		add_filter( 'request', [ $this, 'route_request' ], 1 );
		add_filter( 'pre_handle_404', [ $this, 'pre_handle_404' ], 10, 2 );
		add_action( 'wp', [ $this, 'debug' ], 0 );
		add_filter( 'template_include', [ $this, 'template_include' ], 99 );
		add_filter( 'pre_get_document_title', [ $this, 'document_title' ], 20 );
		add_filter( 'body_class', [ $this, 'body_class' ] );
		add_action( 'add_meta_boxes', [ $this, 'add_box' ] );
		add_action( 'save_post_page', [ $this, 'save_box' ], 10, 2 );
	}

	/**
	 * Enqueue the landing assets only on pages using this template.
	 */
	public function assets(): void {
		if ( ! is_page_template( self::TEMPLATE ) && ! self::is_funnel() ) {
			return;
		}
		$ver = POWERPLUG_VERSION;
		wp_enqueue_style( 'powerplug-landing', POWERPLUG_URI . 'assets/css/landing.css', [ 'powerplug-main' ], $ver );
		wp_enqueue_script( 'powerplug-landing', POWERPLUG_URI . 'assets/js/landing.js', [], $ver, true );
	}

	/* ------------------------------------------------------------------ */
	/* Editor meta box                                                     */
	/* ------------------------------------------------------------------ */

	public function add_box(): void {
		add_meta_box( 'pp_lp_box', __( 'Landing Page (Ads) settings', 'powerplug' ), [ $this, 'render_box' ], 'page', 'normal', 'high' );
	}

	public function render_box( \WP_Post $post ): void {
		wp_nonce_field( 'pp_lp_save', 'pp_lp_nonce' );
		$cat      = (string) get_post_meta( $post->ID, '_pp_lp_category', true );
		$title    = (string) get_post_meta( $post->ID, '_pp_lp_hero_title', true );
		$sub      = (string) get_post_meta( $post->ID, '_pp_lp_hero_sub', true );
		$img      = (string) get_post_meta( $post->ID, '_pp_lp_hero_img', true );
		$promo    = (string) get_post_meta( $post->ID, '_pp_lp_promo', true );
		$benefits = (string) get_post_meta( $post->ID, '_pp_lp_benefits', true );
		$included = (string) get_post_meta( $post->ID, '_pp_lp_included', true );

		$terms = array();
		if ( taxonomy_exists( 'product_cat' ) ) {
			$t = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false ) );
			if ( is_array( $t ) ) {
				$terms = $t;
			}
		}

		echo '<style>.pp-lp-box label{font-weight:600;display:block;margin:14px 0 4px}.pp-lp-box input[type=text],.pp-lp-box input[type=url],.pp-lp-box select,.pp-lp-box textarea{width:100%;max-width:660px}.pp-lp-box .desc{color:#666;font-size:12px}</style>';
		echo '<div class="pp-lp-box">';
		echo '<p class="desc">' . esc_html__( 'Applies only when this page uses the "Landing Page — Category (Ads)" template. Products, prices, stock and images are pulled live from the chosen category.', 'powerplug' ) . '</p>';

		echo '<label for="pp_lp_category">' . esc_html__( 'Product category to feature', 'powerplug' ) . '</label>';
		echo '<select name="pp_lp_category" id="pp_lp_category"><option value="">' . esc_html__( '— Auto-detect from the page slug (blank is fine) —', 'powerplug' ) . '</option>';
		foreach ( $terms as $term ) {
			printf( '<option value="%s"%s>%s (%d)</option>', esc_attr( $term->slug ), selected( $cat, $term->slug, false ), esc_html( $term->name ), (int) $term->count );
		}
		echo '</select>';

		echo '<label for="pp_lp_product_ids">' . esc_html__( 'Advertised product IDs (optional)', 'powerplug' ) . '</label>';
		printf( '<textarea name="pp_lp_product_ids" id="pp_lp_product_ids" rows="2" placeholder="e.g. 1234, 5678, 4321">%s</textarea>', esc_textarea( (string) get_post_meta( $post->ID, '_pp_lp_product_ids', true ) ) );
		echo '<p class="desc">' . esc_html__( 'Comma-separated WooCommerce product IDs to feature on this funnel, in this exact order. Leave blank to auto-show the cheapest products from the category above. Update anytime to change which items you advertise. Find an ID under Products (hover a product, or see post=1234 in its edit URL).', 'powerplug' ) . '</p>';
		echo '<label for="pp_lp_from_price">' . esc_html__( 'Hero From price (optional)', 'powerplug' ) . '</label>';
		printf( '<input type="text" name="pp_lp_from_price" id="pp_lp_from_price" value="%s" placeholder="e.g. 3999">', esc_attr( (string) get_post_meta( $post->ID, '_pp_lp_from_price', true ) ) );
		echo '<p class="desc">' . esc_html__( 'The From price shown in the hero. Type your own number (e.g. 3999 displays as KSh 3,999.00), or leave blank to automatically show the lowest price among this funnel products.', 'powerplug' ) . '</p>';
		echo '<label for="pp_lp_hero_title">' . esc_html__( 'Hero heading', 'powerplug' ) . '</label>';
		printf( '<input type="text" name="pp_lp_hero_title" id="pp_lp_hero_title" value="%s" placeholder="Hatch More Chicks with an Automatic Egg Incubator">', esc_attr( $title ) );

		echo '<label for="pp_lp_hero_sub">' . esc_html__( 'Hero sub-heading', 'powerplug' ) . '</label>';
		printf( '<textarea name="pp_lp_hero_sub" id="pp_lp_hero_sub" rows="2">%s</textarea>', esc_textarea( $sub ) );

		echo '<label for="pp_lp_hero_img">' . esc_html__( 'Hero image URL (blank = built-in incubator hero)', 'powerplug' ) . '</label>';
		printf( '<input type="url" name="pp_lp_hero_img" id="pp_lp_hero_img" value="%s" placeholder="https://.../hero.webp">', esc_attr( $img ) );

		echo '<label for="pp_lp_promo">' . esc_html__( 'Announcement bar text (optional)', 'powerplug' ) . '</label>';
		printf( '<input type="text" name="pp_lp_promo" id="pp_lp_promo" value="%s">', esc_attr( $promo ) );

		echo '<label for="pp_lp_benefits">' . esc_html__( 'Benefits — one per line as "Title | description" (blank uses incubator defaults on the incubators category)', 'powerplug' ) . '</label>';
		printf( '<textarea name="pp_lp_benefits" id="pp_lp_benefits" rows="6" placeholder="Solar or electric | Runs on AC mains or DC solar.">%s</textarea>', esc_textarea( $benefits ) );

		echo '<label for="pp_lp_included">' . esc_html__( "What's included — one item per line (blank uses incubator defaults on the incubators category)", 'powerplug' ) . '</label>';
		printf( '<textarea name="pp_lp_included" id="pp_lp_included" rows="5">%s</textarea>', esc_textarea( $included ) );

		echo '<label for="pp_lp_included_img">' . esc_html__( 'What-is-included image URL (blank uses the built-in incubator photo)', 'powerplug' ) . '</label>';
		printf( '<input type="url" name="pp_lp_included_img" id="pp_lp_included_img" value="%s">', esc_attr( (string) get_post_meta( $post->ID, '_pp_lp_included_img', true ) ) );

		echo '</div>';
	}

	public function save_box( int $post_id, \WP_Post $post ): void {
		if ( ! isset( $_POST['pp_lp_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['pp_lp_nonce'] ) ), 'pp_lp_save' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_page', $post_id ) ) {
			return;
		}
		$fields = array(
			'_pp_lp_category'   => 'sanitize_title',
			'_pp_lp_hero_title' => 'sanitize_text_field',
			'_pp_lp_hero_sub'   => 'sanitize_textarea_field',
			'_pp_lp_hero_img'   => 'esc_url_raw',
			'_pp_lp_promo'      => 'sanitize_text_field',
			'_pp_lp_benefits'   => 'sanitize_textarea_field',
			'_pp_lp_included'   => 'sanitize_textarea_field',
			'_pp_lp_included_img' => 'esc_url_raw',
			'_pp_lp_product_ids' => array( __CLASS__, 'sanitize_ids' ),
			'_pp_lp_from_price' => 'sanitize_text_field',
		);
		foreach ( $fields as $meta => $cb ) {
			$key = ltrim( $meta, '_' );
			$raw = isset( $_POST[ $key ] ) ? wp_unslash( $_POST[ $key ] ) : '';
			$val = call_user_func( $cb, $raw );
			if ( '' === (string) $val ) {
				delete_post_meta( $post_id, $meta );
			} else {
				update_post_meta( $post_id, $meta, $val );
			}
		}
	}

	/* ------------------------------------------------------------------ */
	/* Front-end render                                                    */
	/* ------------------------------------------------------------------ */

	public static function render(): void {
		// Only a real lp- page carries settings; virtual funnels run a product query.
		self::route();
		$post_id = is_page() ? (int) get_queried_object_id() : self::$settings_page_id;
		$cfg     = self::config( $post_id );

		$wa = preg_replace( '/\D+/', '', Customizer::val( 'pp_whatsapp' ) );
		if ( '' === (string) $wa ) {
			$wa = '254708777192';
		}

		$products = self::products( $cfg['term'], (string) $cfg['product_ids'] );
		$from     = (string) $cfg['from_override'];
		if ( '' === $from ) {
			$from = self::from_price( $products );
		}

		$brand = sanitize_hex_color( (string) get_theme_mod( 'pp_brand_color', '#268655' ) );
		$ink   = sanitize_hex_color( (string) get_theme_mod( 'pp_ink_color', '#111418' ) );
		$brand = $brand ? $brand : '#268655';
		$ink   = $ink ? $ink : '#111418';
		printf( '<style id="pp-lp-vars">.pp-lp,.pp-lp-buybar{--pp-lp-brand:%s;--pp-lp-ink:%s}</style>', esc_attr( $brand ), esc_attr( $ink ) );

		self::announce( (string) $cfg['promo'] );
		self::hero( $cfg, $from, (string) $wa );
		self::trust();
		self::stats( $cfg['term'], $products );
		self::product_grid( $cfg, $products, (string) $wa );
		self::benefits( $cfg );
		self::included( $cfg );
		self::comparison();
		self::shipping_warranty();
		Home::faq();
		self::order_form( $cfg, $products, (string) $wa );
		self::sticky_bar( (string) $wa );
	}

	/**
	 * @return array<string,mixed>
	 */
	private static function config( int $post_id ): array {
		$slug = (string) get_post_meta( $post_id, '_pp_lp_category', true );
		if ( '' === $slug && $post_id > 0 ) {
			$slug = self::slug_from_page( $post_id );
		}
		if ( '' === $slug && '' !== self::$virtual_slug ) {
			$slug = self::$virtual_slug;
		}
		if ( '' === $slug ) {
			$slug = trim( (string) Customizer::val( 'pp_priority_cat' ) );
		}
		if ( '' === $slug ) {
			$slug = 'incubators';
		}

		$term = null;
		if ( taxonomy_exists( 'product_cat' ) ) {
			$t = get_term_by( 'slug', $slug, 'product_cat' );
			if ( $t && ! is_wp_error( $t ) ) {
				$term = $t;
			}
		}

		$is_incu = ( 'incubators' === $slug );
		$name    = $term ? (string) $term->name : __( 'Products', 'powerplug' );

		$copy  = self::copy_defaults( $slug );
		$title = (string) get_post_meta( $post_id, '_pp_lp_hero_title', true );
		if ( '' === $title ) {
			$title = ( '' === $copy['title'] )
				? sprintf( __( 'Shop %s in Kenya', 'powerplug' ), $name )
				: $copy['title'];
		}

		$sub = (string) get_post_meta( $post_id, '_pp_lp_hero_sub', true );
		if ( '' === $sub ) {
			$sub = ( '' === $copy['sub'] )
				? __( 'Warranty where applicable. Pay on delivery countrywide, delivered nationwide in 1–5 days.', 'powerplug' )
				: $copy['sub'];
		}

		$promo = (string) get_post_meta( $post_id, '_pp_lp_promo', true );
		if ( '' === $promo ) {
			$promo = __( 'Pay on Delivery Countrywide  ·  Nationwide delivery in 1–5 days  ·  M-Pesa & Cash accepted', 'powerplug' );
		}

		$fp_raw        = preg_replace( '/[^0-9.]/', '', (string) get_post_meta( $post_id, '_pp_lp_from_price', true ) );
		$from_override = '';
		if ( strlen( (string) $fp_raw ) > 0 && (float) $fp_raw > 0 && function_exists( 'wc_price' ) ) {
			$from_override = (string) wc_price( (float) $fp_raw );
		}

		return array(
			'slug'    => $slug,
			'term'    => $term,
			'name'    => $name,
			'is_incu' => $is_incu,
			'title'   => $title,
			'sub'     => $sub,
			'img'     => (string) get_post_meta( $post_id, '_pp_lp_hero_img', true ),
			'promo'   => $promo,
			'post_id' => $post_id,
			'product_ids' => (string) get_post_meta( $post_id, '_pp_lp_product_ids', true ),
			'from_override' => $from_override,
		);
	}

	/**
	 * @return array<int,\WC_Product>
	 */
	private static function products( $term, string $ids = '' ): array {
		if ( false === function_exists( 'wc_get_products' ) ) {
			return array();
		}
		$id_list = self::parse_ids( $ids );
		if ( count( $id_list ) > 0 ) {
			$found = wc_get_products( array(
				'status'  => 'publish',
				'limit'   => count( $id_list ),
				'include' => $id_list,
				'orderby' => 'none',
				'return'  => 'objects',
			) );
			$found = is_array( $found ) ? $found : array();
			$by_id = array();
			foreach ( $found as $fp ) {
				$by_id[ (int) $fp->get_id() ] = $fp;
			}
			$ordered = array();
			foreach ( $id_list as $wanted ) {
				if ( isset( $by_id[ $wanted ] ) ) {
					$ordered[] = $by_id[ $wanted ];
				}
			}
			if ( count( $ordered ) > 0 ) {
				return $ordered;
			}
		}
		if ( null === $term ) {
			return array();
		}
		$products = wc_get_products( array(
			'status'   => 'publish',
			'limit'    => 12,
			'orderby'  => 'price',
			'order'    => 'ASC',
			'category' => array( $term->slug ),
			'return'   => 'objects',
		) );
		return is_array( $products ) ? $products : array();
	}

	/**
	 * @return array<int,int>
	 */
	private static function parse_ids( string $raw ): array {
		$out = array();
		foreach ( preg_split( '/[^0-9]+/', $raw ) as $tok ) {
			$id = (int) $tok;
			if ( $id > 0 && false === in_array( $id, $out, true ) ) {
				$out[] = $id;
			}
		}
		return $out;
	}

	public static function sanitize_ids( $raw ): string {
		return implode( ', ', self::parse_ids( (string) $raw ) );
	}

	private static function from_price( array $products ): string {
		$min = 0.0;
		foreach ( $products as $p ) {
			if ( $p->is_purchasable() ) {
				$price = (float) wc_get_price_to_display( $p );
				if ( $price > 0 && ( 0.0 === $min || $price < $min ) ) {
					$min = $price;
				}
			}
		}
		return $min > 0 ? (string) wc_price( $min ) : '';
	}

	private static function announce( string $promo ): void {
		if ( '' === $promo ) {
			return;
		}
		echo '<div class="pp-lp pp-lp-announce">' . esc_html( $promo ) . '</div>';
	}

	/**
	 * @param array<string,mixed> $cfg
	 */
	private static function hero( array $cfg, string $from, string $wa ): void {
		$uri        = get_template_directory_uri();
		$img        = (string) $cfg['img'];
		$has_custom = ( '' !== $img );
		$base       = self::hero_base( (string) $cfg['slug'] );
		$default    = $uri . '/assets/img/lp-' . $base . '-hero.jpg';
		$wa_msg     = rawurlencode( 'Hi, I would like to order a ' . (string) $cfg['name'] . '. Please advise on sizes and prices.' );

		echo '<section class="pp-lp pp-lp-hero"><div class="pp-lp-wrap pp-lp-hero__grid">';

		echo '<div class="pp-lp-hero__copy">';
		echo '<div class="pp-lp-badges"><span class="pp-lp-chip pp-lp-chip--hot">' . esc_html__( 'Best-selling', 'powerplug' ) . '</span><span class="pp-lp-chip">' . esc_html__( 'Pay on delivery', 'powerplug' ) . '</span></div>';
		echo '<h1 class="pp-lp-h1">' . esc_html( (string) $cfg['title'] ) . '</h1>';
		echo '<p class="pp-lp-lead">' . esc_html( (string) $cfg['sub'] ) . '</p>';
		if ( '' !== $from ) {
			echo '<div class="pp-lp-price"><span class="pp-lp-price__from">' . esc_html__( 'From', 'powerplug' ) . '</span> <span class="pp-lp-price__now">' . wp_kses_post( $from ) . '</span></div>';
		}
		echo '<div class="pp-lp-hero__cta">';
		echo '<a class="pp-lp-btn pp-lp-btn--cta" href="#pp-lp-order">' . esc_html__( 'Order Now — Pay on Delivery', 'powerplug' ) . '</a>';
		echo '<div class="pp-lp-hero__row">';
		echo '<a class="pp-lp-btn pp-lp-btn--wa" href="https://wa.me/' . esc_attr( $wa ) . '?text=' . $wa_msg . '" rel="nofollow noopener">' . esc_html__( 'Order on WhatsApp', 'powerplug' ) . '</a>';
		echo '<a class="pp-lp-btn pp-lp-btn--ghost" href="#pp-lp-models">' . esc_html__( 'View sizes', 'powerplug' ) . '</a>';
		echo '</div></div>';
		echo '<div class="pp-lp-dispatch"><span class="pp-lp-dot" aria-hidden="true"></span><div><strong>' . esc_html__( 'In stock.', 'powerplug' ) . '</strong> <span data-pp-dispatch>' . esc_html__( 'Delivered countrywide in 1–5 days.', 'powerplug' ) . '</span></div></div>';
		echo '<p class="pp-lp-guarantee">' . esc_html__( 'Warranty where applicable · 7-day returns on unused items · Faulty units replaced free', 'powerplug' ) . '</p>';
		echo '</div>';

		echo '<div class="pp-lp-hero__media">';
		if ( $has_custom ) {
			printf( '<img class="pp-lp-hero__img" src="%s" alt="%s" width="1376" height="768" fetchpriority="high" decoding="async">', esc_url( $img ), esc_attr( (string) $cfg['title'] ) );
		} elseif ( strlen( $base ) > 0 ) {
			$srcset = $uri . '/assets/img/lp-' . $base . '-hero-768.webp 768w, ' . $uri . '/assets/img/lp-' . $base . '-hero-1024.webp 1024w, ' . $uri . '/assets/img/lp-' . $base . '-hero.webp 1376w';
			echo '<picture>';
			printf( '<source type="image/webp" srcset="%s" sizes="(max-width:860px) 100vw, 48vw">', esc_attr( $srcset ) );
			printf( '<img class="pp-lp-hero__img" src="%s" alt="%s" width="1376" height="768" fetchpriority="high" decoding="async">', esc_url( $default ), esc_attr( (string) $cfg['title'] ) );
			echo '</picture>';
		} else {
			printf( '<div class="pp-lp-hero__ph"><span class="pp-lp-hero__ph-label">%s</span><span class="pp-lp-hero__ph-note">%s</span></div>', esc_html( (string) $cfg['name'] ), esc_html__( 'Pay on delivery countrywide', 'powerplug' ) );
		}
		echo '</div>';

		echo '</div></section>';
	}

	private static function trust(): void {
		$items = array(
			array( __( 'Warranty where applicable', 'powerplug' ), __( 'Sourced from reputable suppliers', 'powerplug' ) ),
			array( __( 'Physical shop on Tom Mboya St, Nairobi', 'powerplug' ), __( 'Tom Mboya St — visit us', 'powerplug' ) ),
			array( __( 'M-Pesa & Pay on Delivery', 'powerplug' ), __( 'Pay the way that suits you', 'powerplug' ) ),
			array( __( 'Nationwide delivery', 'powerplug' ), __( '1–5 business days', 'powerplug' ) ),
		);
		echo '<section class="pp-lp pp-lp-trust"><div class="pp-lp-wrap pp-lp-trust__grid">';
		foreach ( $items as $it ) {
			echo '<div class="pp-lp-trust__item"><span class="pp-lp-tick" aria-hidden="true">✓</span><div><strong>' . esc_html( $it[0] ) . '</strong><small>' . esc_html( $it[1] ) . '</small></div></div>';
		}
		echo '</div></section>';
	}

	private static function stats( $term, array $products ): void {
		$cat_count = $term ? (int) $term->count : count( $products );
		$total     = 0;
		if ( function_exists( 'wp_count_posts' ) ) {
			$c     = wp_count_posts( 'product' );
			$total = isset( $c->publish ) ? (int) $c->publish : 0;
		}
		$stats = array(
			array( $cat_count > 0 ? $cat_count : count( $products ), '', __( 'Models available', 'powerplug' ) ),
			array( $total > 0 ? $total : 540, '+', __( 'Products in store', 'powerplug' ) ),
			array( 3, '-day', __( 'Countrywide delivery', 'powerplug' ) ),
			array( 7, '-day', __( 'Returns on unused items', 'powerplug' ) ),
		);
		echo '<section class="pp-lp pp-lp-stats-sec"><div class="pp-lp-wrap pp-lp-stats">';
		foreach ( $stats as $s ) {
			printf( '<div class="pp-lp-stat pp-lp-reveal"><div class="pp-lp-stat__num" data-count="%d" data-suffix="%s">0</div><div class="pp-lp-stat__lbl">%s</div></div>', (int) $s[0], esc_attr( (string) $s[1] ), esc_html( (string) $s[2] ) );
		}
		echo '</div></div></section>';
	}

	/**
	 * @param array<string,mixed>   $cfg
	 * @param array<int,\WC_Product> $products
	 */
	private static function product_grid( array $cfg, array $products, string $wa ): void {
		echo '<section class="pp-lp pp-lp-models-sec" id="pp-lp-models"><div class="pp-lp-wrap">';
		echo '<div class="pp-lp-head pp-lp-center"><span class="pp-lp-eyebrow">' . esc_html__( 'Choose your size', 'powerplug' ) . '</span><h2 class="pp-lp-h2">' . esc_html( sprintf( __( 'Pick the %s that fits', 'powerplug' ), rtrim( (string) $cfg['name'], 's' ) ) ) . '</h2><p class="pp-lp-lead">' . esc_html__( 'Live prices, in stock now. Order and pay on delivery.', 'powerplug' ) . '</p></div>';

		if ( empty( $products ) ) {
			echo '<p class="pp-lp-empty">' . esc_html__( 'Products are being updated — please order on WhatsApp.', 'powerplug' ) . '</p>';
		} else {
			$checkout = function_exists( 'wc_get_checkout_url' ) ? wc_get_checkout_url() : home_url( '/checkout/' );
			echo '<div class="pp-lp-models">';
			foreach ( $products as $p ) {
				$pid     = (int) $p->get_id();
				$name    = (string) $p->get_name();
				$link    = (string) get_permalink( $pid );
				$image   = $p->get_image( 'woocommerce_thumbnail', array( 'loading' => 'lazy' ) );
				$in      = (bool) $p->is_in_stock();
				$buyable = $p->is_purchasable() && $in && 'simple' === $p->get_type();
				$buy_url = $buyable ? add_query_arg( 'add-to-cart', $pid, $checkout ) : $link;
				$wa_msg  = rawurlencode( $link . ' ' . 'Hi, I would like to order this ' . $name . '. Please confirm availability and delivery.' );

				echo '<article class="pp-lp-model pp-lp-reveal">';
				printf( '<a class="pp-lp-model__img" href="%s">%s</a>', esc_url( $link ), $image );
				echo '<div class="pp-lp-model__body">';
				printf( '<a class="pp-lp-model__name" href="%s">%s</a>', esc_url( $link ), esc_html( $name ) );
				echo '<div class="pp-lp-model__price">' . wp_kses_post( $p->get_price_html() ) . '</div>';
				printf( '<div class="pp-lp-model__stock%s">%s</div>', $in ? '' : ' is-out', $in ? esc_html__( 'In stock', 'powerplug' ) : esc_html__( 'Out of stock', 'powerplug' ) );
				echo '<div class="pp-lp-model__cta">';
				printf( '<a class="pp-lp-btn pp-lp-btn--cta" href="%s">%s</a>', esc_url( (string) $buy_url ), $buyable ? esc_html__( 'Order now', 'powerplug' ) : esc_html__( 'View', 'powerplug' ) );
				printf( '<a class="pp-lp-btn pp-lp-btn--wa" href="https://wa.me/%s?text=%s" rel="nofollow noopener">%s</a>', esc_attr( $wa ), $wa_msg, esc_html__( 'WhatsApp', 'powerplug' ) );
				echo '</div></div></article>';
			}
			echo '</div>';
			if ( $cfg['term'] ) {
				printf( '<p class="pp-lp-center"><a class="pp-lp-btn pp-lp-btn--ghost" href="%s">%s</a></p>', esc_url( (string) get_term_link( $cfg['term'] ) ), esc_html( sprintf( __( 'See all %s', 'powerplug' ), (string) $cfg['name'] ) ) );
			}
		}
		echo '</div></section>';
	}

	/**
	 * @param array<string,mixed> $cfg
	 */
	private static function benefits( array $cfg ): void {
		$items = self::parse_pipe( (string) get_post_meta( (int) $cfg['post_id'], '_pp_lp_benefits', true ) );
		if ( empty( $items ) && $cfg['is_incu'] ) {
			$items = array(
				array( __( 'Solar or electric', 'powerplug' ), __( 'Runs on AC mains or DC solar — keep hatching through power cuts.', 'powerplug' ) ),
				array( __( 'Digital climate control', 'powerplug' ), __( 'Precise temperature and humidity so eggs develop in ideal conditions.', 'powerplug' ) ),
				array( __( 'Automatic egg turning', 'powerplug' ), __( 'Turns eggs on schedule — less handling, better results.', 'powerplug' ) ),
				array( __( 'Heavy-duty build', 'powerplug' ), __( 'Heat-resistant materials made to run continuously.', 'powerplug' ) ),
				array( __( '24/7 operation', 'powerplug' ), __( 'Battery-backup options keep sensitive periods uninterrupted.', 'powerplug' ) ),
				array( __( 'Easy maintenance', 'powerplug' ), __( 'Removable trays and rollers for quick cleaning.', 'powerplug' ) ),
			);
		}
		if ( empty( $items ) ) {
			$items = self::generic_benefits();
		}
		if ( empty( $items ) ) {
			return;
		}
		echo '<section class="pp-lp pp-lp-benefits-sec"><div class="pp-lp-wrap">';
		echo '<div class="pp-lp-head pp-lp-center"><span class="pp-lp-eyebrow">' . esc_html__( 'Why these', 'powerplug' ) . '</span><h2 class="pp-lp-h2">' . esc_html__( 'Built for the job', 'powerplug' ) . '</h2></div>';
		echo '<div class="pp-lp-cards">';
		foreach ( $items as $it ) {
			echo '<div class="pp-lp-card pp-lp-reveal"><h3>' . esc_html( $it[0] ) . '</h3><p>' . esc_html( $it[1] ) . '</p></div>';
		}
		echo '</div></div></section>';
	}

	/**
	 * @param array<string,mixed> $cfg
	 */
	private static function included( array $cfg ): void {
		$items = array();
		foreach ( preg_split( '/\r\n|\r|\n/', (string) get_post_meta( (int) $cfg['post_id'], '_pp_lp_included', true ) ) as $line ) {
			$line = trim( (string) $line );
			if ( '' !== $line ) {
				$items[] = $line;
			}
		}
		if ( empty( $items ) && $cfg['is_incu'] ) {
			$items = array(
				__( 'Incubator unit with hatching trays', 'powerplug' ),
				__( 'Automatic egg-turning rollers', 'powerplug' ),
				__( 'AC mains power cable + DC solar cable', 'powerplug' ),
				__( 'Water bottle for humidity', 'powerplug' ),
				__( 'Operation manual + warranty card', 'powerplug' ),
			);
		}
		if ( empty( $items ) ) {
			return;
		}
		echo '<section class="pp-lp pp-lp-included-sec"><div class="pp-lp-wrap pp-lp-split">';
		echo '<div><span class="pp-lp-eyebrow">' . esc_html__( 'In the box', 'powerplug' ) . '</span><h2 class="pp-lp-h2">' . esc_html__( 'Everything you need to start', 'powerplug' ) . '</h2><ul class="pp-lp-included">';
		foreach ( $items as $it ) {
			echo '<li>' . esc_html( $it ) . '</li>';
		}
		echo '</ul><a class="pp-lp-btn pp-lp-btn--cta" href="#pp-lp-order">' . esc_html__( 'Order yours today', 'powerplug' ) . '</a></div>';
		self::included_media( $cfg );
		echo '</div></section>';
	}

	private static function included_media( array $cfg ): void {
		$uri    = get_template_directory_uri();
		$custom = (string) get_post_meta( (int) $cfg['post_id'], '_pp_lp_included_img', true );
		if ( '' === $custom && true === (bool) $cfg['is_incu'] ) {
			$jpg    = $uri . '/assets/img/lp-incubators-included.jpg';
			$srcset = $uri . '/assets/img/lp-incubators-included-768.webp 768w, ' . $uri . '/assets/img/lp-incubators-included.webp 1024w';
			echo '<div class="pp-lp-media"><picture>';
			printf( '<source type="image/webp" srcset="%s" sizes="(max-width:820px) 100vw, 48vw">', esc_attr( $srcset ) );
			printf( '<img class="pp-lp-media__img" src="%s" alt="%s" loading="lazy" decoding="async" width="1024" height="768">', esc_url( $jpg ), esc_attr__( 'What is included with your incubator', 'powerplug' ) );
			echo '</picture></div>';
			return;
		}
		if ( '' === $custom ) {
			echo '<div class="pp-lp-media-ph" aria-hidden="true"></div>';
			return;
		}
		printf( '<div class="pp-lp-media"><img class="pp-lp-media__img" src="%s" alt="%s" loading="lazy" decoding="async" width="1024" height="768"></div>', esc_url( $custom ), esc_attr__( 'What is included in the box', 'powerplug' ) );
	}

	private static function comparison(): void {
		$rows = array(
			array( __( 'Physical shop you can visit', 'powerplug' ), __( 'Tom Mboya St, Nairobi', 'powerplug' ), __( 'Usually none', 'powerplug' ) ),
			array( __( 'Warranty & after-sales support', 'powerplug' ), __( 'Yes', 'powerplug' ), __( 'Rarely', 'powerplug' ) ),
			array( __( 'Pay on delivery', 'powerplug' ), __( 'Countrywide', 'powerplug' ), __( 'Deposit first', 'powerplug' ) ),
			array( __( 'Manufacturer warranty where applicable', 'powerplug' ), __( 'Where applicable', 'powerplug' ), __( 'Unknown', 'powerplug' ) ),
			array( __( 'Expert help choosing', 'powerplug' ), __( 'Yes', 'powerplug' ), __( 'No', 'powerplug' ) ),
		);
		echo '<section class="pp-lp pp-lp-compare-sec"><div class="pp-lp-wrap">';
		echo '<div class="pp-lp-head pp-lp-center"><span class="pp-lp-eyebrow">' . esc_html__( 'Buy with confidence', 'powerplug' ) . '</span><h2 class="pp-lp-h2">' . esc_html__( 'Power Tools Plug vs. random online sellers', 'powerplug' ) . '</h2></div>';
		echo '<div class="pp-lp-compare-wrap"><table class="pp-lp-compare"><thead><tr><th></th><th class="pp-lp-us">' . esc_html__( 'Power Tools Plug', 'powerplug' ) . '</th><th>' . esc_html__( 'Unverified seller', 'powerplug' ) . '</th></tr></thead><tbody>';
		foreach ( $rows as $r ) {
			printf( '<tr><td>%s</td><td class="pp-lp-us">%s</td><td>%s</td></tr>', esc_html( $r[0] ), esc_html( $r[1] ), esc_html( $r[2] ) );
		}
		echo '</tbody></table></div></div></section>';
	}

	private static function shipping_warranty(): void {
		echo '<section class="pp-lp pp-lp-sw-sec"><div class="pp-lp-wrap pp-lp-split">';
		echo '<div class="pp-lp-card"><h3>' . esc_html__( 'Delivery & dispatch', 'powerplug' ) . '</h3><p>' . esc_html__( 'Nairobi: KSh 300, same/next day. Rest of Kenya: about KSh 500, 1–5 business days via trusted courier. Rider delivery with pay-on-delivery in Nairobi & Kiambu.', 'powerplug' ) . '</p></div>';
		echo '<div class="pp-lp-card"><h3>' . esc_html__( 'Warranty & returns', 'powerplug' ) . '</h3><p>' . esc_html__( 'Manufacturer warranty applies where available; terms vary by product and are confirmed before purchase. Return unused items in original packaging within 7 days. Faulty units are repaired, replaced or refunded at our cost.', 'powerplug' ) . '</p></div>';
		echo '</div></section>';
	}

	/**
	 * @param array<string,mixed>   $cfg
	 * @param array<int,\WC_Product> $products
	 */
	private static function order_form( array $cfg, array $products, string $wa ): void {
		echo '<section class="pp-lp pp-lp-order-sec" id="pp-lp-order"><div class="pp-lp-wrap">';
		echo '<div class="pp-lp-head pp-lp-center"><span class="pp-lp-eyebrow">' . esc_html__( 'Place your order', 'powerplug' ) . '</span><h2 class="pp-lp-h2">' . esc_html__( 'Order in 30 seconds — pay on delivery', 'powerplug' ) . '</h2><p class="pp-lp-lead">' . esc_html__( 'Fill in your details and we confirm on WhatsApp. No prepayment for pay-on-delivery areas.', 'powerplug' ) . '</p></div>';
		echo '<form class="pp-lp-order pp-lp-reveal" data-pp-order data-wa="' . esc_attr( $wa ) . '" novalidate>';
		echo '<div class="pp-lp-form-grid">';
		echo '<div class="pp-lp-field"><label for="pp-f-name">' . esc_html__( 'Full name', 'powerplug' ) . ' *</label><input id="pp-f-name" name="name" type="text" autocomplete="name" required></div>';
		echo '<div class="pp-lp-field"><label for="pp-f-phone">' . esc_html__( 'Phone number', 'powerplug' ) . ' *</label><input id="pp-f-phone" name="phone" type="tel" inputmode="tel" autocomplete="tel" required></div>';
		echo '<div class="pp-lp-field"><label for="pp-f-county">' . esc_html__( 'County', 'powerplug' ) . ' *</label><input id="pp-f-county" name="county" type="text" required></div>';
		echo '<div class="pp-lp-field"><label for="pp-f-town">' . esc_html__( 'Town / area', 'powerplug' ) . ' *</label><input id="pp-f-town" name="town" type="text" required></div>';
		echo '<div class="pp-lp-field pp-lp-field--full"><label for="pp-f-model">' . esc_html( sprintf( __( '%s size', 'powerplug' ), rtrim( (string) $cfg['name'], 's' ) ) ) . ' *</label><select id="pp-f-model" name="model" required><option value="">' . esc_html__( 'Select…', 'powerplug' ) . '</option>';
		foreach ( $products as $p ) {
			printf( '<option value="%s">%s — %s</option>', esc_attr( (string) $p->get_name() ), esc_html( (string) $p->get_name() ), esc_html( wp_strip_all_tags( $p->get_price_html() ) ) );
		}
		echo '</select></div>';
		echo '<div class="pp-lp-field"><label for="pp-f-qty">' . esc_html__( 'Quantity', 'powerplug' ) . '</label><input id="pp-f-qty" name="qty" type="number" min="1" value="1"></div>';
		echo '<div class="pp-lp-field"><label for="pp-f-pay">' . esc_html__( 'Preferred payment', 'powerplug' ) . '</label><select id="pp-f-pay" name="pay"><option>' . esc_html__( 'Pay on Delivery', 'powerplug' ) . '</option><option>M-Pesa</option><option>' . esc_html__( 'Pay at shop', 'powerplug' ) . '</option></select></div>';
		echo '<div class="pp-lp-field pp-lp-field--full"><label for="pp-f-notes">' . esc_html__( 'Delivery notes (optional)', 'powerplug' ) . '</label><textarea id="pp-f-notes" name="notes" rows="2"></textarea></div>';
		echo '</div>';
		echo '<button class="pp-lp-btn pp-lp-btn--wa pp-lp-btn--block" type="submit">' . esc_html__( 'Submit order on WhatsApp', 'powerplug' ) . '</button>';
		echo '<p class="pp-lp-note pp-lp-center">' . esc_html__( 'You will be taken to WhatsApp with your order pre-filled. We confirm stock and delivery before dispatch.', 'powerplug' ) . '</p>';
		echo '</form>';
		echo '</div></section>';
	}

	private static function sticky_bar( string $wa ): void {
		echo '<div class="pp-lp-buybar" role="region" aria-label="' . esc_attr__( 'Quick order', 'powerplug' ) . '">';
		echo '<a class="pp-lp-btn pp-lp-btn--wa" href="https://wa.me/' . esc_attr( $wa ) . '" rel="nofollow noopener">' . esc_html__( 'WhatsApp', 'powerplug' ) . '</a>';
		echo '<a class="pp-lp-btn pp-lp-btn--cta" href="#pp-lp-order">' . esc_html__( 'Order Now — Pay on Delivery', 'powerplug' ) . '</a>';
		echo '</div>';
	}

	/**
	 * @return array<int,array<int,string>>
	 */
	/**
	 * Derive a product_cat slug from the page slug, e.g. lp-water-pumps => water-pumps.
	 */
	private static function slug_from_page( int $post_id ): string {
		$name = (string) get_post_field( 'post_name', $post_id );
		if ( 0 === strpos( $name, self::PREFIX ) ) {
			$found = self::resolve_category( substr( $name, strlen( self::PREFIX ) ) );
			if ( '' !== $found ) {
				return $found;
			}
		}
		return self::resolve_category( $name );
	}

	/* ------------------------------------------------------------------ */
	/* Funnel routing ( /lp-{category}/ )                                  */
	/* ------------------------------------------------------------------ */

	/**
	 * Funnel key -> candidate product_cat slugs. Extend with the
	 * `powerplug_funnel_aliases` filter in the child theme.
	 *
	 * @return array<string, array<int,string>>
	 */
	private static function aliases(): array {
		return (array) apply_filters(
			'powerplug_funnel_aliases',
			array(
				'incubators'          => array( 'incubators', 'egg-incubators', 'incubator' ),
				'demolition-breakers' => array( 'demolition-breakers', 'demolition-hammers', 'breakers', 'demolition-hammer' ),
				'vacuum-cleaners'     => array( 'vacuum-cleaners', 'vacuum-cleaner', 'vacuums' ),
				'pressure-washers'    => array( 'pressure-washers', 'pressure-washer', 'car-wash-equipment' ),
				'water-pumps'         => array( 'water-pumps', 'water-pump', 'pumps' ),
				'hardware-tools'      => array( 'hardware-tools', 'hand-tools', 'hardware' ),
				'weighing-scales'     => array( 'weighing-scales', 'weighing-scale', 'scales' ),
				'batteries'           => array( 'batteries', 'solar-batteries', 'battery' ),
				'welding-machines'    => array( 'welding-machines', 'welding-machine', 'welding' ),
				'solar-panels'        => array( 'solar-panels', 'solar-panel', 'solar' ),
				'solar-inverters'     => array( 'solar-inverters', 'solar-inverter', 'inverters' ),
				'grinders'            => array( 'grinders', 'angle-grinders', 'grinder' ),
			)
		);
	}

	/**
	 * Resolve a funnel key to an existing product_cat slug: exact -> aliases ->
	 * singular/plural -> category-name match. Returns '' when nothing matches.
	 */
	private static function resolve_category( string $key ): string {
		$key = sanitize_title( $key );
		if ( '' === $key || ! taxonomy_exists( 'product_cat' ) ) {
			return '';
		}
		$aliases = self::aliases();
		$cands   = array_merge( array( $key ), $aliases[ $key ] ?? array() );
		$cands[] = (string) preg_replace( '/s$/', '', $key );
		$cands[] = $key . 's';
		foreach ( array_unique( array_filter( $cands ) ) as $c ) {
			$term = get_term_by( 'slug', $c, 'product_cat' );
			if ( $term && ! is_wp_error( $term ) ) {
				return (string) $term->slug;
			}
		}
		$terms = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'hide_empty' => false,
				'name__like' => (string) preg_replace( '/s$/', '', str_replace( '-', ' ', $key ) ),
				'number'     => 1,
				'orderby'    => 'count',
				'order'      => 'DESC',
			)
		);
		if ( is_array( $terms ) && isset( $terms[0] ) && $terms[0] instanceof \WP_Term ) {
			return (string) $terms[0]->slug;
		}
		return '';
	}

	/**
	 * Read "xyz" from a single-segment /lp-xyz/ request path, else ''.
	 */
	private static function request_key(): string {
		$uri  = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$path = (string) wp_parse_url( $uri, PHP_URL_PATH );
		$home = (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH );
		if ( '' !== $home && '/' !== $home && 0 === strpos( $path, $home ) ) {
			$path = substr( $path, strlen( $home ) );
		}
		$path = trim( strtolower( $path ), '/' );
		if ( '' === $path || false !== strpos( $path, '/' ) || 0 !== strpos( $path, self::PREFIX ) ) {
			return '';
		}
		return sanitize_title( substr( $path, strlen( self::PREFIX ) ) );
	}

	/**
	 * Inspect the request once. A funnel is either a published lp- page, or an
	 * lp- URL with no published page whose key resolves to a category or has a
	 * built-in hero image (virtual funnel).
	 */
	private static function route(): void {
		if ( self::$routed ) {
			return;
		}
		self::$routed = true;
		if ( is_admin() ) {
			return;
		}
		$key = self::request_key();
		if ( '' === $key ) {
			return;
		}
		self::$funnel_key = $key;
		$page = get_page_by_path( self::PREFIX . $key );
		if ( $page instanceof \WP_Post && 'publish' === $page->post_status ) {
			return; // Real page: WordPress serves it; template_include forces the Ads template.
		}
		$cat = self::resolve_category( $key );
		if ( '' !== $cat || file_exists( get_template_directory() . '/assets/img/lp-' . $key . '-hero.jpg' ) ) {
			self::$virtual_slug = '' !== $cat ? $cat : $key;
		}
		if ( '' !== self::$virtual_slug ) {
			self::$settings_page_id = self::find_settings_page( $key, self::$virtual_slug );
		}
	}

	/**
	 * Find an existing landing page whose saved settings (advertised product IDs,
	 * From price, hero text/image...) should power this funnel even though its slug
	 * differs from the URL, e.g. "lp-incubators-2", "incubators-offer", or a draft.
	 * Matches pages on the Ads template by slug prefix, then by the saved category.
	 * Published pages win over drafts/private; trash is ignored.
	 */
	private static function find_settings_page( string $key, string $cat ): int {
		$ids = get_posts(
			array(
				'post_type'        => 'page',
				'post_status'      => array( 'publish', 'private', 'draft', 'pending', 'future' ),
				'posts_per_page'   => 50,
				'fields'           => 'ids',
				'no_found_rows'    => true,
				'suppress_filters' => true,
				'orderby'          => 'modified',
				'order'            => 'DESC',
				'meta_query'       => array( // phpcs:ignore WordPress.DB.SlowDBQuery
					array( 'key' => '_wp_page_template', 'value' => self::TEMPLATE ),
				),
			)
		);
		if ( ! is_array( $ids ) || array() === $ids ) {
			return 0;
		}
		$best      = 0;
		$best_rank = 99;
		foreach ( $ids as $id ) {
			$id     = (int) $id;
			$name   = (string) get_post_field( 'post_name', $id );
			$status = (string) get_post_status( $id );
			$saved  = (string) get_post_meta( $id, '_pp_lp_category', true );
			$rank   = 99;
			if ( 0 === strpos( $name, self::PREFIX . $key ) || 0 === strpos( $name, $key ) ) {
				$rank = 1;
			} elseif ( '' !== $saved && $saved === $cat ) {
				$rank = 2;
			} elseif ( '' === $saved && self::slug_from_page( $id ) === $cat ) {
				$rank = 3;
			}
			if ( 99 === $rank ) {
				continue;
			}
			if ( 'publish' !== $status ) {
				$rank += 10;
			}
			if ( $rank < $best_rank ) {
				$best      = $id;
				$best_rank = $rank;
			}
		}
		return $best;
	}

	/** True when the current request is a funnel (real lp- page or virtual). */
	private static function is_funnel(): bool {
		self::route();
		if ( '' !== self::$virtual_slug ) {
			return true;
		}
		return is_page() && 0 === strpos( (string) get_post_field( 'post_name', (int) get_queried_object_id() ), self::PREFIX );
	}

	/**
	 * Turn a virtual funnel URL into a product-category query before WordPress
	 * looks for a page/post with that slug, so it is never flagged as a 404.
	 *
	 * @param array<string,mixed> $vars Parsed query vars.
	 * @return array<string,mixed>
	 */
	public function route_request( $vars ) {
		self::route();
		if ( '' === self::$virtual_slug || ! taxonomy_exists( 'product_cat' ) ) {
			return $vars;
		}
		$term = get_term_by( 'slug', self::$virtual_slug, 'product_cat' );
		if ( ! $term || is_wp_error( $term ) ) {
			return $vars;
		}
		return array( 'product_cat' => self::$virtual_slug );
	}

	/**
	 * Plain-text diagnostics for a funnel URL: /lp-{slug}/?pp_lp_debug=1
	 * Shows only public routing facts (theme version, slug, matched category).
	 */
	public function debug(): void {
		if ( ! isset( $_GET['pp_lp_debug'] ) || '' === self::request_key() ) { // phpcs:ignore WordPress.Security.NonceVerification
			return;
		}
		self::route();
		$page = get_page_by_path( self::PREFIX . self::$funnel_key );
		nocache_headers();
		header( 'Content-Type: text/plain; charset=utf-8' );
		echo 'powerplug_version: ' . esc_html( POWERPLUG_VERSION ) . "\n";
		echo 'funnel_key: ' . esc_html( self::$funnel_key ) . "\n";
		echo 'page_found: ' . esc_html( $page instanceof \WP_Post ? $page->post_type . ' #' . $page->ID . ' (' . $page->post_status . ')' : 'none' ) . "\n";
		echo 'resolved_category: ' . esc_html( self::resolve_category( self::$funnel_key ) ) . "\n";
		echo 'virtual_slug: ' . esc_html( self::$virtual_slug ) . "\n";
		echo 'settings_page: ' . esc_html( self::$settings_page_id > 0 ? '#' . self::$settings_page_id . ' ' . get_post_field( 'post_name', self::$settings_page_id ) . ' (' . get_post_status( self::$settings_page_id ) . ') product_ids=' . get_post_meta( self::$settings_page_id, '_pp_lp_product_ids', true ) : 'none' ) . "\n";
		echo 'is_404: ' . ( is_404() ? 'yes' : 'no' ) . "\n";
		echo 'is_funnel: ' . ( self::is_funnel() ? 'yes' : 'no' ) . "\n";
		exit;
	}

	/**
	 * Stop WordPress returning a 404 ("Nothing found.") for a virtual funnel.
	 *
	 * @param bool      $preempt  Short-circuit flag.
	 * @param \WP_Query $wp_query Main query.
	 */
	public function pre_handle_404( $preempt, $wp_query ) {
		if ( $preempt ) {
			return $preempt;
		}
		self::route();
		if ( '' === self::$virtual_slug ) {
			return $preempt;
		}
		if ( $wp_query instanceof \WP_Query ) {
			$wp_query->is_404  = false;
			$wp_query->is_home = false;
		}
		status_header( 200 );
		return true;
	}

	/**
	 * Use the Ads landing template for every funnel URL.
	 *
	 * @param string $template Resolved template path.
	 */
	public function template_include( $template ) {
		if ( ! self::is_funnel() ) {
			return $template;
		}
		global $wp_query;
		if ( $wp_query instanceof \WP_Query && $wp_query->is_404 ) {
			$wp_query->is_404 = false;
			status_header( 200 );
		}
		$file = get_template_directory() . '/' . self::TEMPLATE;
		return file_exists( $file ) ? $file : $template;
	}

	/**
	 * Title for virtual funnels (real pages keep their own / Rank Math title).
	 *
	 * @param string $title Title.
	 */
	public function document_title( $title ) {
		self::route();
		if ( '' === self::$virtual_slug ) {
			return $title;
		}
		$term = get_term_by( 'slug', self::$virtual_slug, 'product_cat' );
		$name = ( $term && ! is_wp_error( $term ) ) ? $term->name : ucwords( str_replace( '-', ' ', self::$funnel_key ) );
		return sprintf( __( 'Shop %1$s in Kenya | %2$s', 'powerplug' ), $name, get_bloginfo( 'name' ) );
	}

	/**
	 * @param array<int,string> $classes Body classes.
	 * @return array<int,string>
	 */
	public function body_class( $classes ) {
		if ( self::is_funnel() ) {
			$classes   = array_values( array_diff( (array) $classes, array( 'error404' ) ) );
			$classes[] = 'pp-lp-page';
			$classes[] = 'page-template-template-lp-category';
		}
		return $classes;
	}

	private static function hero_base( string $slug ): string {
		foreach ( array( $slug, self::$funnel_key ) as $base ) {
			if ( strlen( $base ) > 0 && file_exists( get_template_directory() . '/assets/img/lp-' . $base . '-hero.jpg' ) ) {
				return $base;
			}
		}
		return '';
	}

	/**
	 * @return array<string,string>
	 */
	private static function copy_defaults( string $slug ): array {
		$map = array(
			'incubators' => array(
				'title' => 'Hatch More Chicks with an Automatic Egg Incubator',
				'sub'   => 'Incubators with digital temperature and humidity control and automatic egg turning — solar or mains power. Pay on delivery countrywide.',
			),
			'water-pumps' => array(
				'title' => 'Reliable Water Pumps for Home, Farm & Site',
				'sub'   => 'Borehole, submersible and surface pumps for clean water and irrigation. Warranty where applicable. Pay on delivery countrywide.',
			),
			'hardware-tools' => array(
				'title' => 'Hardware Tools That Last',
				'sub'   => 'Hand tools and hardware for builders, fundis and DIY. Warranty where applicable, with fast nationwide delivery and pay on delivery.',
			),
			'weighing-scales' => array(
				'title' => 'Accurate Weighing Scales for Your Business',
				'sub'   => 'Platform, counting and retail scales for shops, farms and warehouses. Warranty where applicable. Pay on delivery countrywide.',
			),
			'batteries' => array(
				'title' => 'Deep-Cycle & Solar Batteries Built to Last',
				'sub'   => 'Dependable batteries for solar, inverter and backup power. Warranty where applicable. Pay on delivery countrywide.',
			),
			'welding-machines' => array(
				'title' => 'Powerful Welding Machines for Every Job',
				'sub'   => 'MMA, MIG and TIG inverter welders for workshops and site. Warranty where applicable, with fast nationwide delivery and pay on delivery.',
			),
			'solar-panels' => array(
				'title' => 'High-Efficiency Solar Panels for Kenya',
				'sub'   => 'Monocrystalline panels for homes, farms and businesses. Warranty where applicable. Pay on delivery countrywide.',
			),
			'solar-inverters' => array(
				'title' => 'Solar Inverters for Clean, Steady Power',
				'sub'   => 'Hybrid and off-grid inverters to keep your home or business running through outages. Warranty where applicable. Pay on delivery countrywide.',
			),
			'grinders' => array(
				'title' => 'Angle Grinders Built for Hard Work',
				'sub'   => 'Cutting and grinding power for metal, masonry and fabrication. Warranty where applicable, with fast nationwide delivery and pay on delivery.',
			),
			'demolition-breakers' => array(
				'title' => 'Demolition Breakers That Power Through Concrete',
				'sub'   => 'Heavy-duty breakers and jackhammers for concrete, rock and tarmac. Warranty where applicable, with fast nationwide delivery and pay on delivery.',
			),
			'pressure-washers' => array(
				'title' => 'High-Pressure Washers for a Spotless Finish',
				'sub'   => 'Powerful pressure washers for cars, yards, walls and equipment. Warranty where applicable. Pay on delivery countrywide.',
			),
			'vacuum-cleaners' => array(
				'title' => 'Powerful Vacuum Cleaners for Home and Workshop',
				'sub'   => 'Wet and dry vacuum cleaners for homes, offices and workshops. Warranty where applicable, with fast nationwide delivery and pay on delivery.',
			),
		);
		return isset( $map[ $slug ] ) ? $map[ $slug ] : array( 'title' => '', 'sub' => '' );
	}

	/**
	 * @return array<int,array<int,string>>
	 */
	private static function generic_benefits(): array {
		return array(
			array( 'Warranty where applicable', 'Manufacturer warranty where available. Terms vary by product.' ),
			array( 'Countrywide delivery', 'Delivered countrywide, typically within 1 to 5 business days.' ),
			array( 'Pay on delivery', 'Inspect your order, then pay by M-Pesa or cash on delivery.' ),
			array( 'Expert advice', 'Talk to real tool specialists before and after you buy.' ),
			array( 'Nationwide delivery', 'Delivered countrywide, typically in 1 to 5 business days.' ),
			array( 'Physical shop in Nairobi', 'Visit us at Magomano House, Tom Mboya St, Nairobi to see stock in person.' ),
		);
	}

	private static function parse_pipe( string $raw ): array {
		$out = array();
		foreach ( preg_split( '/\r\n|\r|\n/', $raw ) as $line ) {
			$line = trim( (string) $line );
			if ( '' === $line ) {
				continue;
			}
			$parts = array_map( 'trim', explode( '|', $line, 2 ) );
			$out[] = array( $parts[0], isset( $parts[1] ) ? $parts[1] : '' );
		}
		return $out;
	}
}
