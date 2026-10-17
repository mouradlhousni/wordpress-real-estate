<?php
/**
 * Lightweight SEO meta: description and Open Graph / Twitter cards.
 *
 * Stays out of the way when a dedicated SEO plugin is active.
 *
 * @package Keystone
 */

defined( 'ABSPATH' ) || exit;

/**
 * Whether a dedicated SEO plugin already handles meta tags.
 *
 * @return bool
 */
function keystone_theme_has_seo_plugin(): bool {
	return defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) || defined( 'AIOSEO_VERSION' ) || class_exists( 'The_SEO_Framework\Load' );
}

/**
 * Meta description of the current view.
 *
 * @return string
 */
function keystone_theme_meta_description(): string {
	$description = '';

	if ( is_front_page() ) {
		$description = __( 'Keystone Realty: hand-picked apartments, houses, villas and offices for sale and rent in Lisbon, Barcelona, Paris, Nice and Marbella.', 'keystone' );
	} elseif ( is_singular() ) {
		$post        = get_queried_object();
		$description = has_excerpt( $post ) ? get_the_excerpt( $post ) : wp_trim_words( wp_strip_all_tags( strip_shortcodes( $post->post_content ) ), 30, '…' );

		if ( is_singular( 'property' ) && keystone_theme_has_plugin() ) {
			$property    = keystone_get_property( $post );
			$description = trim( $property['price_formatted'] . ' · ' . $description );
		}
	} elseif ( is_tax() || is_category() || is_tag() ) {
		$term        = get_queried_object();
		$description = $term->description ? $term->description : sprintf(
			/* translators: %s: term name. */
			__( 'Browse properties in %s with Keystone Realty.', 'keystone' ),
			$term->name
		);
	} elseif ( is_post_type_archive( 'property' ) ) {
		$description = __( 'Search apartments, houses, villas and offices by city, price and number of bedrooms.', 'keystone' );
	} elseif ( is_post_type_archive( 'agent' ) ) {
		$description = __( 'Meet the Keystone Realty agents in Lisbon, Barcelona, Paris, Nice and Marbella.', 'keystone' );
	}

	if ( ! $description ) {
		$description = (string) get_bloginfo( 'description' );
	}

	return wp_html_excerpt( wp_strip_all_tags( $description ), 160, '…' );
}

/**
 * Prints description, canonical-friendly Open Graph and Twitter tags.
 *
 * @return void
 */
function keystone_theme_print_meta(): void {
	if ( keystone_theme_has_seo_plugin() || is_404() ) {
		return;
	}

	$description = keystone_theme_meta_description();
	$title       = wp_get_document_title();
	$url         = home_url( '/' );
	$image       = '';

	if ( is_singular() ) {
		$url = (string) get_permalink();
	} elseif ( is_tax() || is_category() || is_tag() ) {
		$link = get_term_link( get_queried_object() );
		$url  = is_wp_error( $link ) ? $url : $link;
	} elseif ( is_post_type_archive() ) {
		$url = (string) get_post_type_archive_link( (string) get_query_var( 'post_type' ) );
	}

	if ( is_singular() && has_post_thumbnail() ) {
		$image = (string) wp_get_attachment_image_url( (int) get_post_thumbnail_id(), 'large' );
	}

	$tags = array(
		'description'    => $description,
		'og:type'        => is_singular( array( 'property', 'post' ) ) ? 'article' : 'website',
		'og:site_name'   => get_bloginfo( 'name' ),
		'og:title'       => $title,
		'og:description' => $description,
		'og:url'         => $url,
		'og:image'       => $image,
		'twitter:card'   => $image ? 'summary_large_image' : 'summary',
	);

	foreach ( array_filter( $tags ) as $name => $content ) {
		$attribute = str_starts_with( $name, 'og:' ) ? 'property' : 'name';
		printf( '<meta %1$s="%2$s" content="%3$s">' . "\n", esc_attr( $attribute ), esc_attr( $name ), esc_attr( $content ) );
	}
}
add_action( 'wp_head', 'keystone_theme_print_meta', 5 );
