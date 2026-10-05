<?php
declare( strict_types=1 );

namespace PowerPlug\Setup;

defined( 'ABSPATH' ) || exit;

/**
 * Idempotent demo-content importer.
 *
 * Reads /demo/*.json manifests bundled with the theme and creates the matching
 * categories, products, pages and menus. Safe to re-run: existing items keyed by
 * a _powerplug_demo_key meta are updated in place rather than duplicated.
 */
final class Demo_Importer {

	private string $demo_dir;

	public function __construct() {
		$this->demo_dir = POWERPLUG_DIR . 'demo/';
	}

	/**
	 * @return array<string,mixed>|\WP_Error
	 */
	public function import( string $part ) {
		if ( function_exists( 'set_time_limit' ) ) {
			@set_time_limit( 0 );
		}
		if ( function_exists( 'wp_raise_memory_limit' ) ) {
			wp_raise_memory_limit( 'admin' );
		}
		@ignore_user_abort( true );
		$log = [];
		$map = [
			'categories' => 'import_categories',
			'products'   => 'import_products',
			'pages'      => 'import_pages',
			'menus'      => 'import_menus',
		];

		$parts     = 'all' === $part ? array_keys( $map ) : [ $part ];
		$populated = self::store_is_populated();
		$skip_live = [ 'categories', 'products', 'menus' ];
		foreach ( $parts as $p ) {
			if ( $populated && in_array( $p, $skip_live, true ) ) {
				$log[ $p ] = 'skipped: your store already has products';
				continue;
			}
			if ( isset( $map[ $p ] ) ) {
				$log[ $p ] = $this->{$map[ $p ]}();
			}
		}
		return [ 'imported' => $log, 'store_populated' => $populated ];
	}

	/**
	 * True when the store already has at least one published product. Used to
	 * auto-skip demo categories/products/menus on a live, populated store so the
	 * importer never touches real inventory.
	 */
	public static function store_is_populated(): bool {
		if ( function_exists( 'wc_get_products' ) === false ) {
			return false;
		}
		$ids = wc_get_products( [ 'status' => 'publish', 'limit' => 1, 'return' => 'ids' ] );
		return is_array( $ids ) && count( $ids ) > 0;
	}

	private function read( string $file ): array {
		$path = $this->demo_dir . $file;
		if ( ! is_readable( $path ) ) {
			return [];
		}
		$data = json_decode( (string) file_get_contents( $path ), true );
		return is_array( $data ) ? $data : [];
	}

	/** @return int number of terms created/updated */
	private function import_categories(): int {
		if ( ! taxonomy_exists( 'product_cat' ) ) {
			return 0;
		}
		$count = 0;
		foreach ( $this->read( 'categories.json' ) as $cat ) {
			$name = sanitize_text_field( $cat['name'] ?? '' );
			if ( '' === $name ) {
				continue;
			}
			$existing = term_exists( $name, 'product_cat' );
			if ( ! $existing ) {
				$res = wp_insert_term( $name, 'product_cat', [ 'description' => wp_kses_post( $cat['description'] ?? '' ) ] );
				if ( ! is_wp_error( $res ) ) {
					$count++;
				}
			}
		}
		return $count;
	}

	/** @return int */
	private function import_products(): int {
		if ( ! class_exists( '\WC_Product_Simple' ) ) {
			return 0;
		}
		$count = 0;
		foreach ( $this->read( 'products.json' ) as $row ) {
			$key      = sanitize_key( $row['key'] ?? '' );
			$existing = $key ? $this->find_by_demo_key( $key ) : 0;
			$product  = $existing ? wc_get_product( $existing ) : new \WC_Product_Simple();
			if ( ! $product ) {
				$product = new \WC_Product_Simple();
			}
			$product->set_name( sanitize_text_field( $row['name'] ?? 'Product' ) );
			$product->set_status( 'publish' );
			$product->set_regular_price( (string) ( $row['price'] ?? '' ) );
			$product->set_short_description( wp_kses_post( $row['short'] ?? '' ) );
			$product->set_description( wp_kses_post( $row['description'] ?? '' ) );
			$product->set_sku( sanitize_text_field( $row['sku'] ?? '' ) );
			$product->set_manage_stock( false );
			$product->set_stock_status( 'instock' );
			$id = $product->save();
			if ( $id ) {
				if ( $key ) {
					update_post_meta( $id, '_powerplug_demo_key', $key );
				}
				if ( ! empty( $row['brand'] ) ) {
					update_post_meta( $id, '_powerplug_brand', sanitize_text_field( $row['brand'] ) );
				}
				if ( ! empty( $row['category'] ) ) {
					wp_set_object_terms( $id, sanitize_text_field( $row['category'] ), 'product_cat' );
				}
				$count++;
			}
		}
		return $count;
	}

	private function import_pages(): int {
		$count = 0;
		foreach ( $this->read( 'pages.json' ) as $page ) {
			$title = sanitize_text_field( $page['title'] ?? '' );
			if ( '' === $title || get_page_by_path( sanitize_title( $title ) ) ) {
				continue;
			}
			$id = wp_insert_post( [
				'post_title'   => $title,
				'post_content' => wp_kses_post( $page['content'] ?? '' ),
				'post_status'  => 'publish',
				'post_type'    => 'page',
			] );
			if ( $id && ! is_wp_error( $id ) ) {
				$count++;
			}
		}
		return $count;
	}

	private function import_menus(): int {
		$menu_name = 'PowerPlug Primary';
		$menu      = wp_get_nav_menu_object( $menu_name );
		$menu_id   = $menu ? (int) $menu->term_id : (int) wp_create_nav_menu( $menu_name );
		if ( is_wp_error( $menu_id ) || ! $menu_id ) {
			return 0;
		}
		$locations          = get_theme_mod( 'nav_menu_locations', [] );
		$locations['primary'] = $menu_id;
		set_theme_mod( 'nav_menu_locations', $locations );
		return 1;
	}

	private function find_by_demo_key( string $key ): int {
		$q = new \WP_Query( [
			'post_type'      => 'product',
			'post_status'    => 'any',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_key'       => '_powerplug_demo_key',
			'meta_value'     => $key,
			'no_found_rows'  => true,
		] );
		return $q->have_posts() ? (int) $q->posts[0] : 0;
	}
}
