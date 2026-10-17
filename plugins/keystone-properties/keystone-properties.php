<?php
/**
 * Plugin Name:       Keystone Properties
 * Plugin URI:        https://github.com/mouradlhousni/wordpress-real-estate
 * Description:       Business logic for Keystone Realty: properties, agents, leads, REST API, structured data and WP-CLI seeding.
 * Version:           1.0.0
 * Requires at least: 6.5
 * Requires PHP:      8.1
 * Author:            Keystone Realty
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       keystone-properties
 *
 * @package Keystone\Properties
 */

defined( 'ABSPATH' ) || exit;

define( 'KEYSTONE_PROPERTIES_VERSION', '1.0.0' );
define( 'KEYSTONE_PROPERTIES_FILE', __FILE__ );
define( 'KEYSTONE_PROPERTIES_DIR', plugin_dir_path( __FILE__ ) );
define( 'KEYSTONE_PROPERTIES_URL', plugin_dir_url( __FILE__ ) );

/*
 * Minimal PSR-4 autoloader for the Keystone\Properties namespace.
 * Avoids shipping a Composer runtime with the plugin.
 */
spl_autoload_register(
	static function ( $class_name ) {
		$prefix = 'Keystone\\Properties\\';

		if ( 0 !== strpos( $class_name, $prefix ) ) {
			return;
		}

		$relative = substr( $class_name, strlen( $prefix ) );
		$file     = KEYSTONE_PROPERTIES_DIR . 'src/' . str_replace( '\\', '/', $relative ) . '.php';

		if ( is_readable( $file ) ) {
			require $file;
		}
	}
);

require_once KEYSTONE_PROPERTIES_DIR . 'includes/functions.php';

register_activation_hook( __FILE__, array( \Keystone\Properties\Plugin::class, 'activate' ) );
register_deactivation_hook( __FILE__, array( \Keystone\Properties\Plugin::class, 'deactivate' ) );

add_action( 'plugins_loaded', array( \Keystone\Properties\Plugin::class, 'instance' ) );
