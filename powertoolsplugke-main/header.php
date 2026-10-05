<?php
/**
 * PowerPlug Pro header: top contact bar, main bar (logo + AJAX search +
 * account/wishlist/cart), primary navigation and mini-cart drawer.
 *
 * @package PowerPlug
 */

defined( 'ABSPATH' ) || exit;

$pp_phone   = \PowerPlug\Customizer\Customizer::val( 'pp_phone' );
$pp_wa      = (string) preg_replace( '/[^0-9]/', '', \PowerPlug\Customizer\Customizer::val( 'pp_whatsapp' ) );
$pp_email   = \PowerPlug\Customizer\Customizer::val( 'pp_email' );
$pp_hours   = \PowerPlug\Customizer\Customizer::val( 'pp_hours' );
$pp_notice  = \PowerPlug\Customizer\Customizer::val( 'pp_topbar_notice' );
$pp_tel     = (string) preg_replace( '/[^0-9+]/', '', $pp_phone );
$pp_account = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : home_url( '/my-account/' );
$pp_action  = home_url( '/' );
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="screen-reader-text" href="#pp-main"><?php esc_html_e( 'Skip to content', 'powerplug' ); ?></a>

<header class="pp-header" role="banner" data-pp-header>

	<div class="pp-topbar">
		<div class="pp-topbar__inner pp-container">
			<?php if ( strlen( $pp_notice ) > 0 ) : ?>
				<p class="pp-topbar__notice"><?php echo esc_html( $pp_notice ); ?></p>
			<?php endif; ?>
			<ul class="pp-topbar__contact">
				<?php if ( strlen( $pp_phone ) > 0 ) : ?>
					<li><a href="tel:<?php echo esc_attr( $pp_tel ); ?>"><?php echo esc_html( $pp_phone ); ?></a></li>
				<?php endif; ?>
				<?php if ( strlen( $pp_wa ) > 0 ) : ?>
					<li><a href="<?php echo esc_url( 'https://wa.me/' . $pp_wa ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'WhatsApp', 'powerplug' ); ?></a></li>
				<?php endif; ?>
				<?php if ( strlen( $pp_email ) > 0 ) : ?>
					<li><a href="<?php echo esc_url( 'mailto:' . $pp_email ); ?>"><?php echo esc_html( $pp_email ); ?></a></li>
				<?php endif; ?>
				<?php if ( strlen( $pp_hours ) > 0 ) : ?>
					<li class="pp-topbar__hours"><?php echo esc_html( $pp_hours ); ?></li>
				<?php endif; ?>
			</ul>
		</div>
	</div>

	<div class="pp-header__sticky">
		<div class="pp-headbar">
			<div class="pp-headbar__inner pp-container">
				<button class="pp-burger" type="button" data-pp-nav-toggle aria-label="<?php esc_attr_e( 'Menu', 'powerplug' ); ?>" aria-controls="pp-primary-menu" aria-expanded="false"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 6h18M3 12h18M3 18h18"/></svg></button>
					<div class="pp-headbar__brand">
					<?php if ( has_custom_logo() ) : the_custom_logo(); else : ?>
						<a class="pp-logo-text" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php bloginfo( 'name' ); ?></a>
					<?php endif; ?>
				</div>

				<form class="pp-search" role="search" method="get" action="<?php echo esc_url( $pp_action ); ?>" data-pp-search>
					<label class="screen-reader-text" for="pp-search-input"><?php esc_html_e( 'Search products', 'powerplug' ); ?></label>
					<input id="pp-search-input" class="pp-search__input" type="search" name="s" value="<?php echo esc_attr( get_search_query() ); ?>" placeholder="<?php esc_attr_e( 'Search products, brands, SKU...', 'powerplug' ); ?>" autocomplete="off" role="combobox" aria-autocomplete="list" aria-expanded="false" aria-controls="pp-search-panel" data-pp-search-input />
					<input type="hidden" name="post_type" value="product" />
					<button type="submit" class="pp-search__btn" aria-label="<?php esc_attr_e( 'Search', 'powerplug' ); ?>"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg></button>
					<div id="pp-search-panel" class="pp-search__panel" role="listbox" data-pp-search-panel hidden></div>
				</form>

				<div class="pp-headbar__actions">
					<a class="pp-action" href="<?php echo esc_url( $pp_account ); ?>" aria-label="<?php esc_attr_e( 'Account', 'powerplug' ); ?>"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg><span><?php esc_html_e( 'Account', 'powerplug' ); ?></span></a>
					<a class="pp-action" href="<?php echo esc_url( home_url( '/wishlist/' ) ); ?>" aria-label="<?php esc_attr_e( 'Wishlist', 'powerplug' ); ?>"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 21S4 14.5 4 8.8A4.2 4.2 0 0 1 12 6a4.2 4.2 0 0 1 8 2.8C20 14.5 12 21 12 21Z"/></svg><span><?php esc_html_e( 'Wishlist', 'powerplug' ); ?></span></a>
					<?php if ( function_exists( 'wc_get_cart_url' ) ) : ?>
						<button class="pp-action pp-action--cart" type="button" data-pp-minicart-open aria-label="<?php esc_attr_e( 'Open cart', 'powerplug' ); ?>"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="9" cy="20" r="1.5"/><circle cx="18" cy="20" r="1.5"/><path d="M2 3h3l2.5 12h11L21 7H6"/></svg><span class="pp-cart__count"><?php echo esc_html( (string) ( ( WC()->cart instanceof \WC_Cart ) ? WC()->cart->get_cart_contents_count() : 0 ) ); ?></span><span><?php esc_html_e( 'Cart', 'powerplug' ); ?></span></button>
					<?php endif; ?>
				</div>
			</div>
		</div>

		<div class="pp-navbar">
			<div class="pp-navbar__inner pp-container">
				<button class="pp-nav-toggle" type="button" data-pp-nav-toggle aria-expanded="false" aria-controls="pp-primary-menu"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 6h18M3 12h18M3 18h18"/></svg><?php esc_html_e( 'Menu', 'powerplug' ); ?></button>
				<nav class="pp-primary" aria-label="<?php esc_attr_e( 'Primary', 'powerplug' ); ?>">
					<div class="pp-primary__head"><span class="pp-primary__headtitle"><?php esc_html_e( 'Menu', 'powerplug' ); ?></span><button type="button" class="pp-primary__close" data-pp-nav-toggle aria-label="<?php esc_attr_e( 'Close menu', 'powerplug' ); ?>">&times;</button></div>
					<?php
					wp_nav_menu(
						array(
							'theme_location' => 'primary',
							'container'      => false,
							'menu_id'        => 'pp-primary-menu',
							'menu_class'     => 'pp-primary__menu',
							'fallback_cb'    => 'powerplug_primary_menu_fallback',
							'depth'          => 2,
						)
					);
					?>
					<div class="pp-drawer-extra">
						<div class="pp-drawer-extra__title"><?php esc_html_e( 'Shop by Category', 'powerplug' ); ?></div>
						<ul class="pp-drawer-cats">
							<?php
							if ( function_exists( 'get_terms' ) && taxonomy_exists( 'product_cat' ) ) {
								$pp_terms = \PowerPlug\Support\Cache::remember( 'pp_drawer_cats_10', 6 * HOUR_IN_SECONDS, static function () { $r = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => true, 'number' => 10, 'orderby' => 'count', 'order' => 'DESC' ) ); return is_array( $r ) ? $r : array(); } );
								if ( is_array( $pp_terms ) ) {
									foreach ( $pp_terms as $pp_t ) {
										$pp_link = get_term_link( $pp_t );
										if ( is_wp_error( $pp_link ) === false ) {
											printf( '<li><a href="%s">%s<span>%d</span></a></li>', esc_url( $pp_link ), esc_html( $pp_t->name ), (int) $pp_t->count );
										}
									}
								}
							}
							?>
						</ul>
						<div class="pp-drawer-extra__links">
							<?php if ( function_exists( 'wc_get_page_permalink' ) ) : ?>
								<a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>"><?php esc_html_e( 'All Products', 'powerplug' ); ?></a>
							<?php endif; ?>
							<a href="<?php echo esc_url( home_url( '/about-us/' ) ); ?>"><?php esc_html_e( 'About Us', 'powerplug' ); ?></a>
							<a href="<?php echo esc_url( home_url( '/contact-us/' ) ); ?>"><?php esc_html_e( 'Contact', 'powerplug' ); ?></a>
						</div>
					</div>
				</nav>
			</div>
		</div>
	</div>
</header>

<?php if ( function_exists( 'wc_get_cart_url' ) ) : ?>
<div class="pp-minicart" data-pp-minicart hidden>
	<div class="pp-minicart__overlay" data-pp-minicart-close></div>
	<aside class="pp-minicart__panel" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Shopping cart', 'powerplug' ); ?>">
		<div class="pp-minicart__head">
			<h2 class="pp-minicart__title"><?php esc_html_e( 'Your Cart', 'powerplug' ); ?></h2>
			<button class="pp-minicart__close" type="button" data-pp-minicart-close aria-label="<?php esc_attr_e( 'Close cart', 'powerplug' ); ?>">&times;</button>
		</div>
		<div class="pp-minicart__body">
			<?php woocommerce_mini_cart(); ?>
		</div>
	</aside>
</div>
<?php endif; ?>

<main id="pp-main" class="pp-main">
