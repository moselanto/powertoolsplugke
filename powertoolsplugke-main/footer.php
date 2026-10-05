<?php
/**
 * @package PowerPlug
 */
defined( 'ABSPATH' ) || exit;
$pp_year     = (string) gmdate( 'Y' );
$pp_phone    = \PowerPlug\Customizer\Customizer::val( 'pp_phone' );
$pp_email    = \PowerPlug\Customizer\Customizer::val( 'pp_email' );
$pp_hours    = \PowerPlug\Customizer\Customizer::val( 'pp_hours' );
$pp_address  = \PowerPlug\Customizer\Customizer::val( 'pp_address' );
$pp_whatsapp = \PowerPlug\Customizer\Customizer::val( 'pp_whatsapp' );
$pp_tel      = preg_replace( '/[^0-9+]/', '', $pp_phone );
$pp_brands   = trim( (string) get_theme_mod( 'pp_brands', 'Total, Ingco, Maxmech, DCA' ) );
if ( '' === $pp_brands ) { $pp_brands = 'Total, Ingco, Maxmech, DCA'; }
?>
</main>
<div class="pp-trustbar"><div class="pp-container"><?php echo do_shortcode( '[pp_trust_badges]' ); ?></div></div>
<footer class="pp-footer" role="contentinfo">
	<div class="pp-footer__cols">
		<div class="pp-footer__col pp-footer__about">
			<?php the_custom_logo(); ?>
			<p><?php printf( esc_html( __( 'Your trusted power-tools shop in Nairobi. %s and more, for professionals and home projects. Pay by M-Pesa or on delivery.', 'powerplug' ) ), esc_html( $pp_brands ) ); ?></p>
			<p class="pp-footer__social">
				<?php if ( strlen( $pp_whatsapp ) > 0 ) : ?><a href="https://wa.me/<?php echo esc_attr( $pp_whatsapp ); ?>" rel="noopener">WhatsApp</a> &middot;<?php endif; ?>
				<a href="https://www.facebook.com/" rel="noopener">Facebook</a> &middot;
				<a href="https://www.instagram.com/" rel="noopener">Instagram</a> &middot;
				<a href="https://www.tiktok.com/" rel="noopener">TikTok</a>
			</p>
		</div>
		<div class="pp-footer__col">
			<h3 class="pp-footer__title"><?php esc_html_e( 'Shop', 'powerplug' ); ?></h3>
			<ul>
				<?php
				$pp_shop_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' );
				$pp_footer_cats = array();
				if ( function_exists( 'get_terms' ) && taxonomy_exists( 'product_cat' ) ) {
					$pp_footer_cats = \PowerPlug\Support\Cache::remember( 'pp_footer_cats_5', 6 * HOUR_IN_SECONDS, static function () {
						$r = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => true, 'number' => 5, 'orderby' => 'count', 'order' => 'DESC', 'exclude' => array( (int) get_option( 'default_product_cat' ) ) ) );
						return is_array( $r ) ? $r : array();
					} );
				}
				foreach ( $pp_footer_cats as $pp_fcat ) {
					$pp_fcat_link = get_term_link( $pp_fcat );
					if ( is_wp_error( $pp_fcat_link ) === false ) {
						printf( '<li><a href="%s">%s</a></li>', esc_url( (string) $pp_fcat_link ), esc_html( $pp_fcat->name ) );
					}
				}
				printf( '<li><a href="%s">%s</a></li>', esc_url( (string) $pp_shop_url ), esc_html__( 'All Products', 'powerplug' ) );
				?>
			</ul>
		</div>
		<div class="pp-footer__col">
			<h3 class="pp-footer__title"><?php esc_html_e( 'Customer Care', 'powerplug' ); ?></h3>
			<?php if ( has_nav_menu( 'footer' ) ) : ?>
				<?php wp_nav_menu( array( 'theme_location' => 'footer', 'container' => false, 'menu_class' => 'pp-footer__menu', 'fallback_cb' => false, 'depth' => 1 ) ); ?>
			<?php else : ?>
				<ul class="pp-footer__menu">
					<li><a href="<?php echo esc_url( home_url( '/about-us/' ) ); ?>"><?php esc_html_e( 'About Us', 'powerplug' ); ?></a></li>
					<li><a href="<?php echo esc_url( home_url( '/return-refund-policy/' ) ); ?>"><?php esc_html_e( 'Return and Refund Policy', 'powerplug' ); ?></a></li>
					<li><a href="<?php echo esc_url( home_url( '/shipping-delivery-policy/' ) ); ?>"><?php esc_html_e( 'Shipping and Delivery Policy', 'powerplug' ); ?></a></li>
					<li><a href="<?php echo esc_url( home_url( '/terms-conditions/' ) ); ?>"><?php esc_html_e( 'Terms and Conditions', 'powerplug' ); ?></a></li>
					<li><a href="<?php echo esc_url( home_url( '/privacy-policy/' ) ); ?>"><?php esc_html_e( 'Privacy Policy', 'powerplug' ); ?></a></li>
					<li><a href="<?php echo esc_url( home_url( '/contact-us/' ) ); ?>"><?php esc_html_e( 'Contact Us', 'powerplug' ); ?></a></li>
					<li><a href="<?php echo esc_url( home_url( '/faq/' ) ); ?>"><?php esc_html_e( 'FAQ', 'powerplug' ); ?></a></li>
					<li><a href="<?php echo esc_url( home_url( '/track-order/' ) ); ?>"><?php esc_html_e( 'Track Order', 'powerplug' ); ?></a></li>
				</ul>
			<?php endif; ?>
		</div>
		<div class="pp-footer__col">
			<h3 class="pp-footer__title"><?php esc_html_e( 'Get in Touch', 'powerplug' ); ?></h3>
			<ul class="pp-footer__contact">
				<?php if ( strlen( $pp_address ) > 0 ) : ?><li><?php echo esc_html( $pp_address ); ?></li><?php endif; ?>
				<?php if ( strlen( $pp_phone ) > 0 ) : ?><li><a href="tel:<?php echo esc_attr( $pp_tel ); ?>"><?php echo esc_html( $pp_phone ); ?></a></li><?php endif; ?>
				<?php if ( strlen( $pp_email ) > 0 ) : ?><li><a href="mailto:<?php echo esc_attr( $pp_email ); ?>"><?php echo esc_html( $pp_email ); ?></a></li><?php endif; ?>
				<?php if ( strlen( $pp_hours ) > 0 ) : ?><li><?php echo esc_html( $pp_hours ); ?></li><?php endif; ?>
				<li><?php esc_html_e( 'We accept: M-Pesa and Pay on Delivery', 'powerplug' ); ?></li>
			</ul>
		</div>
	</div>
	<div class="pp-footer__trust">
		<span class="pp-footer__trust-label"><?php esc_html_e( 'How you pay', 'powerplug' ); ?></span>
		<span class="pp-pay">M-Pesa</span>
		<span class="pp-pay">Pay on Delivery</span>
		<span class="pp-footer__trust-secure"><?php esc_html_e( 'SSL secured checkout', 'powerplug' ); ?></span>
	</div>
	<div class="pp-footer__legal">
		<p>&copy; <?php echo esc_html( $pp_year ); ?> <?php bloginfo( 'name' ); ?>. <?php esc_html_e( 'All rights reserved. All prices in Kenyan Shillings (KES).', 'powerplug' ); ?>
		<a href="<?php echo esc_url( home_url( '/return-refund-policy/' ) ); ?>"><?php esc_html_e( 'Returns', 'powerplug' ); ?></a> &middot;
		<a href="<?php echo esc_url( home_url( '/shipping-delivery-policy/' ) ); ?>"><?php esc_html_e( 'Shipping', 'powerplug' ); ?></a> &middot;
		<a href="<?php echo esc_url( home_url( '/privacy-policy/' ) ); ?>"><?php esc_html_e( 'Privacy', 'powerplug' ); ?></a> &middot;
		<a href="<?php echo esc_url( home_url( '/terms-conditions/' ) ); ?>"><?php esc_html_e( 'Terms', 'powerplug' ); ?></a></p>
	</div>
</footer>
<?php if ( strlen( $pp_whatsapp ) > 0 ) : ?>
<a class="pp-whatsapp" href="https://wa.me/<?php echo esc_attr( $pp_whatsapp ); ?>" aria-label="<?php esc_attr_e( 'Chat on WhatsApp', 'powerplug' ); ?>" rel="noopener"><svg class="pp-whatsapp__icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false" fill="currentColor"><path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91c0 1.75.46 3.46 1.33 4.97L2 22l5.25-1.38a9.9 9.9 0 0 0 4.79 1.22h.01c5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.82 9.82 0 0 0 12.04 2Zm0 1.82c2.16 0 4.19.84 5.72 2.37a8.06 8.06 0 0 1 2.37 5.72c0 4.46-3.63 8.09-8.1 8.09a8.1 8.1 0 0 1-4.12-1.13l-.3-.18-3.12.82.83-3.04-.19-.31a8.03 8.03 0 0 1-1.25-4.25c0-4.46 3.63-8.09 8.1-8.09Zm4.68 10.29c-.26-.13-1.51-.75-1.74-.83-.23-.09-.4-.13-.57.13-.17.26-.65.83-.8 1-.15.17-.29.19-.55.06-.26-.13-1.08-.4-2.06-1.27-.76-.68-1.28-1.52-1.43-1.78-.15-.26-.02-.4.11-.53.12-.12.26-.31.39-.46.13-.15.17-.26.26-.44.09-.17.04-.33-.02-.46-.06-.13-.57-1.38-.78-1.89-.21-.5-.42-.43-.57-.44l-.49-.01c-.17 0-.44.06-.68.33-.23.26-.89.87-.89 2.12 0 1.25.91 2.46 1.04 2.63.13.17 1.79 2.74 4.34 3.84.61.26 1.08.42 1.45.54.61.19 1.16.16 1.6.1.49-.07 1.51-.62 1.72-1.21.21-.6.21-1.11.15-1.21-.06-.11-.23-.17-.49-.3Z"/></svg></a>
<?php endif; ?>
<a class="pp-backtotop" href="#pp-main" aria-label="<?php esc_attr_e( 'Back to top', 'powerplug' ); ?>">&uarr;</a>
<?php wp_footer(); ?>
</body>
</html>
