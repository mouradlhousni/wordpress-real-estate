<?php
/**
 * Admin notices.
 *
 * @package Keystone\Properties
 */

namespace Keystone\Properties\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * Reminds administrators that the site runs in demo mode.
 */
final class Notices {

	/**
	 * Hooks.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action( 'admin_notices', array( $this, 'demo_mode' ) );
	}

	/**
	 * Demo mode notice.
	 *
	 * @return void
	 */
	public function demo_mode(): void {
		if ( ! keystone_is_demo_mode() || ! current_user_can( 'edit_posts' ) ) {
			return;
		}

		printf(
			'<div class="notice notice-info"><p><strong>%1$s</strong> %2$s</p></div>',
			esc_html__( 'Demo mode is on.', 'keystone-properties' ),
			esc_html__( 'The public contact form validates submissions but stores no lead and sends no email. Set KEYSTONE_DEMO_MODE to false in wp-config.php to go live.', 'keystone-properties' )
		);
	}
}
