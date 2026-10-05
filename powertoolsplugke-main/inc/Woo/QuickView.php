<?php
declare( strict_types=1 );

namespace PowerPlug\Woo;

use PowerPlug\Core\Bootable;
use PowerPlug\Customizer\Customizer;

defined( 'ABSPATH' ) || exit;

/**
 * Loop action button: an editable "Order on WhatsApp" call-to-action on each
 * product card (replaces the old Quick view button). Number + message template
 * come from the Customizer, so nothing is hardcoded. The read-only quick-view
 * AJAX endpoint is retained for optional reuse.
 */
final class QuickView implements Bootable {

	public function boot(): void {
		add_action( 'wp_ajax_pp_quickview', array( $this, 'ajax' ) );
		add_action( 'wp_ajax_nopriv_pp_quickview', array( $this, 'ajax' ) );
		add_action( 'woocommerce_after_shop_loop_item', array( $this, 'button' ), 15 );
	}

	public function button(): void {
		global $product;
		if ( ( $product instanceof \WC_Product ) === false ) {
			return;
		}
		$wa = (string) preg_replace( '/[^0-9]/', '', Customizer::val( 'pp_whatsapp' ) );
		if ( '' === $wa ) {
			return;
		}
		$tmpl = (string) Customizer::val( 'pp_wa_order_msg' );
		if ( '' === trim( $tmpl ) ) {
			$tmpl = 'Hi Power Tools Plug, I would like to order: {product} ({url})';
		}
		$msg = str_replace(
			array( '{product}', '{url}' ),
			array( $product->get_name(), (string) get_permalink( $product->get_id() ) ),
			$tmpl
		);
		printf(
			'<a class="pp-wa-order" href="%s" target="_blank" rel="nofollow noopener"><svg class="pp-wa-order__icon" viewBox="0 0 24 24" aria-hidden="true" fill="currentColor"><path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91c0 1.75.46 3.46 1.33 4.97L2 22l5.25-1.38a9.9 9.9 0 0 0 4.79 1.22h.01c5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.82 9.82 0 0 0 12.04 2Zm0 1.82c2.16 0 4.19.84 5.72 2.37a8.06 8.06 0 0 1 2.37 5.72c0 4.46-3.63 8.09-8.1 8.09a8.1 8.1 0 0 1-4.12-1.13l-.3-.18-3.12.82.83-3.04-.19-.31a8.03 8.03 0 0 1-1.25-4.25c0-4.46 3.63-8.09 8.1-8.09Zm4.68 10.29c-.26-.13-1.51-.75-1.74-.83-.23-.09-.4-.13-.57.13-.17.26-.65.83-.8 1-.15.17-.29.19-.55.06-.26-.13-1.08-.4-2.06-1.27-.76-.68-1.28-1.52-1.43-1.78-.15-.26-.02-.4.11-.53.12-.12.26-.31.39-.46.13-.15.17-.26.26-.44.09-.17.04-.33-.02-.46-.06-.13-.57-1.38-.78-1.89-.21-.5-.42-.43-.57-.44l-.49-.01c-.17 0-.44.06-.68.33-.23.26-.89.87-.89 2.12 0 1.25.91 2.46 1.04 2.63.13.17 1.79 2.74 4.34 3.84.61.26 1.08.42 1.45.54.61.19 1.16.16 1.6.1.49-.07 1.51-.62 1.72-1.21.21-.6.21-1.11.15-1.21-.06-.11-.23-.17-.49-.3Z"/></svg><span>%s</span></a>',
			esc_url( 'https://wa.me/' . $wa . '?text=' . rawurlencode( $msg ) ),
			esc_html__( 'Order on WhatsApp', 'powerplug' )
		);
	}

	public function ajax(): void {
		$id      = isset( $_GET['id'] ) ? absint( wp_unslash( $_GET['id'] ) ) : 0;
		$product = $id ? wc_get_product( $id ) : null;
		if ( ( $product instanceof \WC_Product ) === false ) {
			wp_send_json_error( array( 'message' => __( 'Product not found.', 'powerplug' ) ), 404 );
		}
		$img_id = (int) $product->get_image_id();
		$img    = $img_id ? (string) wp_get_attachment_image_url( $img_id, 'large' ) : (string) wc_placeholder_img_src( 'large' );
		wp_send_json_success( array(
			'id'          => (int) $product->get_id(),
			'title'       => $product->get_name(),
			'price'       => $product->get_price_html(),
			'image'       => $img,
			'excerpt'     => wp_kses_post( (string) $product->get_short_description() ),
			'permalink'   => (string) get_permalink( $product->get_id() ),
			'in_stock'    => $product->is_in_stock(),
			'purchasable' => $product->is_purchasable(),
			'add_url'     => $product->add_to_cart_url(),
			'add_text'    => $product->add_to_cart_text(),
		) );
	}
}
