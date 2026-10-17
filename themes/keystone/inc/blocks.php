<?php
/**
 * Custom Gutenberg blocks (sources in src/blocks, compiled to build/blocks by @wordpress/scripts).
 *
 * @package Keystone
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registers every compiled block from its block.json.
 *
 * @return void
 */
function keystone_theme_register_blocks(): void {
	$manifests = glob( KEYSTONE_THEME_DIR . '/build/blocks/*/block.json' );

	foreach ( $manifests ? $manifests : array() as $manifest ) {
		register_block_type( dirname( $manifest ) );
	}
}
add_action( 'init', 'keystone_theme_register_blocks' );
