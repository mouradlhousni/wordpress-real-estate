<?php
/**
 * Static export mode.
 *
 * @package Keystone\Properties
 */

namespace Keystone\Properties\Frontend;

defined( 'ABSPATH' ) || exit;

/**
 * When KEYSTONE_STATIC_BUILD is true, strips every reference to dynamic endpoints
 * (REST API, XML-RPC, oEmbed, feeds, admin bar) so the crawled HTML is fully static.
 */
final class StaticBuild {

	/**
	 * Hooks.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		if ( ! keystone_is_static_build() ) {
			return;
		}

		add_action( 'init', array( $this, 'cleanup_head' ) );
		add_filter( 'show_admin_bar', '__return_false' );
		add_filter( 'xmlrpc_enabled', '__return_false' );
		add_filter( 'wp_headers', array( $this, 'remove_headers' ) );
		add_filter( 'wp_resource_hints', array( $this, 'remove_dns_prefetch' ), 10, 2 );
	}

	/**
	 * Removes <head> links to dynamic endpoints.
	 *
	 * @return void
	 */
	public function cleanup_head(): void {
		remove_action( 'wp_head', 'rest_output_link_wp_head', 10 );
		remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
		remove_action( 'wp_head', 'rsd_link' );
		remove_action( 'wp_head', 'feed_links', 2 );
		remove_action( 'wp_head', 'feed_links_extra', 3 );
		remove_action( 'wp_head', 'wp_shortlink_wp_head', 10 );
		remove_action( 'template_redirect', 'rest_output_link_header', 11 );
		remove_action( 'template_redirect', 'wp_shortlink_header', 11 );
	}

	/**
	 * Removes the pingback header.
	 *
	 * @param array<string, string> $headers Headers.
	 * @return array<string, string>
	 */
	public function remove_headers( $headers ): array {
		unset( $headers['X-Pingback'] );

		return (array) $headers;
	}

	/**
	 * Drops the s.w.org emoji DNS prefetch.
	 *
	 * @param array<int, string|array<string, string>> $urls          URLs.
	 * @param string                                   $relation_type Relation.
	 * @return array<int, string|array<string, string>>
	 */
	public function remove_dns_prefetch( $urls, $relation_type ): array {
		return 'dns-prefetch' === $relation_type ? array() : (array) $urls;
	}
}
