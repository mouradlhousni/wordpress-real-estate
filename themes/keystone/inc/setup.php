<?php
/**
 * Theme setup, menus and front-end hygiene.
 *
 * @package Keystone
 */

defined( 'ABSPATH' ) || exit;

/**
 * Declares theme supports and menus.
 *
 * @return void
 */
function keystone_theme_setup(): void {
	load_theme_textdomain( 'keystone', KEYSTONE_THEME_DIR . '/languages' );

	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'align-wide' );
	add_theme_support( 'editor-styles' );
	add_theme_support(
		'html5',
		array( 'search-form', 'gallery', 'caption', 'style', 'script', 'navigation-widgets' )
	);
	add_theme_support(
		'editor-font-sizes',
		array(
			array(
				'name' => __( 'Small', 'keystone' ),
				'slug' => 'small',
				'size' => 15,
			),
			array(
				'name' => __( 'Regular', 'keystone' ),
				'slug' => 'regular',
				'size' => 18,
			),
			array(
				'name' => __( 'Large', 'keystone' ),
				'slug' => 'large',
				'size' => 24,
			),
		)
	);

	add_editor_style( array( 'assets/css/main.css', 'assets/css/editor.css' ) );

	register_nav_menus(
		array(
			'primary' => __( 'Primary menu', 'keystone' ),
		)
	);
}
add_action( 'after_setup_theme', 'keystone_theme_setup' );

/**
 * Content width for embeds.
 *
 * @return void
 */
function keystone_theme_content_width(): void {
	$GLOBALS['content_width'] = 760;
}
add_action( 'after_setup_theme', 'keystone_theme_content_width', 0 );

/**
 * Removes front-end weight we never use.
 *
 * @return void
 */
function keystone_theme_cleanup(): void {
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_action( 'wp_head', 'wp_generator' );
	remove_action( 'wp_head', 'wlwmanifest_link' );
	remove_action( 'wp_head', 'rsd_link' );
}
add_action( 'init', 'keystone_theme_cleanup' );

/**
 * The property archive and taxonomy pages render their results through the cached plugin search,
 * so the main query only needs to resolve the page (no extra listing query, no found rows).
 *
 * @param WP_Query $query Query.
 * @return void
 */
function keystone_theme_light_main_query( $query ): void {
	if ( is_admin() || ! $query->is_main_query() || ! keystone_theme_is_search_page( $query ) ) {
		return;
	}

	$query->set( 'posts_per_page', 1 );
	$query->set( 'no_found_rows', true );
	$query->set( 'update_post_meta_cache', false );
	$query->set( 'update_post_term_cache', false );
}
add_action( 'pre_get_posts', 'keystone_theme_light_main_query' );

/**
 * Taxonomy pages of properties use the property search template, pre-filtered.
 *
 * @param string $template Template path.
 * @return string
 */
function keystone_theme_taxonomy_template( $template ) {
	if ( is_tax( array( 'property_city', 'property_type', 'property_status' ) ) ) {
		$search = locate_template( 'archive-property.php' );
		return $search ? $search : $template;
	}

	return $template;
}
add_filter( 'template_include', 'keystone_theme_taxonomy_template' );

/**
 * Whether a query is a property search page (archive or property taxonomy).
 *
 * @param WP_Query|null $query Query (defaults to the main query).
 * @return bool
 */
function keystone_theme_is_search_page( $query = null ): bool {
	$query = $query ? $query : $GLOBALS['wp_query'];

	return $query->is_post_type_archive( 'property' ) || $query->is_tax( array( 'property_city', 'property_type', 'property_status' ) );
}

/**
 * Body classes and the static-build marker.
 *
 * @param array<int, string> $classes Classes.
 * @return array<int, string>
 */
function keystone_theme_body_class( $classes ): array {
	if ( keystone_theme_is_search_page() ) {
		$classes[] = 'is-property-search';
	}

	if ( keystone_theme_has_plugin() && keystone_is_demo_mode() ) {
		$classes[] = 'is-demo';
	}

	return $classes;
}
add_filter( 'body_class', 'keystone_theme_body_class' );

/**
 * Shorter, cleaner excerpts.
 *
 * @return int
 */
function keystone_theme_excerpt_length(): int {
	return 24;
}
add_filter( 'excerpt_length', 'keystone_theme_excerpt_length' );

/**
 * Plain ellipsis for excerpts.
 *
 * @return string
 */
function keystone_theme_excerpt_more(): string {
	return '…';
}
add_filter( 'excerpt_more', 'keystone_theme_excerpt_more' );

/**
 * Agents archive: show them all (there are few), in their editorial order.
 *
 * @param WP_Query $query Query.
 * @return void
 */
function keystone_theme_agents_query( $query ): void {
	if ( ! is_admin() && $query->is_main_query() && $query->is_post_type_archive( 'agent' ) ) {
		$query->set( 'posts_per_page', 24 );
		$query->set(
			'orderby',
			array(
				'menu_order' => 'ASC',
				'title'      => 'ASC',
			)
		);
	}
}
add_action( 'pre_get_posts', 'keystone_theme_agents_query' );
