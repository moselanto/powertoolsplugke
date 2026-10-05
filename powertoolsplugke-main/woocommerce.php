<?php
/**
 * WooCommerce wrapper. Archives (shop + product taxonomy) get a filter sidebar;
 * single products and other endpoints render full width.
 *
 * @package PowerPlug
 */
defined( 'ABSPATH' ) || exit;

get_header();

$pp_is_archive = ( function_exists( 'is_shop' ) && ( is_shop() || is_product_taxonomy() ) );
?>
<div class="pp-container pp-wc<?php echo $pp_is_archive ? ' pp-wc--shop' : ''; ?>">
	<?php if ( $pp_is_archive && class_exists( '\PowerPlug\Woo\Filters' ) ) : ?>
		<button class="pp-filters-toggle" type="button" data-pp-filters-toggle aria-expanded="false">
			<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 6h16M7 12h10M10 18h4"/></svg>
			<?php esc_html_e( 'Filters', 'powerplug' ); ?>
		</button>
		<aside class="pp-filters" data-pp-filters aria-label="<?php esc_attr_e( 'Product filters', 'powerplug' ); ?>">
			<?php \PowerPlug\Woo\Filters::panel(); ?>
		</aside>
	<?php endif; ?>
	<div class="pp-wc__main">
		<?php woocommerce_content(); ?>
	</div>
</div>
<?php
get_footer();
