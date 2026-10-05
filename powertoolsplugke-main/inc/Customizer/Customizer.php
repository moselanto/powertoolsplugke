<?php
declare( strict_types=1 );

namespace PowerPlug\Customizer;

use PowerPlug\Core\Bootable;

defined( 'ABSPATH' ) || exit;

/**
 * Theme Customizer: brandable header + contact settings (no hardcoded data).
 */
final class Customizer implements Bootable {

	public function boot(): void {
		add_action( 'customize_register', [ $this, 'register' ] );
	}

	/**
	 * @return array<string,string>
	 */
	public static function defaults(): array {
		return [
			'pp_phone'         => '+254 708 777192',
			'pp_whatsapp'      => '254708777192',
			'pp_email'         => 'info@powertoolsplugke.co.ke',
			'pp_hours'         => 'Mon - Sat, 9AM - 5PM',
			'pp_address'       => 'Magomano House, 1st Floor, Room 10D, Tom Mboya Street, Nairobi',
			'pp_gbp_url'       => '',
				'pp_priority_cat'  => 'incubators',
			'pp_topbar_notice' => 'Power tools, solar & equipment  |  Pay by M-Pesa or on delivery',
			'pp_default_warranty' => 'Manufacturer warranty where applicable. Warranty terms vary by product and are confirmed before purchase.',
				'pp_wa_order_msg'  => 'Hi Power Tools Plug, I would like to order: {product} ({url})',
		];
	}

	public static function val( string $key ): string {
		$defaults = self::defaults();
		$fallback = isset( $defaults[ $key ] ) ? $defaults[ $key ] : '';
		return (string) get_theme_mod( $key, $fallback );
	}

	public function register( \WP_Customize_Manager $wp ): void {
		$wp->add_panel( 'powerplug', [ 'title' => __( 'PowerPlug Pro', 'powerplug' ), 'priority' => 20 ] );
		$wp->add_section( 'pp_contact', [ 'title' => __( 'Header & Contact', 'powerplug' ), 'panel' => 'powerplug' ] );

		$fields = [
			'pp_phone'         => [ 'label' => __( 'Phone number', 'powerplug' ), 'type' => 'text' ],
			'pp_whatsapp'      => [ 'label' => __( 'WhatsApp number (digits only)', 'powerplug' ), 'type' => 'text' ],
			'pp_email'         => [ 'label' => __( 'Support email', 'powerplug' ), 'type' => 'email' ],
			'pp_hours'         => [ 'label' => __( 'Support hours', 'powerplug' ), 'type' => 'text' ],
			'pp_address'       => [ 'label' => __( 'Shop address', 'powerplug' ), 'type' => 'text' ],
			'pp_topbar_notice' => [ 'label' => __( 'Top bar promo text', 'powerplug' ), 'type' => 'text' ],
		];
		$defaults = self::defaults();

		foreach ( $fields as $key => $meta ) {
			$is_email  = ( 'email' === $meta['type'] );
			$sanitizer = $is_email ? 'sanitize_email' : 'sanitize_text_field';
			$wp->add_setting( $key, [
				'default'           => $defaults[ $key ],
				'sanitize_callback' => $sanitizer,
				'transport'         => 'refresh',
			] );
			$wp->add_control( $key, [
				'label'   => $meta['label'],
				'section' => 'pp_contact',
				'type'    => $is_email ? 'email' : 'text',
			] );
		}

		$wp->add_setting( 'pp_gbp_url', [ 'default' => '', 'sanitize_callback' => 'esc_url_raw', 'transport' => 'refresh' ] );
		$wp->add_control( 'pp_gbp_url', [ 'label' => __( 'Google Business Profile URL (for reviews link)', 'powerplug' ), 'section' => 'pp_contact', 'type' => 'url' ] );

		$wp->add_setting( 'pp_wa_order_msg', [ 'default' => 'Hi Power Tools Plug, I would like to order: {product} ({url})', 'sanitize_callback' => 'sanitize_text_field', 'transport' => 'refresh' ] );
		$wp->add_control( 'pp_wa_order_msg', [ 'label' => __( 'WhatsApp order message', 'powerplug' ), 'description' => __( 'Prefilled text for the Order on WhatsApp buttons. Use {product} for the product name and {url} for its link.', 'powerplug' ), 'section' => 'pp_contact', 'type' => 'textarea' ] );

		$wp->add_setting( 'pp_default_warranty', [ 'default' => $defaults['pp_default_warranty'], 'sanitize_callback' => 'sanitize_textarea_field', 'transport' => 'refresh' ] );
		$wp->add_control( 'pp_default_warranty', [ 'label' => __( 'Default warranty statement', 'powerplug' ), 'description' => __( 'Shown on product pages that have no specific warranty terms set. Keep it accurate for the products you actually offer.', 'powerplug' ), 'section' => 'pp_contact', 'type' => 'textarea' ] );

		$this->register_hero( $wp );
		$this->register_home( $wp );
		$this->register_testimonials( $wp );
		$this->register_branding( $wp );
	}

	private function register_branding( \WP_Customize_Manager $wp ): void {
		$wp->add_section( 'pp_branding', array( 'title' => __( 'Branding & Colors', 'powerplug' ), 'panel' => 'powerplug' ) );
		$wp->add_setting( 'pp_brand_color', array( 'default' => '#268655', 'sanitize_callback' => 'sanitize_hex_color', 'transport' => 'refresh' ) );
		$wp->add_control( new \WP_Customize_Color_Control( $wp, 'pp_brand_color', array( 'label' => __( 'Primary brand color', 'powerplug' ), 'section' => 'pp_branding' ) ) );
		$wp->add_setting( 'pp_ink_color', array( 'default' => '#111418', 'sanitize_callback' => 'sanitize_hex_color', 'transport' => 'refresh' ) );
		$wp->add_control( new \WP_Customize_Color_Control( $wp, 'pp_ink_color', array( 'label' => __( 'Headings & text color', 'powerplug' ), 'section' => 'pp_branding' ) ) );
	}

    private function register_hero( \WP_Customize_Manager $wp ): void {
        $uri = get_template_directory_uri();
        $wp->add_section( 'pp_hero', array( 'title' => __( 'Hero Slider & Categories', 'powerplug' ), 'panel' => 'powerplug' ) );

        $wp->add_setting( 'pp_hero_cats_title', array( 'default' => 'Shop by Category', 'sanitize_callback' => 'sanitize_text_field', 'transport' => 'refresh' ) );
        $wp->add_control( 'pp_hero_cats_title', array( 'label' => __( 'Category menu heading', 'powerplug' ), 'section' => 'pp_hero', 'type' => 'text' ) );

        $wp->add_setting( 'pp_hero_cats_count', array( 'default' => 8, 'sanitize_callback' => 'absint', 'transport' => 'refresh' ) );
        $wp->add_control( 'pp_hero_cats_count', array( 'label' => __( 'Categories in left menu', 'powerplug' ), 'description' => __( 'Or assign a Hero Categories menu under Appearance > Menus for full control of the list and order.', 'powerplug' ), 'section' => 'pp_hero', 'type' => 'number', 'input_attrs' => array( 'min' => 4, 'max' => 15 ) ) );

        $slide_defaults = array( 1 => $uri . '/assets/img/slide-1.jpg', 2 => $uri . '/assets/img/slide-2.jpg', 3 => $uri . '/assets/img/slide-3.jpg', 4 => '' );
        for ( $i = 1; $i <= 4; $i++ ) {
            $wp->add_setting( 'pp_slide_' . $i, array( 'default' => $slide_defaults[ $i ], 'sanitize_callback' => 'esc_url_raw', 'transport' => 'refresh' ) );
            $wp->add_control( new \WP_Customize_Image_Control( $wp, 'pp_slide_' . $i, array( 'label' => sprintf( __( 'Slide %d image', 'powerplug' ), $i ), 'section' => 'pp_hero' ) ) );

            $wp->add_setting( 'pp_slide_' . $i . '_title', array( 'default' => '', 'sanitize_callback' => 'sanitize_text_field', 'transport' => 'refresh' ) );
            $wp->add_control( 'pp_slide_' . $i . '_title', array( 'label' => sprintf( __( 'Slide %d heading', 'powerplug' ), $i ), 'section' => 'pp_hero', 'type' => 'text' ) );

            $wp->add_setting( 'pp_slide_' . $i . '_sub', array( 'default' => '', 'sanitize_callback' => 'sanitize_text_field', 'transport' => 'refresh' ) );
            $wp->add_control( 'pp_slide_' . $i . '_sub', array( 'label' => sprintf( __( 'Slide %d subtitle', 'powerplug' ), $i ), 'section' => 'pp_hero', 'type' => 'text' ) );

            $wp->add_setting( 'pp_slide_' . $i . '_btn', array( 'default' => '', 'sanitize_callback' => 'sanitize_text_field', 'transport' => 'refresh' ) );
            $wp->add_control( 'pp_slide_' . $i . '_btn', array( 'label' => sprintf( __( 'Slide %d button text', 'powerplug' ), $i ), 'section' => 'pp_hero', 'type' => 'text' ) );

            $wp->add_setting( 'pp_slide_' . $i . '_url', array( 'default' => '', 'sanitize_callback' => 'esc_url_raw', 'transport' => 'refresh' ) );
            $wp->add_control( 'pp_slide_' . $i . '_url', array( 'label' => sprintf( __( 'Slide %d button link', 'powerplug' ), $i ), 'section' => 'pp_hero', 'type' => 'url' ) );
        }
    }

    private function register_home( \WP_Customize_Manager $wp ): void {
        $wp->add_section( 'pp_home', array( 'title' => __( 'Homepage Sections', 'powerplug' ), 'panel' => 'powerplug' ) );

        $wp->add_setting( 'pp_featured_title', array( 'default' => 'Shop by Category', 'sanitize_callback' => 'sanitize_text_field', 'transport' => 'refresh' ) );
        $wp->add_control( 'pp_featured_title', array( 'label' => __( 'Featured categories heading', 'powerplug' ), 'section' => 'pp_home', 'type' => 'text' ) );

        $wp->add_setting( 'pp_featured_count', array( 'default' => 12, 'sanitize_callback' => 'absint', 'transport' => 'refresh' ) );
        $wp->add_control( 'pp_featured_count', array( 'label' => __( 'Number of featured category cards', 'powerplug' ), 'section' => 'pp_home', 'type' => 'number', 'input_attrs' => array( 'min' => 4, 'max' => 24 ) ) );

        $wp->add_setting( 'pp_section_cats', array( 'default' => 6, 'sanitize_callback' => 'absint', 'transport' => 'refresh' ) );
        $wp->add_control( 'pp_section_cats', array( 'label' => __( 'Category product sections to show', 'powerplug' ), 'section' => 'pp_home', 'type' => 'number', 'input_attrs' => array( 'min' => 1, 'max' => 12 ) ) );

        $wp->add_setting( 'pp_section_products', array( 'default' => 6, 'sanitize_callback' => 'absint', 'transport' => 'refresh' ) );
        $wp->add_control( 'pp_section_products', array( 'label' => __( 'Products per category row', 'powerplug' ), 'section' => 'pp_home', 'type' => 'number', 'input_attrs' => array( 'min' => 2, 'max' => 12 ) ) );

        $wp->add_setting( 'pp_products_order', array( 'default' => 'rand', 'sanitize_callback' => array( __CLASS__, 'sanitize_order' ), 'transport' => 'refresh' ) );
        $wp->add_control( 'pp_products_order', array( 'label' => __( 'Product order in homepage rows', 'powerplug' ), 'description' => __( 'Random shows a fresh mix from each category on every visit (recommended). Latest first keeps the rows fixed.', 'powerplug' ), 'section' => 'pp_home', 'type' => 'select', 'choices' => array( 'rand' => __( 'Random (changes on refresh)', 'powerplug' ), 'date' => __( 'Latest first', 'powerplug' ), 'popularity' => __( 'Best selling', 'powerplug' ), 'price' => __( 'Price: low to high', 'powerplug' ) ) ) );

        $wp->add_setting( 'pp_priority_cat', array( 'default' => 'incubators', 'sanitize_callback' => 'sanitize_title', 'transport' => 'refresh' ) );
        $wp->add_control( 'pp_priority_cat', array( 'label' => __( 'Priority category (slug) shown first', 'powerplug' ), 'description' => __( 'Category slug pinned first on the homepage, e.g. incubators. Leave blank to order by popularity.', 'powerplug' ), 'section' => 'pp_home', 'type' => 'text' ) );

        $wp->add_setting( 'pp_home_cats', array( 'default' => '', 'sanitize_callback' => 'sanitize_text_field', 'transport' => 'refresh' ) );
        $wp->add_control( 'pp_home_cats', array( 'label' => __( 'Homepage category rows (slugs)', 'powerplug' ), 'description' => __( 'Comma-separated category slugs shown as product rows below Featured Categories, in this order (e.g. incubators, water-pumps, drills). Leave blank to auto-pick by popularity.', 'powerplug' ), 'section' => 'pp_home', 'type' => 'text' ) );

        $wp->add_setting( 'pp_featured_cats', array( 'default' => '', 'sanitize_callback' => 'sanitize_text_field', 'transport' => 'refresh' ) );
        $wp->add_control( 'pp_featured_cats', array( 'label' => __( 'Shop by Category cards (slugs)', 'powerplug' ), 'description' => __( 'Comma-separated category slugs shown as the Shop by Category cards at the top of the homepage, in this order (e.g. incubators, water-pumps, welding-machines). Leave blank to auto-pick the most popular categories.', 'powerplug' ), 'section' => 'pp_home', 'type' => 'text' ) );

        $wp->add_setting( 'pp_brands', array( 'default' => 'Total, Ingco, Maxmech, DCA', 'sanitize_callback' => 'sanitize_text_field', 'transport' => 'refresh' ) );
        $wp->add_control( 'pp_brands', array( 'label' => __( 'Brands you stock (comma-separated)', 'powerplug' ), 'description' => __( 'Shown in the footer text and the Shop by Brand chips on the homepage. List only brands you genuinely stock (e.g. Total, Ingco, Maxmech, DCA). Keeping this accurate protects your store with Google.', 'powerplug' ), 'section' => 'pp_home', 'type' => 'text' ) );
    }

    private function register_testimonials( \WP_Customize_Manager $wp ): void {
        $wp->add_section( 'pp_reviews', array( 'title' => __( 'Customer Reviews', 'powerplug' ), 'panel' => 'powerplug', 'description' => __( 'Approved WooCommerce product reviews (4 stars and up) appear here automatically. Until you have some, you can seed this with genuine customer quotes below. Do not enter fake reviews.', 'powerplug' ) ) );

        $wp->add_setting( 'pp_reviews_title', array( 'default' => 'What our customers say', 'sanitize_callback' => 'sanitize_text_field', 'transport' => 'refresh' ) );
        $wp->add_control( 'pp_reviews_title', array( 'label' => __( 'Section heading', 'powerplug' ), 'section' => 'pp_reviews', 'type' => 'text' ) );

        for ( $i = 1; $i <= 6; $i++ ) {
            $wp->add_setting( 'pp_review_' . $i . '_quote', array( 'default' => '', 'sanitize_callback' => 'sanitize_textarea_field', 'transport' => 'refresh' ) );
            $wp->add_control( 'pp_review_' . $i . '_quote', array( 'label' => sprintf( __( 'Review %d — quote', 'powerplug' ), $i ), 'section' => 'pp_reviews', 'type' => 'textarea' ) );

            $wp->add_setting( 'pp_review_' . $i . '_author', array( 'default' => '', 'sanitize_callback' => 'sanitize_text_field', 'transport' => 'refresh' ) );
            $wp->add_control( 'pp_review_' . $i . '_author', array( 'label' => sprintf( __( 'Review %d — customer name', 'powerplug' ), $i ), 'section' => 'pp_reviews', 'type' => 'text' ) );

            $wp->add_setting( 'pp_review_' . $i . '_meta', array( 'default' => '', 'sanitize_callback' => 'sanitize_text_field', 'transport' => 'refresh' ) );
            $wp->add_control( 'pp_review_' . $i . '_meta', array( 'label' => sprintf( __( 'Review %d — location or product (optional)', 'powerplug' ), $i ), 'section' => 'pp_reviews', 'type' => 'text' ) );

            $wp->add_setting( 'pp_review_' . $i . '_rating', array( 'default' => 5, 'sanitize_callback' => 'absint', 'transport' => 'refresh' ) );
            $wp->add_control( 'pp_review_' . $i . '_rating', array( 'label' => sprintf( __( 'Review %d — stars (1-5)', 'powerplug' ), $i ), 'section' => 'pp_reviews', 'type' => 'number', 'input_attrs' => array( 'min' => 1, 'max' => 5 ) ) );
        }
    }

    public static function sanitize_order( $value ): string {
        $allowed = array( 'rand', 'date', 'popularity', 'price' );
        return in_array( (string) $value, $allowed, true ) ? (string) $value : 'rand';
    }
}
