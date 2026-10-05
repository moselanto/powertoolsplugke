<?php
declare( strict_types=1 );

namespace PowerPlug\Front;

defined( 'ABSPATH' ) || exit;

/**
 * Homepage section renderer.
 *
 * Pulls live WooCommerce data (top categories, brands, featured & recent
 * products) so the homepage is never a static placeholder — it reflects the
 * real catalogue the moment products exist.
 */
final class Home {

	public static function hero(): void {
		$uri = get_template_directory_uri();
		$slides = array(
			get_theme_mod( 'pp_slide_1', $uri . '/assets/img/slide-1.jpg' ),
			get_theme_mod( 'pp_slide_2', $uri . '/assets/img/slide-2.jpg' ),
			get_theme_mod( 'pp_slide_3', $uri . '/assets/img/slide-3.jpg' ),
			get_theme_mod( 'pp_slide_4', '' ),
			get_theme_mod( 'pp_slide_5', '' ),
			get_theme_mod( 'pp_slide_6', '' ),
		);
		$slides = array_values( array_filter( $slides ) );
		?>
		<section class="pp-hero" aria-label="<?php esc_attr_e( 'Featured', 'powerplug' ); ?>">
			<?php foreach ( $slides as $slide ) : ?>
				<?php if ( $slide ) : ?>
					<div class="pp-hero__slide" style="background-image:url('<?php echo esc_url( $slide ); ?>')"></div>
				<?php endif; ?>
			<?php endforeach; ?>
			<script>(function(){var s=document.querySelectorAll('.pp-hero__slide');if(s.length<1){return;}var r=window.matchMedia('(prefers-reduced-motion: reduce)').matches;var i=0;s[0].classList.add('is-on');if(r||s.length<2){return;}setInterval(function(){s[i].classList.remove('is-on');i=(i+1)%s.length;s[i].classList.add('is-on');},6000);})();</script>
			<div class="pp-hero__scrim" aria-hidden="true"></div>
			<div class="pp-hero__inner">
				<div class="pp-hero__copy">
					<p class="pp-hero__eyebrow"><?php esc_html_e( 'Nairobi · Nationwide delivery · M-Pesa', 'powerplug' ); ?></p>
					<h1 class="pp-hero__title"><?php esc_html_e( 'Power Tools & Industrial Equipment in Kenya', 'powerplug' ); ?></h1>
					<p class="pp-hero__sub"><?php esc_html_e( 'Makita, Bosch, DeWalt, Ingco & Total — warranty where applicable, dispatched same day in Nairobi.', 'powerplug' ); ?></p>
					<div class="pp-hero__cta">
						<a class="button" href="<?php echo esc_url( function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' ) ); ?>"><?php esc_html_e( 'Shop all tools', 'powerplug' ); ?></a>
						<a class="button button-ghost" href="https://wa.me/<?php echo esc_attr( preg_replace( '/[^0-9]/', '', \PowerPlug\Customizer\Customizer::val( 'pp_whatsapp' ) ) ); ?>" rel="noopener"><?php esc_html_e( 'Chat on WhatsApp', 'powerplug' ); ?></a>
					</div>
				</div>
			</div>
		</section>
		<?php
	}

    protected static function prioritize( array $terms, int $limit ): array {
        $slug = trim( (string) get_theme_mod( 'pp_priority_cat', 'incubators' ) );
        if ( $slug === '' ) {
            return $terms;
        }
        $front = null;
        $rest  = array();
        foreach ( $terms as $t ) {
            if ( $front === null && isset( $t->slug ) && $t->slug === $slug ) {
                $front = $t;
            } else {
                $rest[] = $t;
            }
        }
        if ( $front === null ) {
            $term = get_term_by( 'slug', $slug, 'product_cat' );
            if ( $term && ( is_wp_error( $term ) === false ) && (int) $term->count > 0 ) {
                $front = $term;
            }
        }
        if ( $front === null ) {
            return $terms;
        }
        array_unshift( $rest, $front );
        if ( $limit > 0 && count( $rest ) > $limit ) {
            $rest = array_slice( $rest, 0, $limit );
        }
        return $rest;
    }

    public static function featured_categories(): void {
        if ( taxonomy_exists( 'product_cat' ) === false ) {
            return;
        }
        $count = (int) get_theme_mod( 'pp_featured_count', 12 );
        if ( $count < 1 ) {
            $count = 12;
        }
        $heading = (string) get_theme_mod( 'pp_featured_title', __( 'Shop by Category', 'powerplug' ) );

        $pp_manual = (string) get_theme_mod( 'pp_featured_cats', '' );
        $pp_mterms = array();
        if ( strlen( trim( $pp_manual ) ) > 0 ) {
            foreach ( array_filter( array_map( 'trim', explode( ',', $pp_manual ) ) ) as $mslug ) {
                $mt = get_term_by( 'slug', sanitize_title( $mslug ), 'product_cat' );
                if ( $mt && false === is_wp_error( $mt ) ) {
                    $pp_mterms[] = $mt;
                }
            }
        }
        $terms   = \PowerPlug\Support\Cache::remember( 'pp_featured_cats_' . (int) $count, 6 * HOUR_IN_SECONDS, static function () use ( $count ) { $r = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => true, 'number' => $count, 'orderby' => 'count', 'order' => 'DESC', 'exclude' => array( (int) get_option( 'default_product_cat' ) ) ) ); return is_array( $r ) ? $r : array(); } );
        $terms = ( count( $pp_mterms ) > 0 ) ? $pp_mterms : self::prioritize( $terms, $count );
        if ( is_wp_error( $terms ) || empty( $terms ) ) {
            return;
        }
        echo '<section class="pp-section pp-featured-cats"><div class="pp-container">';
        echo '<div class="pp-featured-cats__head"><h2 class="pp-section__title">' . esc_html( $heading ) . '</h2>';
        echo '<div class="pp-catscroll__nav"><button class="pp-catscroll__arrow" type="button" data-pp-cats-prev aria-label="' . esc_attr__( 'Scroll left', 'powerplug' ) . '">&#8249;</button><button class="pp-catscroll__arrow" type="button" data-pp-cats-next aria-label="' . esc_attr__( 'Scroll right', 'powerplug' ) . '">&#8250;</button></div></div>';
        echo '<div class="pp-catscroll" data-pp-catscroll><div class="pp-catscroll__track">';
        foreach ( $terms as $t ) {
            $thumb_id = get_term_meta( $t->term_id, 'thumbnail_id', true );
            $img      = $thumb_id ? wp_get_attachment_image( (int) $thumb_id, 'medium', false, array( 'loading' => 'lazy' ) ) : self::category_fallback_image( (string) $t->slug, (string) $t->name );
            printf(
                '<a class="pp-cat-card" href="%s"><span class="pp-cat-card__img">%s</span><span class="pp-cat-card__name">%s</span><span class="pp-cat-card__count">%d %s</span></a>',
                esc_url( (string) get_term_link( $t ) ),
                $img,
                esc_html( $t->name ),
                (int) $t->count,
                esc_html__( 'items', 'powerplug' )
            );
        }
        echo '</div></div>';
        echo '</div></section>';
    }

	public static function category_fallback_image( string $slug, string $name ): string {
		$slug = sanitize_title( $slug );
		$dir  = get_template_directory() . '/assets/img/categories/';
		$uri  = get_template_directory_uri() . '/assets/img/categories/';
		if ( '' !== $slug ) {
			foreach ( array( 'webp', 'jpg', 'png' ) as $ext ) {
				if ( file_exists( $dir . $slug . '.' . $ext ) ) {
					return sprintf( '<img src="%s" alt="%s" loading="lazy" decoding="async" width="320" height="320" />', esc_url( $uri . $slug . '.' . $ext ), esc_attr( $name ) );
				}
			}
		}
		if ( file_exists( $dir . 'default.jpg' ) ) {
			return sprintf( '<img src="%s" alt="%s" loading="lazy" decoding="async" width="320" height="320" />', esc_url( $uri . 'default.jpg' ), esc_attr( $name ) );
		}
		return '<span class="pp-cat-card__ph" aria-hidden="true"></span>';
	}

	public static function shop_by_brand(): void {
		$pp_brand_raw = trim( (string) get_theme_mod( 'pp_brands', 'Total, Ingco, Maxmech, DCA' ) ); $brands = array_filter( array_map( 'trim', explode( ',', $pp_brand_raw ) ) ); if ( empty( $brands ) ) { $brands = array( 'Total', 'Ingco', 'Maxmech', 'DCA' ); }
		echo '<section class="pp-section pp-section--muted"><div class="pp-container"><h2 class="pp-section__title">' . esc_html__( 'Shop by brand', 'powerplug' ) . '</h2><div class="pp-brands">';
		foreach ( $brands as $b ) {
			printf(
				'<a class="pp-brand-chip" href="%s">%s</a>',
				esc_url( add_query_arg( 'brand', sanitize_title( $b ), function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' ) ) ),
				esc_html( $b )
			);
		}
		echo '</div></div></section>';
	}

	/**
	 * @param string $heading section title
	 * @param array<string,mixed> $args wc_get_products args
	 */
	public static function product_row( string $heading, array $args ): void {
		if ( ! function_exists( 'wc_get_products' ) ) {
			return;
		}
		$defaults = [ 'status' => 'publish', 'limit' => 8, 'orderby' => 'date', 'order' => 'DESC' ];
		$products = wc_get_products( array_merge( $defaults, $args ) );
		if ( empty( $products ) ) {
			return;
		}
		echo '<section class="pp-section"><div class="pp-container"><h2 class="pp-section__title">' . esc_html( $heading ) . '</h2><div class="pp-grid pp-grid--products">';
		foreach ( $products as $product ) {
			echo '<div class="pp-product-card">';
			printf( '<a href="%s" class="pp-product-card__img">%s</a>', esc_url( (string) get_permalink( $product->get_id() ) ), $product->get_image( 'woocommerce_thumbnail' ) );
			printf( '<a href="%s" class="pp-product-card__name">%s</a>', esc_url( (string) get_permalink( $product->get_id() ) ), esc_html( $product->get_name() ) );
			echo '<div class="pp-product-card__price">' . wp_kses_post( $product->get_price_html() ) . '</div>';
			echo '</div>';
		}
		echo '</div></div></section>';
	}

    public static function hero_row(): void {
        $shop = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' );
        $uri  = get_template_directory_uri();

        $cats_title = (string) get_theme_mod( 'pp_hero_cats_title', __( 'Shop by Category', 'powerplug' ) );
        $cats_count = (int) get_theme_mod( 'pp_hero_cats_count', 8 );
        if ( $cats_count < 1 ) {
            $cats_count = 8;
        }

        $slide_defaults = array(
            1 => $uri . '/assets/img/slide-1.jpg',
            2 => $uri . '/assets/img/slide-2.jpg',
            3 => $uri . '/assets/img/slide-3.jpg',
            4 => '',
        );
        $slides = array();
        for ( $i = 1; $i <= 4; $i++ ) {
            $img = (string) get_theme_mod( 'pp_slide_' . $i, $slide_defaults[ $i ] );
            if ( strlen( $img ) === 0 ) {
                continue;
            }
            $pp_cap_titles = array(
                1 => __( 'Power Tools, Solar & Generators', 'powerplug' ),
                2 => __( 'Pay on Delivery, Countrywide', 'powerplug' ),
                3 => __( 'Visit Our Nairobi Shop', 'powerplug' ),
            );
            $pp_cap_subs = array(
                1 => __( 'Warranty where applicable. Nationwide delivery and M-Pesa.', 'powerplug' ),
                2 => __( 'Order today and pay when it arrives — M-Pesa or cash on delivery.', 'powerplug' ),
                3 => __( 'See stock in person at Magomano House, Tom Mboya Street, Nairobi.', 'powerplug' ),
            );
            $d_title = isset( $pp_cap_titles[ $i ] ) ? $pp_cap_titles[ $i ] : '';
            $d_sub   = isset( $pp_cap_subs[ $i ] ) ? $pp_cap_subs[ $i ] : '';
            $d_btn   = ( strlen( $d_title ) > 0 ) ? __( 'Shop all products', 'powerplug' ) : '';
            $slides[] = array(
                'img'   => $img,
                'title' => (string) get_theme_mod( 'pp_slide_' . $i . '_title', $d_title ),
                'sub'   => (string) get_theme_mod( 'pp_slide_' . $i . '_sub', $d_sub ),
                'btn'   => (string) get_theme_mod( 'pp_slide_' . $i . '_btn', $d_btn ),
                'url'   => (string) get_theme_mod( 'pp_slide_' . $i . '_url', (string) $shop ),
            );
        }

        echo '<section class="pp-herorow"><div class="pp-container pp-herorow__grid">';

        echo '<aside class="pp-herocats"><h2 class="pp-herocats__title">' . esc_html( $cats_title ) . '</h2>';
        if ( has_nav_menu( 'hero-categories' ) ) {
            wp_nav_menu( array(
                'theme_location' => 'hero-categories',
                'container'      => false,
                'menu_class'     => 'pp-herocats__list',
                'depth'          => 1,
                'fallback_cb'    => false,
            ) );
        } else {
            $terms = array();
            if ( taxonomy_exists( 'product_cat' ) ) {
                $found = \PowerPlug\Support\Cache::remember( 'pp_hero_cats_' . (int) $cats_count, 6 * HOUR_IN_SECONDS, static function () use ( $cats_count ) { $r = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => true, 'parent' => 0, 'orderby' => 'count', 'order' => 'DESC', 'number' => $cats_count, 'exclude' => array( (int) get_option( 'default_product_cat' ) ) ) ); return is_array( $r ) ? $r : array(); } );
                $found = self::prioritize( is_array( $found ) ? $found : array(), $cats_count );
                if ( is_array( $found ) ) {
                    $terms = $found;
                }
            }
            echo '<ul class="pp-herocats__list">';
            if ( empty( $terms ) ) {
                $fallback = array( 'Power Tools', 'Solar Panels', 'Generators', 'Inverters', 'Batteries', 'Hand Tools', 'Water Pumps', 'CCTV & Security' );
                foreach ( $fallback as $f ) {
                    printf( '<li><a href="%s"><span>%s</span></a></li>', esc_url( (string) $shop ), esc_html( $f ) );
                }
            } else {
                foreach ( $terms as $mt ) {
                    printf( '<li><a href="%s"><span>%s</span><span class="pp-herocats__count">%d</span></a></li>', esc_url( (string) get_term_link( $mt ) ), esc_html( $mt->name ), (int) $mt->count );
                }
            }
            echo '</ul>';
        }
        echo '<a class="pp-herocats__all" href="' . esc_url( (string) $shop ) . '">' . esc_html__( 'View all categories', 'powerplug' ) . '</a></aside>';

        echo '<div class="pp-heroslider" data-pp-slider tabindex="0" role="region" aria-roledescription="carousel" aria-label="' . esc_attr__( 'Promotions', 'powerplug' ) . '">';
        if ( empty( $slides ) ) {
            echo '<div class="pp-heroslider__slide is-on"></div>';
        } else {
            $first = true;
            foreach ( $slides as $sl ) {
                printf( '<div class="pp-heroslider__slide%s" role="group"%s>', $first ? ' is-on' : '', $first ? '' : ' style="background-image:url(' . esc_url( $sl['img'] ) . ')"' );
                if ( $first ) {
                    $pp_dir    = get_template_directory_uri() . '/assets/img/';
                    $pp_srcset = '';
                    foreach ( array( 'slide-1', 'slide-2', 'slide-3' ) as $pp_b ) {
                        if ( $sl['img'] === $pp_dir . $pp_b . '.jpg' ) {
                            $pp_srcset = $pp_dir . $pp_b . '-768.jpg 768w, ' . $pp_dir . $pp_b . '-1024.jpg 1024w, ' . $sl['img'] . ' 1376w';
                        }
                    }
                    if ( strlen( $pp_srcset ) > 0 ) {
                        printf( '<img class="pp-heroslider__img" src="%s" srcset="%s" sizes="%s" width="1376" height="768" alt="" fetchpriority="high" decoding="async" />', esc_url( $sl['img'] ), esc_attr( $pp_srcset ), esc_attr( '(max-width: 900px) 100vw, 66vw' ) );
                    } else {
                        printf( '<img class="pp-heroslider__img" src="%s" width="1376" height="768" alt="" fetchpriority="high" decoding="async" />', esc_url( $sl['img'] ) );
                    }
                }
                if ( strlen( $sl['title'] ) > 0 || strlen( $sl['sub'] ) > 0 || strlen( $sl['btn'] ) > 0 ) {
                    echo '<div class="pp-heroslider__overlay">';
                    if ( strlen( $sl['title'] ) > 0 ) {
                        echo '<h2 class="pp-heroslider__title">' . esc_html( $sl['title'] ) . '</h2>';
                    }
                    if ( strlen( $sl['sub'] ) > 0 ) {
                        echo '<p class="pp-heroslider__sub">' . esc_html( $sl['sub'] ) . '</p>';
                    }
                    if ( strlen( $sl['btn'] ) > 0 ) {
                        echo '<a class="button pp-heroslider__cta" href="' . esc_url( $sl['url'] ) . '">' . esc_html( $sl['btn'] ) . '</a>';
                    }
                    echo '</div>';
                }
                echo '</div>';
                $first = false;
            }
        }
        if ( count( $slides ) > 1 ) {
            echo '<button class="pp-heroslider__arrow pp-heroslider__arrow--prev" type="button" data-pp-slide-prev aria-label="' . esc_attr__( 'Previous slide', 'powerplug' ) . '">&#8249;</button>';
            echo '<button class="pp-heroslider__arrow pp-heroslider__arrow--next" type="button" data-pp-slide-next aria-label="' . esc_attr__( 'Next slide', 'powerplug' ) . '">&#8250;</button>';
            echo '<div class="pp-heroslider__dots" data-pp-slide-dots></div>';
        }
        echo '</div>';

        echo '</div></section>';
    }

    public static function category_sections( int $max_cats = 0 ): void {
        if ( taxonomy_exists( 'product_cat' ) === false ) {
            return;
        }
        if ( $max_cats < 1 ) {
            $max_cats = (int) get_theme_mod( 'pp_section_cats', 6 );
        }
        if ( $max_cats < 1 ) {
            $max_cats = 6;
        }
        $per = (int) get_theme_mod( 'pp_section_products', 6 );
        if ( $per < 1 ) {
            $per = 6;
        }
        $order   = (string) get_theme_mod( 'pp_products_order', 'rand' );
        $allowed = array( 'rand', 'date', 'popularity', 'price' );
        if ( in_array( $order, $allowed, true ) === false ) {
            $order = 'rand';
        }
        $default_cat = (int) get_option( 'default_product_cat' );
        $pp_manual_raw = (string) get_theme_mod( 'pp_home_cats', '' );
        $pp_manual = array_values( array_filter( array_map( 'trim', explode( ',', $pp_manual_raw ) ) ) );
        $row_terms = array();
        if ( count( $pp_manual ) > 0 ) {
            foreach ( $pp_manual as $pp_mslug ) {
                $pp_mt = get_term_by( 'slug', sanitize_title( $pp_mslug ), 'product_cat' );
                if ( ( $pp_mt instanceof \WP_Term ) === false ) {
                    continue;
                }
                if ( (int) $pp_mt->term_id === $default_cat ) {
                    continue;
                }
                $row_terms[] = $pp_mt;
            }
        }
        if ( count( $row_terms ) === 0 ) {
            $row_terms = \PowerPlug\Support\Cache::remember( 'pp_row_cats_' . (int) $max_cats, 6 * HOUR_IN_SECONDS, static function () use ( $max_cats, $default_cat ) { $r = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => true, 'parent' => 0, 'orderby' => 'count', 'order' => 'DESC', 'number' => $max_cats, 'exclude' => array( $default_cat ) ) ); return is_array( $r ) ? $r : array(); } );
            $row_terms = self::prioritize( $row_terms, $max_cats );
        }
        if ( is_wp_error( $row_terms ) || empty( $row_terms ) ) {
            return;
        }
        echo '<div class="pp-shop-full pp-container"><div class="pp-shop-main">';
        foreach ( $row_terms as $t ) {
            $link = (string) get_term_link( $t );
            echo '<section class="pp-cat-block">';
            printf( '<div class="pp-cat-block__head"><h2 class="pp-cat-block__title">%s</h2><a class="pp-viewmore" href="%s">%s</a></div>', esc_html( $t->name ), esc_url( $link ), esc_html__( 'View all', 'powerplug' ) );
if ( 'rand' === $order ) {
	$pp_pool = \PowerPlug\Support\Cache::remember( 'pp_cat_pool_' . (int) $t->term_id, 12 * HOUR_IN_SECONDS, static function () use ( $t ) {
		$r = wc_get_products( array( 'status' => 'publish', 'limit' => 40, 'orderby' => 'date', 'order' => 'DESC', 'category' => array( $t->slug ), 'return' => 'ids' ) );
		return is_array( $r ) ? array_map( 'intval', $r ) : array();
	} );
	if ( count( $pp_pool ) > 0 ) {
		shuffle( $pp_pool );
		$pp_render = min( count( $pp_pool ), 2 * (int) $per );
		$pp_pick = array_slice( $pp_pool, 0, $pp_render );
		printf( '<div class="pp-shuffle" data-pp-shuffle data-pp-show="%d">%s</div>', (int) $per, do_shortcode( sprintf( '[products ids="%s" columns="%d" limit="%d"]', esc_attr( implode( ',', array_map( 'strval', $pp_pick ) ) ), (int) $per, (int) $pp_render ) ) );
	} else {
		echo do_shortcode( sprintf( '[products limit="%d" columns="%d" category="%s" orderby="date"]', $per, $per, esc_attr( $t->slug ) ) );
	}
} else {
	echo do_shortcode( sprintf( '[products limit="%d" columns="%d" category="%s" orderby="%s"]', $per, $per, esc_attr( $t->slug ), esc_attr( $order ) ) );
}
            printf( '<div class="pp-cat-block__more"><a class="button button-ghost" href="%s">%s %s</a></div>', esc_url( $link ), esc_html__( 'View more in', 'powerplug' ), esc_html( $t->name ) );
            echo '</section>';
        }
        echo '</div></div>';
    }

	public static function why_choose_us(): void {
		$points = [
			[ 'Warranty where applicable', 'We source only from reputable manufacturers and distributors.' ],
			[ 'Countrywide delivery', 'Delivery countrywide within 1–5 days.' ],
			[ 'M-Pesa & pay on delivery', 'Pay the way that suits you — securely.' ],
			[ 'Expert support', 'Our team knows these tools inside out and helps you choose right.' ],
		];
		echo '<section class="pp-section pp-section--muted"><div class="pp-container"><h2 class="pp-section__title">' . esc_html__( 'Why choose Power Tools Plug', 'powerplug' ) . '</h2><div class="pp-grid pp-grid--why">';
		foreach ( $points as [ $title, $body ] ) {
			printf( '<div class="pp-why-card"><h3>%s</h3><p>%s</p></div>', esc_html( $title ), esc_html( $body ) );
		}
		echo '</div></div></section>';
	}

	public static function faq(): void {
		$faqs = [
			[ 'Do your products come with a warranty?', 'Warranty terms vary by product. Where a manufacturer warranty applies, it is stated on the product page and honored. Contact us to confirm the warranty for a specific product before you buy.' ],
			[ 'Do you deliver countrywide?', 'Yes. Delivery is KSh 300 within Nairobi and KSh 500 to the rest of Kenya, with Nairobi same/next day and countrywide in 1–5 business days.' ],
			[ 'What payment methods do you accept?', 'M-Pesa, bank transfer and pay-on-delivery where available.' ],
			[ 'Can I return an item?', 'Yes, unused items in original packaging within 7 days. Faulty items are replaced or refunded at our cost.' ],
		];
		echo '<section class="pp-section"><div class="pp-container pp-container--narrow"><h2 class="pp-section__title">' . esc_html__( 'Frequently asked questions', 'powerplug' ) . '</h2>';
		foreach ( $faqs as [ $q, $a ] ) {
			printf( '<details class="pp-faq"><summary>%s</summary><p>%s</p></details>', esc_html( $q ), esc_html( $a ) );
		}
		echo '</div></section>';
		// FAQ JSON-LD
		$ld = [ '@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => [] ];
		foreach ( $faqs as [ $q, $a ] ) {
			$ld['mainEntity'][] = [ '@type' => 'Question', 'name' => $q, 'acceptedAnswer' => [ '@type' => 'Answer', 'text' => $a ] ];
		}
		echo '<script type="application/ld+json">' . wp_json_encode( $ld, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>';
	}

	public static function newsletter(): void {
		echo '<section class="pp-section pp-newsletter"><div class="pp-container pp-container--narrow">';
		echo '<h2 class="pp-section__title">' . esc_html__( 'Get deals & new arrivals', 'powerplug' ) . '</h2>';
		echo '<p>' . esc_html__( 'Join our list for offers on Makita, Bosch, Ingco and Total tools.', 'powerplug' ) . '</p>';
		$html = do_shortcode( '[contact-form-7 id="newsletter" title="Newsletter"]' );
		$has_form = ( strpos( (string) $html, 'wpcf7' ) === false ) ? false : true;
		$has_error = ( strpos( (string) $html, 'not found' ) === false ) ? false : true;
		if ( $has_form === true && $has_error === false ) {
			echo $html;
		} else {
			echo '<div class="pp-newsletter__fallback">';
			echo '<a class="button" href="https://wa.me/' . esc_attr( preg_replace( '/[^0-9]/', '', \PowerPlug\Customizer\Customizer::val( 'pp_whatsapp' ) ) ) . '" rel="noopener">' . esc_html__( 'Message us on WhatsApp', 'powerplug' ) . '</a> ';
			echo '<a class="button button-ghost" href="mailto:info@powertoolsplugke.co.ke?subject=Subscribe">' . esc_html__( 'Email to subscribe', 'powerplug' ) . '</a>';
			echo '</div>';
		}
		echo '</div></section>';
	}

	public static function trust_strip(): void {
		$items = array(
			__( 'Warranty where applicable', 'powerplug' ),
			__( 'Physical shop on Tom Mboya St, Nairobi', 'powerplug' ),
			__( 'Secure payment: M-Pesa or Pay on delivery', 'powerplug' ),
			__( 'Nationwide delivery in 1–5 days', 'powerplug' ),
		);
		echo '<section class="pp-trust-strip" aria-label="' . esc_attr__( 'Why shop with Power Tools Plug', 'powerplug' ) . '"><div class="pp-container pp-trust-strip__grid">';
		foreach ( $items as $it ) {
			echo '<div class="pp-trust-strip__item"><span class="pp-trust-strip__tick" aria-hidden="true">✓</span><span>' . esc_html( $it ) . '</span></div>';
		}
		echo '</div></section>';
	}

	public static function testimonials(): void {
		$reviews = array();
		if ( post_type_exists( 'product' ) === true ) {
			$comments = \PowerPlug\Support\Cache::remember( 'pp_reviews_top6', 6 * HOUR_IN_SECONDS, static function () {
					$r = get_comments( array(
						'post_type'  => 'product',
						'status'     => 'approve',
						'number'     => 6,
						'orderby'    => 'comment_date_gmt',
						'order'      => 'DESC',
						'meta_query' => array(
							array( 'key' => 'rating', 'value' => 4, 'compare' => '>=', 'type' => 'NUMERIC' ),
						),
					) );
					return is_array( $r ) ? $r : array();
				} );
			foreach ( (array) $comments as $c ) {
				$text = trim( (string) $c->comment_content );
				if ( '' === $text ) {
					continue;
				}
				$reviews[] = array(
					'quote'  => $text,
					'author' => (string) $c->comment_author,
					'meta'   => (string) get_the_title( (int) $c->comment_post_ID ),
					'rating' => (int) get_comment_meta( $c->comment_ID, 'rating', true ),
				);
			}
		}
		if ( count( $reviews ) === 0 ) {
			for ( $i = 1; $i <= 6; $i++ ) {
				$quote = trim( (string) get_theme_mod( 'pp_review_' . $i . '_quote', '' ) );
				if ( '' === $quote ) {
					continue;
				}
				$reviews[] = array(
					'quote'  => $quote,
					'author' => (string) get_theme_mod( 'pp_review_' . $i . '_author', '' ),
					'meta'   => (string) get_theme_mod( 'pp_review_' . $i . '_meta', '' ),
					'rating' => (int) get_theme_mod( 'pp_review_' . $i . '_rating', 5 ),
				);
			}
		}
		$pp_gbp_url = \PowerPlug\Customizer\Customizer::val( 'pp_gbp_url' );
		$heading = (string) get_theme_mod( 'pp_reviews_title', __( 'What our customers say', 'powerplug' ) );
		if ( count( $reviews ) === 0 ) {
			if ( strlen( trim( $pp_gbp_url ) ) === 0 ) {
				return;
			}
			echo '<section class="pp-section pp-section--muted pp-reviews"><div class="pp-container"><h2 class="pp-section__title">' . esc_html( $heading ) . '</h2>';
			echo '<p class="pp-reviews__gbp"><a class="button" href="' . esc_url( $pp_gbp_url ) . '" rel="noopener">' . esc_html__( 'Read our reviews on Google', 'powerplug' ) . '</a></p>';
			echo '</div></section>';
			return;
		}
		echo '<section class="pp-section pp-section--muted pp-reviews"><div class="pp-container"><h2 class="pp-section__title">' . esc_html( $heading ) . '</h2><div class="pp-grid pp-grid--reviews">';
		foreach ( $reviews as $r ) {
			$rn = (int) $r['rating'];
			if ( $rn < 1 ) {
				$rn = 5;
			}
			if ( $rn > 5 ) {
				$rn = 5;
			}
			$stars = str_repeat( '★', $rn ) . str_repeat( '☆', 5 - $rn );
			$cite  = trim( (string) $r['author'] );
			if ( '' === trim( (string) $r['meta'] ) ) {
				$cite = $cite;
			} else {
				$cite = trim( $cite . ' — ' . (string) $r['meta'] );
			}
			if ( '' === $cite ) {
				$cite = __( 'Verified customer', 'powerplug' );
			}
			echo '<figure class="pp-review-card">';
			echo '<div class="pp-review-card__stars" aria-label="' . esc_attr( sprintf( __( '%d out of 5 stars', 'powerplug' ), $rn ) ) . '">' . esc_html( $stars ) . '</div>';
			echo '<blockquote class="pp-review-card__quote">' . esc_html( (string) $r['quote'] ) . '</blockquote>';
			echo '<figcaption class="pp-review-card__cite">' . esc_html( $cite ) . '</figcaption>';
			echo '</figure>';
		}
		echo '</div>';
		if ( strlen( trim( $pp_gbp_url ) ) > 0 ) {
			echo '<p class="pp-reviews__gbp"><a class="button button-ghost" href="' . esc_url( $pp_gbp_url ) . '" rel="noopener">' . esc_html__( 'Read more reviews on Google', 'powerplug' ) . '</a></p>';
		}
		echo '</div></section>';
	}
}
