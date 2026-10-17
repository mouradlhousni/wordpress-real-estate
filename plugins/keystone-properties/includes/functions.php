<?php
/**
 * Public template API of the plugin.
 *
 * These global, `keystone_`-prefixed functions are the only contract the theme relies on,
 * so the theme never touches plugin internals and degrades gracefully without the plugin.
 *
 * @package Keystone\Properties
 */

defined( 'ABSPATH' ) || exit;

use Keystone\Properties\Frontend\JsonLd;
use Keystone\Properties\Leads\FormHandler;
use Keystone\Properties\PropertyRepository;

/**
 * Whether the site runs in portfolio demo mode (forms validate but never store or send anything).
 *
 * Single source of truth for the KEYSTONE_DEMO_MODE constant.
 *
 * @return bool
 */
function keystone_is_demo_mode(): bool {
	$enabled = defined( 'KEYSTONE_DEMO_MODE' ) && KEYSTONE_DEMO_MODE;

	/**
	 * Filters whether demo mode is active.
	 *
	 * @param bool $enabled Demo mode state.
	 */
	return (bool) apply_filters( 'keystone_is_demo_mode', $enabled );
}

/**
 * Whether the site is being rendered for the static HTML export.
 *
 * @return bool
 */
function keystone_is_static_build(): bool {
	return defined( 'KEYSTONE_STATIC_BUILD' ) && KEYSTONE_STATIC_BUILD;
}

/**
 * URL of the WordPress Playground live demo.
 *
 * @return string
 */
function keystone_playground_url(): string {
	$url = 'https://playground.wordpress.net/?blueprint-url=' . rawurlencode( 'https://raw.githubusercontent.com/mouradlhousni/wordpress-real-estate/master/blueprint.json' );

	/**
	 * Filters the Playground demo URL.
	 *
	 * @param string $url URL.
	 */
	return (string) apply_filters( 'keystone_playground_url', $url );
}

/**
 * Currency settings.
 *
 * @return array{code: string, symbol: string}
 */
function keystone_currency(): array {
	/**
	 * Filters the currency used to display prices.
	 *
	 * @param array{code: string, symbol: string} $currency Currency.
	 */
	return apply_filters(
		'keystone_currency',
		array(
			'code'   => 'EUR',
			'symbol' => '€',
		)
	);
}

/**
 * Formats a price, appending "/ month" for rentals.
 *
 * @param float  $price       Price.
 * @param string $status_slug Status term slug.
 * @return string
 */
function keystone_format_price( float $price, string $status_slug = '' ): string {
	if ( $price <= 0 ) {
		return __( 'Price on request', 'keystone-properties' );
	}

	$currency  = keystone_currency();
	$formatted = $currency['symbol'] . number_format_i18n( $price );

	if ( 'for-rent' === $status_slug ) {
		/* translators: %s: monthly rent. */
		return sprintf( __( '%s / month', 'keystone-properties' ), $formatted );
	}

	return $formatted;
}

/**
 * Normalized property data.
 *
 * @param WP_Post|int|null $post Post, ID or null for the current post.
 * @return array<string, mixed>
 */
function keystone_get_property( $post = null ): array {
	return PropertyRepository::to_array( $post ?? get_post() );
}

/**
 * Normalized agent data.
 *
 * @param WP_Post|int $post Agent post or ID.
 * @return array<string, mixed>|null
 */
function keystone_get_agent( $post ): ?array {
	return PropertyRepository::agent( $post );
}

/**
 * Filtered, paginated, cached property search.
 *
 * @param array<string, mixed> $filters Filters (city, type, status, min_price, max_price, min_beds, sort, page, per_page, featured, exclude).
 * @return array<string, mixed>
 */
function keystone_search_properties( array $filters = array() ): array {
	return PropertyRepository::search( $filters );
}

/**
 * Sort options shown in the UI.
 *
 * @return array<string, string>
 */
function keystone_sort_options(): array {
	return array(
		'newest'     => __( 'Newest first', 'keystone-properties' ),
		'price-asc'  => __( 'Price: low to high', 'keystone-properties' ),
		'price-desc' => __( 'Price: high to low', 'keystone-properties' ),
		'surface'    => __( 'Largest surface', 'keystone-properties' ),
	);
}

/**
 * Renders the "Contact the agent" form for a property.
 *
 * @param int $property_id Property ID.
 * @return void
 */
function keystone_lead_form( int $property_id ): void {
	FormHandler::render( $property_id );
}

/**
 * Cover image ID of a city term.
 *
 * @param int $term_id Term ID.
 * @return int
 */
function keystone_city_image_id( int $term_id ): int {
	return (int) get_term_meta( $term_id, 'keystone_city_image', true );
}

/**
 * Structured data of a property (also printed automatically in <head>).
 *
 * @param int $property_id Property ID.
 * @return array<string, mixed>
 */
function keystone_property_json_ld( int $property_id ): array {
	return JsonLd::build( $property_id );
}
