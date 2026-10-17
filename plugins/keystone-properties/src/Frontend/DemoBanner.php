<?php
/**
 * "Demo site" banner.
 *
 * @package Keystone\Properties
 */

namespace Keystone\Properties\Frontend;

defined( 'ABSPATH' ) || exit;

/**
 * Discreet banner at the top of every page in demo mode and in the static export.
 */
final class DemoBanner {

	/**
	 * Hooks.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action( 'wp_body_open', array( $this, 'render' ), 5 );
	}

	/**
	 * Outputs the banner.
	 *
	 * @return void
	 */
	public function render(): void {
		if ( keystone_is_static_build() ) {
			printf(
				'<div class="demo-banner" role="note">%1$s <a href="%2$s" rel="noopener" target="_blank">%3$s</a></div>',
				esc_html__( 'Demo site — static showcase.', 'keystone-properties' ),
				esc_url( keystone_playground_url() ),
				esc_html__( 'Try the live WordPress demo in Playground', 'keystone-properties' )
			);
			return;
		}

		if ( keystone_is_demo_mode() ) {
			printf(
				'<div class="demo-banner" role="note">%s</div>',
				esc_html__( 'Demo site — forms are validated, but nothing is stored or sent.', 'keystone-properties' )
			);
		}
	}
}
