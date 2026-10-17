<?php
/**
 * Versioned transient cache for heavy property queries.
 *
 * @package Keystone\Properties
 */

namespace Keystone\Properties;

defined( 'ABSPATH' ) || exit;

/**
 * Caches query results in transients whose keys embed a "version" number.
 *
 * Invalidation is O(1): instead of hunting down every transient, any change to a
 * property (or an agent, or a term) bumps the version, so old keys are simply
 * never read again and expire on their own.
 */
final class Cache {

	private const VERSION_OPTION = 'keystone_cache_version';
	private const TTL            = DAY_IN_SECONDS;

	/**
	 * Hooks that invalidate the cache.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action( 'save_post_' . PostTypes::PROPERTY, array( self::class, 'flush' ) );
		add_action( 'save_post_' . PostTypes::AGENT, array( self::class, 'flush' ) );
		add_action( 'deleted_post', array( $this, 'maybe_flush_on_delete' ), 10, 2 );
		add_action( 'trashed_post', array( $this, 'maybe_flush_on_trash' ) );
		add_action( 'set_object_terms', array( $this, 'maybe_flush_on_terms' ), 10, 4 );
		add_action( 'updated_post_meta', array( $this, 'maybe_flush_on_meta' ), 10, 2 );
		add_action( 'added_post_meta', array( $this, 'maybe_flush_on_meta' ), 10, 2 );
		add_action( 'edited_term', array( self::class, 'flush' ) );
	}

	/**
	 * Returns a cached value or computes and stores it.
	 *
	 * @param string   $key      Logical cache key (any string).
	 * @param callable $callback Producer of the value.
	 * @return mixed
	 */
	public static function remember( string $key, callable $callback ) {
		$transient = 'keystone_' . self::version() . '_' . md5( $key );
		$cached    = get_transient( $transient );

		if ( false !== $cached ) {
			return $cached;
		}

		$value = $callback();
		set_transient( $transient, $value, self::TTL );

		return $value;
	}

	/**
	 * Current cache version.
	 *
	 * @return int
	 */
	public static function version(): int {
		return (int) get_option( self::VERSION_OPTION, 1 );
	}

	/**
	 * Invalidates every cached query.
	 *
	 * @return void
	 */
	public static function flush(): void {
		update_option( self::VERSION_OPTION, self::version() + 1, true );
	}

	/**
	 * Flushes when a property or agent is deleted.
	 *
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post    Post object.
	 * @return void
	 */
	public function maybe_flush_on_delete( $post_id, $post ): void {
		if ( $post instanceof \WP_Post && $this->is_tracked( $post->post_type ) ) {
			self::flush();
		}
	}

	/**
	 * Flushes when a property or agent is trashed.
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	public function maybe_flush_on_trash( $post_id ): void {
		if ( $this->is_tracked( (string) get_post_type( $post_id ) ) ) {
			self::flush();
		}
	}

	/**
	 * Flushes when property terms change.
	 *
	 * @param int    $object_id Object ID.
	 * @param array  $terms     Terms.
	 * @param array  $tt_ids    Term taxonomy IDs.
	 * @param string $taxonomy  Taxonomy.
	 * @return void
	 */
	public function maybe_flush_on_terms( $object_id, $terms, $tt_ids, $taxonomy ): void {
		if ( in_array( $taxonomy, array( PostTypes::TAX_TYPE, PostTypes::TAX_CITY, PostTypes::TAX_STATUS ), true ) ) {
			self::flush();
		}
	}

	/**
	 * Flushes when one of our meta keys changes outside of a full save.
	 *
	 * @param int $meta_id   Meta ID.
	 * @param int $object_id Post ID.
	 * @return void
	 */
	public function maybe_flush_on_meta( $meta_id, $object_id ): void {
		if ( $this->is_tracked( (string) get_post_type( $object_id ) ) ) {
			self::flush();
		}
	}

	/**
	 * Whether a post type affects cached queries.
	 *
	 * @param string $post_type Post type.
	 * @return bool
	 */
	private function is_tracked( string $post_type ): bool {
		return in_array( $post_type, array( PostTypes::PROPERTY, PostTypes::AGENT ), true );
	}
}
