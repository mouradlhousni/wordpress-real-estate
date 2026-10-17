<?php
/**
 * Plugin bootstrap.
 *
 * @package Keystone\Properties
 */

namespace Keystone\Properties;

defined( 'ABSPATH' ) || exit;

/**
 * Wires every module of the plugin together.
 */
final class Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var Plugin|null
	 */
	private static ?Plugin $instance = null;

	/**
	 * Returns (and boots on first call) the plugin instance.
	 *
	 * @return Plugin
	 */
	public static function instance(): Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
			self::$instance->boot();
		}

		return self::$instance;
	}

	/**
	 * Registers the hooks of every module.
	 *
	 * @return void
	 */
	private function boot(): void {
		add_action( 'init', array( $this, 'load_textdomain' ) );
		add_action( 'after_setup_theme', array( $this, 'register_image_sizes' ) );
		add_filter( 'image_editor_output_format', array( $this, 'webp_subsizes' ) );

		( new PostTypes() )->register_hooks();
		( new Meta() )->register_hooks();
		( new Cache() )->register_hooks();
		( new Rest\PropertiesController() )->register_hooks();
		( new Rest\LeadsController() )->register_hooks();
		( new Leads\FormHandler() )->register_hooks();
		( new Frontend\JsonLd() )->register_hooks();
		( new Frontend\DemoBanner() )->register_hooks();
		( new Frontend\StaticBuild() )->register_hooks();

		if ( is_admin() ) {
			( new Admin\MetaBoxes() )->register_hooks();
			( new Admin\Columns() )->register_hooks();
			( new Admin\Notices() )->register_hooks();
		}

		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			\WP_CLI::add_command( 'keystone', Cli\Command::class );
		}
	}

	/**
	 * Loads translations.
	 *
	 * @return void
	 */
	public function load_textdomain(): void {
		load_plugin_textdomain( 'keystone-properties', false, dirname( plugin_basename( KEYSTONE_PROPERTIES_FILE ) ) . '/languages' );
	}

	/**
	 * Image sizes used by the normalized data (cards, agent portraits, gallery).
	 *
	 * @return void
	 */
	public function register_image_sizes(): void {
		add_image_size( 'keystone-card', 720, 480, true );
		add_image_size( 'keystone-portrait', 480, 600, true );
	}

	/**
	 * Generates WebP sub-sizes for JPEG uploads (originals are kept as uploaded).
	 *
	 * Roughly 30% lighter listing images. Disable with `add_filter( 'keystone_webp_subsizes', '__return_false' );`.
	 *
	 * @param array<string, string> $formats Source MIME => output MIME.
	 * @return array<string, string>
	 */
	public function webp_subsizes( $formats ): array {
		$formats = (array) $formats;

		/**
		 * Filters whether JPEG sub-sizes are generated as WebP.
		 *
		 * @param bool $enabled Default true when the image editor supports WebP.
		 */
		if ( apply_filters( 'keystone_webp_subsizes', wp_image_editor_supports( array( 'mime_type' => 'image/webp' ) ) ) ) {
			$formats['image/jpeg'] = 'image/webp';
		}

		return $formats;
	}

	/**
	 * Activation: register content types so rewrite rules include them, then flush.
	 *
	 * @return void
	 */
	public static function activate(): void {
		$post_types = new PostTypes();
		$post_types->register_post_types();
		$post_types->register_taxonomies();
		PostTypes::ensure_default_terms();
		flush_rewrite_rules();
	}

	/**
	 * Deactivation: drop our rewrite rules.
	 *
	 * @return void
	 */
	public static function deactivate(): void {
		flush_rewrite_rules();
	}
}
