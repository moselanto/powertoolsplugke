<?php
declare( strict_types=1 );

namespace PowerPlug\Admin;

use PowerPlug\Core\Bootable;

defined( 'ABSPATH' ) || exit;

/**
 * Tidies the WooCommerce Products admin list.
 *
 * Restricts the list-table to a clean, compact column set and constrains
 * thumbnails, so third-party columns (Rank Math "SEO Details", Brands,
 * catalog-sync, GTIN, tags) can no longer be squeezed to one-character width
 * and blow the row height up into huge empty gaps.
 */
final class ProductList implements Bootable {

	public function boot(): void {
		add_filter( 'manage_edit-product_columns', array( $this, 'columns' ), 9999 );
		add_action( 'admin_head-edit.php', array( $this, 'styles' ) );
	}

	/**
	 * @param array<string,string> $columns
	 * @return array<string,string>
	 */
	public function columns( array $columns ): array {
		$keep = array( 'cb', 'thumb', 'name', 'sku', 'is_in_stock', 'price', 'product_cat', 'featured', 'date' );
		$out  = array();
		foreach ( $keep as $key ) {
			if ( isset( $columns[ $key ] ) ) {
				$out[ $key ] = $columns[ $key ];
			}
		}
		return count( $out ) > 3 ? $out : $columns;
	}

	public function styles(): void {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ( $screen instanceof \WP_Screen ) === false ) {
			return;
		}
		if ( 'product' === $screen->post_type ) {
			echo '<style id="pp-admin-productlist">' . $this->css() . '</style>';
		}
	}

	private function css(): string {
		return implode( '', array(
			'body.post-type-product .wp-list-table .column-thumb{width:52px;text-align:center}',
			'body.post-type-product .wp-list-table .column-thumb img{width:44px;height:44px;object-fit:cover;border-radius:6px;display:inline-block;padding:0;box-shadow:0 0 0 1px rgba(0,0,0,.06)}',
			'body.post-type-product .wp-list-table td,body.post-type-product .wp-list-table th{vertical-align:middle}',
			'body.post-type-product .wp-list-table #the-list td{padding-top:12px;padding-bottom:12px}',
			'body.post-type-product .wp-list-table .column-name{width:26%}',
			'body.post-type-product .wp-list-table .column-name a.row-title{font-weight:600}',
			'body.post-type-product .wp-list-table .column-sku{width:9%}',
			'body.post-type-product .wp-list-table .column-is_in_stock{width:9%}',
			'body.post-type-product .wp-list-table .column-price{width:11%}',
			'body.post-type-product .wp-list-table .column-product_cat{width:16%}',
			'body.post-type-product .wp-list-table .column-featured{width:46px;text-align:center}',
			'body.post-type-product .wp-list-table .column-date{width:14%}',
			'body.post-type-product .wp-list-table .column-rank_math_seo_details,',
			'body.post-type-product .wp-list-table .column-taxonomy-product_brand,',
			'body.post-type-product .wp-list-table .column-product_brand,',
			'body.post-type-product .wp-list-table .column-product_tag,',
			'body.post-type-product .wp-list-table [class*="column-fb_"],',
			'body.post-type-product .wp-list-table [class*="column-facebook"],',
			'body.post-type-product .wp-list-table [class*="column-wc_facebook"],',
			'body.post-type-product .wp-list-table [class*="column-google"],',
			'body.post-type-product .wp-list-table [class*="column-gtin"],',
			'body.post-type-product .wp-list-table [class*="column-global_unique_id"]{display:none}',
		) );
	}
}
