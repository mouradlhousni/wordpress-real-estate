<?php
/**
 * Asset registration: versioned, conditional, deferred.
 *
 * @package Keystone
 */

defined( 'ABSPATH' ) || exit;

/**
 * Cache-busting version of a theme file: its modification time, or the theme version.
 *
 * @param string $relative Path relative to the theme root.
 * @return string
 */
function keystone_theme_asset_version( string $relative ): string {
	$file = KEYSTONE_THEME_DIR . '/' . ltrim( $relative, '/' );

	return file_exists( $file ) ? KEYSTONE_THEME_VERSION . '.' . filemtime( $file ) : KEYSTONE_THEME_VERSION;
}

/**
 * Minified variant of an asset (built by `npm run build:assets`), unless SCRIPT_DEBUG is on.
 *
 * @param string $relative Path relative to the theme root, e.g. assets/js/search.js.
 * @return string
 */
function keystone_theme_asset_path( string $relative ): string {
	if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) {
		return $relative;
	}

	$minified = preg_replace( '/\.(css|js)$/', '.min.$1', $relative );

	return file_exists( KEYSTONE_THEME_DIR . '/' . $minified ) ? $minified : $relative;
}

/**
 * Registers a theme script with the `defer` loading strategy.
 *
 * @param string             $handle Handle.
 * @param string             $path   Path relative to the theme root.
 * @param array<int, string> $deps   Dependencies.
 * @return void
 */
function keystone_theme_register_script( string $handle, string $path, array $deps = array() ): void {
	$path = keystone_theme_asset_path( $path );

	wp_register_script(
		$handle,
		KEYSTONE_THEME_URI . '/' . $path,
		$deps,
		keystone_theme_asset_version( $path ),
		array(
			'strategy'  => 'defer',
			'in_footer' => true,
		)
	);
}

/**
 * Enqueues the global stylesheet and the tiny global script.
 *
 * @return void
 */
function keystone_theme_enqueue_assets(): void {
	$style = keystone_theme_asset_path( 'assets/css/main.css' );
	wp_enqueue_style( 'keystone', KEYSTONE_THEME_URI . '/' . $style, array(), keystone_theme_asset_version( $style ) );

	keystone_theme_register_script( 'keystone-theme', 'assets/js/theme.js' );
	wp_enqueue_script( 'keystone-theme' );

	if ( ! keystone_theme_has_plugin() ) {
		return;
	}

	$is_search = keystone_theme_is_search_page();

	// The map script lazy-loads Leaflet itself, only when a map scrolls into view.
	if ( $is_search || is_singular( 'property' ) ) {
		keystone_theme_register_script( 'keystone-map', 'assets/js/map.js' );
		wp_localize_script(
			'keystone-map',
			'keystoneMap',
			array(
				'leafletJs'   => KEYSTONE_THEME_URI . '/assets/vendor/leaflet/leaflet.js?ver=1.9.4',
				'leafletCss'  => KEYSTONE_THEME_URI . '/assets/vendor/leaflet/leaflet.css?ver=1.9.4',
				'tiles'       => 'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
				'attribution' => '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
				'view'        => __( 'View property', 'keystone' ),
			)
		);
		wp_enqueue_script( 'keystone-map' );
	}

	if ( $is_search ) {
		keystone_theme_register_script( 'keystone-search', 'assets/js/search.js' );
		wp_localize_script( 'keystone-search', 'keystoneSearch', keystone_theme_search_config() );
		wp_enqueue_script( 'keystone-search' );
	}

	if ( is_singular( 'property' ) ) {
		keystone_theme_register_script( 'keystone-gallery', 'assets/js/gallery.js' );
		wp_localize_script(
			'keystone-gallery',
			'keystoneGallery',
			array(
				'dialog'   => __( 'Photo gallery', 'keystone' ),
				'close'    => __( 'Close gallery', 'keystone' ),
				'previous' => __( 'Previous photo', 'keystone' ),
				'next'     => __( 'Next photo', 'keystone' ),
				/* translators: 1: current photo number, 2: total photos. */
				'counter'  => __( 'Photo %1$d of %2$d', 'keystone' ),
			)
		);
		wp_enqueue_script( 'keystone-gallery' );
	}
}
add_action( 'wp_enqueue_scripts', 'keystone_theme_enqueue_assets' );

/**
 * Removes core block CSS on templates that render no blocks (property, agent and search pages).
 *
 * @return void
 */
function keystone_theme_dequeue_block_css(): void {
	if ( is_singular( array( 'property', 'agent' ) ) || keystone_theme_is_search_page() || is_post_type_archive( 'agent' ) ) {
		wp_dequeue_style( 'wp-block-library' );
		wp_dequeue_style( 'wp-block-library-theme' );
		wp_dequeue_style( 'global-styles' );
		wp_dequeue_style( 'classic-theme-styles' );
	}
}
add_action( 'wp_enqueue_scripts', 'keystone_theme_dequeue_block_css', 100 );

/**
 * Preloads the two fonts used above the fold.
 *
 * @return void
 */
function keystone_theme_preload_fonts(): void {
	foreach ( array( 'manrope-latin.woff2', 'instrument-serif-latin-italic.woff2' ) as $font ) {
		printf(
			'<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n",
			esc_url( KEYSTONE_THEME_URI . '/assets/fonts/' . $font )
		);
	}
}
add_action( 'wp_head', 'keystone_theme_preload_fonts', 1 );

/**
 * Configuration passed to search.js.
 *
 * @return array<string, mixed>
 */
function keystone_theme_search_config(): array {
	return array(
		'endpoint'    => keystone_is_static_build() ? '' : esc_url_raw( rest_url( 'keystone/v1/properties' ) ),
		// Static export: the same UI filters data/properties.json (resolved from the home link) instead of the REST API.
		'static'      => keystone_is_static_build(),
		'perPage'     => 9,
		'sortDefault' => 'newest',
		'i18n'        => array(
			/* translators: %s: number of properties. */
			'countOne'  => __( '%s property', 'keystone' ),
			/* translators: %s: number of properties. */
			'countMany' => __( '%s properties', 'keystone' ),
			/* translators: %d: number of bedrooms. */
			'bedOne'    => __( '%d bed', 'keystone' ),
			/* translators: %d: number of bedrooms. */
			'bedMany'   => __( '%d beds', 'keystone' ),
			/* translators: %d: number of bathrooms. */
			'bathOne'   => __( '%d bath', 'keystone' ),
			/* translators: %d: number of bathrooms. */
			'bathMany'  => __( '%d baths', 'keystone' ),
			'loading'   => __( 'Loading properties…', 'keystone' ),
			'error'     => __( 'The search is unavailable right now. Please reload the page.', 'keystone' ),
			'pages'     => __( 'Results pages', 'keystone' ),
			'showMap'   => __( 'Show map', 'keystone' ),
			'showList'  => __( 'Show list', 'keystone' ),
			'photos'    => __( 'photos', 'keystone' ),
		),
		'icons'       => array(
			'pin'    => keystone_theme_icon( 'pin' ),
			'bed'    => keystone_theme_icon( 'bed' ),
			'bath'   => keystone_theme_icon( 'bath' ),
			'area'   => keystone_theme_icon( 'area' ),
			'go'     => keystone_theme_icon( 'go' ),
			'camera' => keystone_theme_icon( 'camera' ),
		),
	);
}

/**
 * SVG favicon (the arch mark) when no Site Icon is set in the Customizer.
 *
 * @return void
 */
function keystone_theme_favicon(): void {
	if ( has_site_icon() ) {
		return;
	}

	printf( '<link rel="icon" href="%s" type="image/svg+xml">' . "\n", esc_url( KEYSTONE_THEME_URI . '/assets/images/favicon.svg' ) );
}
add_action( 'wp_head', 'keystone_theme_favicon', 2 );
add_action( 'login_head', 'keystone_theme_favicon' );
add_action( 'admin_head', 'keystone_theme_favicon' );
