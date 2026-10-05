<?php
declare( strict_types=1 );

namespace PowerPlug\Support;

defined( 'ABSPATH' ) || exit;

/**
 * Lightweight version-keyed transient cache for homepage catalogue queries.
 *
 * Every entry is stored under a key that embeds a global version integer.
 * Bumping the version (when a product, category or review changes) instantly
 * invalidates all cached entries without tracking individual keys. Safe on
 * hosts with no persistent object cache: entries fall back to the options
 * table via the Transients API and expire on TTL.
 */
final class Cache {

	const VER_OPTION = 'pp_cache_ver';
	const PREFIX     = 'pp_c_';

	public static function version(): int {
		$v = (int) get_option( self::VER_OPTION, 1 );
		if ( $v < 1 ) {
			$v = 1;
		}
		return $v;
	}

	public static function flush(): void {
		update_option( self::VER_OPTION, self::version() + 1, false );
	}

	/**
	 * Return a cached value for $key, computing and storing it on a miss.
	 *
	 * @param string   $key Stable identifier for the payload.
	 * @param int      $ttl Seconds to keep the entry.
	 * @param callable $cb  Producer invoked on a miss; return value is cached.
	 * @return mixed
	 */
	public static function remember( string $key, int $ttl, callable $cb ) {
		$name   = self::PREFIX . self::version() . '_' . md5( $key );
		$cached = get_transient( $name );
		if ( false === $cached ) {
			$value = $cb();
			set_transient( $name, $value, $ttl );
			return $value;
		}
		return $cached;
	}
}
