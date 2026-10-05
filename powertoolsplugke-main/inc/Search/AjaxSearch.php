<?php
declare( strict_types=1 );

namespace PowerPlug\Search;

use PowerPlug\Core\Bootable;

defined( 'ABSPATH' ) || exit;

/**
 * Secure AJAX product search: title + SKU + category. Nonce-verified, read-only.
 */
final class AjaxSearch implements Bootable {

	public function boot(): void {
		add_action( 'wp_ajax_pp_search', [ $this, 'handle' ] );
		add_action( 'wp_ajax_nopriv_pp_search', [ $this, 'handle' ] );
	}

	public function handle(): void {
		check_ajax_referer( 'powerplug', 'nonce' );

		$term = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['q'] ) ) : '';
		if ( strlen( $term ) < 2 ) {
			wp_send_json_success( [ 'products' => [], 'categories' => [] ] );
		}

		wp_send_json_success( [
			'products'   => $this->products( $term ),
			'categories' => $this->categories( $term ),
		] );
	}

	/**
	 * @return array<int,array<string,string>>
	 */
	private function products( string $term ): array {
		if ( function_exists( 'wc_get_product' ) === false ) {
			return [];
		}

		$ids   = [];
		$query = new \WP_Query( [
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => 6,
			's'              => $term,
			'fields'         => 'ids',
			'no_found_rows'  => true,
		] );
		foreach ( $query->posts as $pid ) {
			$ids[] = (int) $pid;
		}

		global $wpdb;
		$like    = '%' . $wpdb->esc_like( $term ) . '%';
		$sku_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_sku' AND meta_value LIKE %s LIMIT 6",
				$like
			)
		);
		foreach ( (array) $sku_ids as $sid ) {
			$ids[] = (int) $sid;
		}

		$ids = array_slice( array_values( array_unique( $ids ) ), 0, 6 );

		$out = [];
		foreach ( $ids as $id ) {
			$product = wc_get_product( $id );
			if ( ( $product instanceof \WC_Product ) === false ) {
				continue;
			}
			if ( $product->is_visible() === false ) {
				continue;
			}
			$img = wp_get_attachment_image_url( (int) $product->get_image_id(), 'thumbnail' );
			if ( false === $img ) {
				$img = function_exists( 'wc_placeholder_img_src' ) ? wc_placeholder_img_src( 'thumbnail' ) : '';
			}
			$out[] = [
				'title' => $product->get_name(),
				'url'   => (string) get_permalink( $id ),
				'price' => wp_strip_all_tags( $product->get_price_html() ),
				'img'   => (string) $img,
			];
		}
		return $out;
	}

	/**
	 * @return array<int,array<string,string|int>>
	 */
	private function categories( string $term ): array {
		$terms = get_terms( [
			'taxonomy'   => 'product_cat',
			'name__like' => $term,
			'number'     => 4,
			'hide_empty' => true,
		] );
		if ( is_array( $terms ) === false ) {
			return [];
		}
		$out = [];
		foreach ( $terms as $t ) {
			if ( ( $t instanceof \WP_Term ) === false ) {
				continue;
			}
			$link = get_term_link( $t );
			$out[] = [
				'name'  => $t->name,
				'url'   => is_wp_error( $link ) ? '' : (string) $link,
				'count' => (int) $t->count,
			];
		}
		return $out;
	}
}
