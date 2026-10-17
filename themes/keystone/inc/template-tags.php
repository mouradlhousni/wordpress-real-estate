<?php
/**
 * Template helpers.
 *
 * @package Keystone
 */

defined( 'ABSPATH' ) || exit;

/**
 * Whether the Keystone Properties plugin (the data layer) is active.
 *
 * @return bool
 */
function keystone_theme_has_plugin(): bool {
	return function_exists( 'keystone_search_properties' );
}

/**
 * Inline SVG icons (decorative: hidden from assistive technology).
 *
 * @param string $name  Icon name.
 * @param string $extra_class Extra class.
 * @return string Safe SVG markup.
 */
function keystone_theme_icon( string $name, string $extra_class = '' ): string {
	$paths = array(
		'bed'      => '<path d="M3 18v-6.5A2.5 2.5 0 0 1 5.5 9h13a2.5 2.5 0 0 1 2.5 2.5V18M3 15h18M3 18v2m18-2v2M6 9V6.5A1.5 1.5 0 0 1 7.5 5h9A1.5 1.5 0 0 1 18 6.5V9"/>',
		'bath'     => '<path d="M4 12h16v2a5 5 0 0 1-5 5H9a5 5 0 0 1-5-5v-2Zm2 7-1 2m13-2 1 2M6 12V5.5A2.5 2.5 0 0 1 8.5 3c1.1 0 2 .7 2.4 1.7"/>',
		'area'     => '<path d="M4 4h6M4 4v6M20 20h-6m6 0v-6M4 4l6 6m10 10-6-6M20 4h-4m4 0v4M4 20h4m-4 0v-4"/>',
		'pin'      => '<path d="M12 21s-7-5.6-7-11a7 7 0 1 1 14 0c0 5.4-7 11-7 11Z"/><circle cx="12" cy="10" r="2.5"/>',
		'phone'    => '<path d="M5 4h3l2 5-2.5 1.5a11 11 0 0 0 6 6L15 14l5 2v3a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2Z"/>',
		'mail'     => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>',
		'arrow'    => '<path d="M5 12h14m-6-6 6 6-6 6"/>',
		'map'      => '<path d="m9 4-6 2v14l6-2 6 2 6-2V4l-6 2-6-2Zm0 0v14m6-12v14"/>',
		'list'     => '<path d="M8 6h13M8 12h13M8 18h13M3.5 6h.01M3.5 12h.01M3.5 18h.01"/>',
		'close'    => '<path d="M6 6l12 12M18 6 6 18"/>',
		'menu'     => '<path d="M4 7h16M4 12h16M4 17h16"/>',
		'chevron'  => '<path d="m9 6 6 6-6 6"/>',
		'calendar' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4m8-4v4"/>',
		'check'    => '<path d="m5 12 5 5L20 7"/>',
		'expand'   => '<path d="M4 9V4h5M20 9V4h-5M4 15v5h5m11-5v5h-5"/>',
		'search'   => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
		'go'       => '<path d="M7 17 17 7M8 7h9v9"/>',
		'camera'   => '<path d="M4 8h3l2-3h6l2 3h3v11H4V8Z"/><circle cx="12" cy="13" r="3.5"/>',
		'sliders'  => '<path d="M4 7h10m4 0h2M4 17h4m4 0h8"/><circle cx="16" cy="7" r="2"/><circle cx="10" cy="17" r="2"/>',
		'home'     => '<path d="M4 11 12 4l8 7v9h-5v-6H9v6H4v-9Z"/>',
		'key'      => '<circle cx="8" cy="15" r="4"/><path d="m11 12 9-9m-4 4 3 3"/>',
		'quote'    => '<path d="M5 18c2.5-1 4-3.4 4-6.5V7H4v5h4m8 6c2.5-1 4-3.4 4-6.5V7h-5v5h4"/>',
	);

	if ( ! isset( $paths[ $name ] ) ) {
		return '';
	}

	return sprintf(
		'<svg class="icon icon--%1$s %2$s" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">%3$s</svg>',
		esc_attr( $name ),
		esc_attr( $extra_class ),
		$paths[ $name ]
	);
}

/**
 * Allowed markup for icons, for wp_kses().
 *
 * @return array<string, array<string, bool>>
 */
function keystone_theme_svg_kses(): array {
	$shape = array(
		'd'      => true,
		'cx'     => true,
		'cy'     => true,
		'r'      => true,
		'x'      => true,
		'y'      => true,
		'width'  => true,
		'height' => true,
		'rx'     => true,
	);

	return array(
		'svg'    => array(
			'class'           => true,
			'viewbox'         => true,
			'width'           => true,
			'height'          => true,
			'fill'            => true,
			'stroke'          => true,
			'stroke-width'    => true,
			'stroke-linecap'  => true,
			'stroke-linejoin' => true,
			'aria-hidden'     => true,
			'focusable'       => true,
		),
		'path'   => $shape,
		'circle' => $shape,
		'rect'   => $shape,
	);
}

/**
 * Echoes an icon.
 *
 * @param string $name  Icon.
 * @param string $extra_class Extra class.
 * @return void
 */
function keystone_theme_the_icon( string $name, string $extra_class = '' ): void {
	echo wp_kses( keystone_theme_icon( $name, $extra_class ), keystone_theme_svg_kses() );
}

/**
 * Echoes a heading that may emphasise words with <em class="serif"> (the editorial accent).
 *
 * @param string $html Translated heading markup.
 * @return void
 */
function keystone_theme_the_heading( string $html ): void {
	echo wp_kses(
		$html,
		array(
			'em' => array( 'class' => true ),
			'br' => array(),
		)
	);
}

/**
 * Current search filters from the URL (and the queried term on taxonomy pages).
 *
 * @return array<string, mixed>
 */
function keystone_theme_request_filters(): array {
	$filters = array();

	// phpcs:disable WordPress.Security.NonceVerification.Recommended -- public, read-only search parameters.
	foreach ( array( 'city', 'type', 'status', 'sort' ) as $key ) {
		if ( ! empty( $_GET[ $key ] ) ) {
			$filters[ $key ] = sanitize_title( wp_unslash( $_GET[ $key ] ) );
		}
	}

	foreach ( array( 'min_price', 'max_price', 'min_beds' ) as $key ) {
		if ( isset( $_GET[ $key ] ) && '' !== $_GET[ $key ] ) {
			$filters[ $key ] = absint( $_GET[ $key ] );
		}
	}

	$filters['page'] = isset( $_GET['pg'] ) ? max( 1, absint( $_GET['pg'] ) ) : 1;
	// phpcs:enable

	$map = array(
		'property_city'   => 'city',
		'property_type'   => 'type',
		'property_status' => 'status',
	);

	$term = get_queried_object();
	if ( $term instanceof WP_Term && isset( $map[ $term->taxonomy ] ) && empty( $filters[ $map[ $term->taxonomy ] ] ) ) {
		$filters[ $map[ $term->taxonomy ] ] = $term->slug;
	}

	$filters['per_page'] = 9;

	return $filters;
}

/**
 * Non-empty terms of a property taxonomy.
 *
 * @param string $taxonomy Taxonomy.
 * @return array<int, WP_Term>
 */
function keystone_theme_terms( string $taxonomy ): array {
	$terms = get_terms(
		array(
			'taxonomy'   => $taxonomy,
			'hide_empty' => true,
			'orderby'    => 'name',
		)
	);

	return is_array( $terms ) ? $terms : array();
}

/**
 * Price presets for the min/max selects.
 *
 * @return array<int, int>
 */
function keystone_theme_price_steps(): array {
	return array( 1500, 3000, 10000, 250000, 500000, 750000, 1000000, 1500000, 2000000, 3000000, 5000000 );
}

/**
 * Compact price label for selects ("€750k", "€1.5M").
 *
 * @param int $value Price.
 * @return string
 */
function keystone_theme_short_price( int $value ): string {
	$symbol = keystone_theme_has_plugin() ? keystone_currency()['symbol'] : '€';

	if ( $value >= 1000000 ) {
		return $symbol . rtrim( rtrim( number_format( $value / 1000000, 1 ), '0' ), '.' ) . 'M';
	}

	if ( $value >= 10000 ) {
		return $symbol . number_format_i18n( $value / 1000 ) . 'k';
	}

	return $symbol . number_format_i18n( $value );
}

/**
 * Renders a property card.
 *
 * @param array<string, mixed> $property Normalized property (keystone_get_property()).
 * @param array<string, mixed> $args     heading_level, eager.
 * @return void
 */
function keystone_theme_property_card( array $property, array $args = array() ): void {
	get_template_part(
		'template-parts/property-card',
		null,
		array_merge(
			array(
				'property'      => $property,
				'heading_level' => 3,
				'eager'         => false,
			),
			$args
		)
	);
}

/**
 * Pagination links for the property search (uses ?pg= so the main query never 404s).
 *
 * @param int                  $current     Current page.
 * @param int                  $total_pages Total pages.
 * @param array<string, mixed> $filters     Active filters.
 * @return void
 */
function keystone_theme_search_pagination( int $current, int $total_pages, array $filters ): void {
	if ( $total_pages < 2 ) {
		return;
	}

	$base  = keystone_theme_is_search_page() ? remove_query_arg( 'pg' ) : get_post_type_archive_link( 'property' );
	$query = array_diff_key( $filters, array_flip( array( 'page', 'per_page' ) ) );

	echo '<nav class="pagination" aria-label="' . esc_attr__( 'Results pages', 'keystone' ) . '"><ul>';
	for ( $i = 1; $i <= $total_pages; $i++ ) {
		$url = add_query_arg( array_merge( $query, array( 'pg' => $i > 1 ? $i : false ) ), $base );
		printf(
			'<li><a href="%1$s" data-page="%2$d"%3$s>%2$d</a></li>',
			esc_url( $url ),
			(int) $i,
			$i === $current ? ' aria-current="page"' : ''
		);
	}
	echo '</ul></nav>';
}

/**
 * URL of the property search page, optionally filtered.
 *
 * @param array<string, mixed> $filters Filters.
 * @return string
 */
function keystone_theme_search_url( array $filters = array() ): string {
	$base = get_post_type_archive_link( 'property' );

	return $filters ? add_query_arg( $filters, $base ) : (string) $base;
}

/**
 * Human label for property counts.
 *
 * @param int $count Count.
 * @return string
 */
function keystone_theme_count_label( int $count ): string {
	/* translators: %s: number of properties. */
	return sprintf( _n( '%s property', '%s properties', $count, 'keystone' ), number_format_i18n( $count ) );
}

/**
 * Shared "Data is missing" notice when the plugin is inactive.
 *
 * @return void
 */
function keystone_theme_missing_plugin_notice(): void {
	echo '<p class="notice-box">' . esc_html__( 'Activate the Keystone Properties plugin to display listings.', 'keystone' ) . '</p>';
}

/**
 * Minimal marker data for the Leaflet map.
 *
 * @param array<int, array<string, mixed>> $items Normalized properties.
 * @return array<int, array<string, mixed>>
 */
function keystone_theme_map_markers( array $items ): array {
	$markers = array();

	foreach ( $items as $item ) {
		if ( empty( $item['latitude'] ) && empty( $item['longitude'] ) ) {
			continue;
		}

		$markers[] = array(
			'id'    => (int) $item['id'],
			'lat'   => (float) $item['latitude'],
			'lng'   => (float) $item['longitude'],
			'title' => (string) $item['title'],
			'price' => (string) $item['price_formatted'],
			'link'  => (string) $item['link'],
			'place' => (string) ( $item['city']['name'] ?? '' ),
			'image' => (string) ( $item['image']['src'] ?? '' ),
		);
	}

	return $markers;
}
