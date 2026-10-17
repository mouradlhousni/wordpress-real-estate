<?php
/**
 * Custom post types and taxonomies.
 *
 * @package Keystone\Properties
 */

namespace Keystone\Properties;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the `property`, `agent` and `lead` post types and the property taxonomies.
 */
final class PostTypes {

	public const PROPERTY = 'property';
	public const AGENT    = 'agent';
	public const LEAD     = 'lead';

	public const TAX_TYPE   = 'property_type';
	public const TAX_CITY   = 'property_city';
	public const TAX_STATUS = 'property_status';

	/**
	 * Default terms created on activation (slug => label).
	 */
	public const DEFAULT_TERMS = array(
		self::TAX_TYPE   => array(
			'apartment' => 'Apartment',
			'house'     => 'House',
			'villa'     => 'Villa',
			'office'    => 'Office',
		),
		self::TAX_STATUS => array(
			'for-sale' => 'For Sale',
			'for-rent' => 'For Rent',
			'sold'     => 'Sold',
		),
	);

	/**
	 * Hooks.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action( 'init', array( $this, 'register_post_types' ), 5 );
		add_action( 'init', array( $this, 'register_taxonomies' ), 5 );
		add_filter( 'enter_title_here', array( $this, 'title_placeholder' ), 10, 2 );
	}

	/**
	 * Registers the post types.
	 *
	 * @return void
	 */
	public function register_post_types(): void {
		register_post_type(
			self::PROPERTY,
			array(
				'labels'        => array(
					'name'               => __( 'Properties', 'keystone-properties' ),
					'singular_name'      => __( 'Property', 'keystone-properties' ),
					'add_new_item'       => __( 'Add New Property', 'keystone-properties' ),
					'edit_item'          => __( 'Edit Property', 'keystone-properties' ),
					'new_item'           => __( 'New Property', 'keystone-properties' ),
					'view_item'          => __( 'View Property', 'keystone-properties' ),
					'search_items'       => __( 'Search Properties', 'keystone-properties' ),
					'not_found'          => __( 'No properties found.', 'keystone-properties' ),
					'not_found_in_trash' => __( 'No properties found in Trash.', 'keystone-properties' ),
					'all_items'          => __( 'All Properties', 'keystone-properties' ),
					'menu_name'          => __( 'Properties', 'keystone-properties' ),
				),
				'public'        => true,
				'has_archive'   => 'properties',
				'rewrite'       => array(
					'slug'       => 'property',
					'with_front' => false,
				),
				'menu_icon'     => 'dashicons-building',
				'menu_position' => 20,
				'show_in_rest'  => true,
				'supports'      => array( 'title', 'editor', 'excerpt', 'thumbnail', 'custom-fields', 'revisions' ),
				'taxonomies'    => array( self::TAX_TYPE, self::TAX_CITY, self::TAX_STATUS ),
			)
		);

		register_post_type(
			self::AGENT,
			array(
				'labels'        => array(
					'name'               => __( 'Agents', 'keystone-properties' ),
					'singular_name'      => __( 'Agent', 'keystone-properties' ),
					'add_new_item'       => __( 'Add New Agent', 'keystone-properties' ),
					'edit_item'          => __( 'Edit Agent', 'keystone-properties' ),
					'view_item'          => __( 'View Agent', 'keystone-properties' ),
					'search_items'       => __( 'Search Agents', 'keystone-properties' ),
					'not_found'          => __( 'No agents found.', 'keystone-properties' ),
					'featured_image'     => __( 'Agent photo', 'keystone-properties' ),
					'set_featured_image' => __( 'Set agent photo', 'keystone-properties' ),
					'all_items'          => __( 'All Agents', 'keystone-properties' ),
				),
				'public'        => true,
				'has_archive'   => 'agents',
				'rewrite'       => array(
					'slug'       => 'agent',
					'with_front' => false,
				),
				'menu_icon'     => 'dashicons-businessperson',
				'menu_position' => 21,
				'show_in_rest'  => true,
				'supports'      => array( 'title', 'editor', 'thumbnail', 'custom-fields' ),
			)
		);

		/*
		 * Leads hold personal data: never public, never in REST, admin-only.
		 * They are created programmatically by the contact form only.
		 */
		register_post_type(
			self::LEAD,
			array(
				'labels'              => array(
					'name'          => __( 'Leads', 'keystone-properties' ),
					'singular_name' => __( 'Lead', 'keystone-properties' ),
					'edit_item'     => __( 'Lead details', 'keystone-properties' ),
					'search_items'  => __( 'Search Leads', 'keystone-properties' ),
					'not_found'     => __( 'No leads yet.', 'keystone-properties' ),
					'all_items'     => __( 'Leads', 'keystone-properties' ),
				),
				'public'              => false,
				'publicly_queryable'  => false,
				'exclude_from_search' => true,
				'show_ui'             => true,
				'show_in_menu'        => 'edit.php?post_type=' . self::PROPERTY,
				'show_in_rest'        => false,
				'show_in_nav_menus'   => false,
				'rewrite'             => false,
				'query_var'           => false,
				'supports'            => array( 'title' ),
				'map_meta_cap'        => true,
				'capability_type'     => 'post',
				'capabilities'        => array(
					'create_posts'           => 'do_not_allow',
					'edit_posts'             => 'manage_options',
					'edit_others_posts'      => 'manage_options',
					'edit_private_posts'     => 'manage_options',
					'edit_published_posts'   => 'manage_options',
					'publish_posts'          => 'manage_options',
					'read_private_posts'     => 'manage_options',
					'delete_posts'           => 'manage_options',
					'delete_others_posts'    => 'manage_options',
					'delete_private_posts'   => 'manage_options',
					'delete_published_posts' => 'manage_options',
				),
			)
		);
	}

	/**
	 * Registers the property taxonomies.
	 *
	 * @return void
	 */
	public function register_taxonomies(): void {
		$shared = array(
			'public'            => true,
			'show_in_rest'      => true,
			'show_admin_column' => false,
			'hierarchical'      => true,
		);

		register_taxonomy(
			self::TAX_TYPE,
			self::PROPERTY,
			array_merge(
				$shared,
				array(
					'labels'  => $this->taxonomy_labels( __( 'Property Types', 'keystone-properties' ), __( 'Property Type', 'keystone-properties' ) ),
					'rewrite' => array(
						'slug'       => 'type',
						'with_front' => false,
					),
				)
			)
		);

		register_taxonomy(
			self::TAX_CITY,
			self::PROPERTY,
			array_merge(
				$shared,
				array(
					'labels'  => $this->taxonomy_labels( __( 'Cities', 'keystone-properties' ), __( 'City', 'keystone-properties' ) ),
					'rewrite' => array(
						'slug'       => 'city',
						'with_front' => false,
					),
				)
			)
		);

		register_taxonomy(
			self::TAX_STATUS,
			self::PROPERTY,
			array_merge(
				$shared,
				array(
					'labels'  => $this->taxonomy_labels( __( 'Statuses', 'keystone-properties' ), __( 'Status', 'keystone-properties' ) ),
					'rewrite' => array(
						'slug'       => 'status',
						'with_front' => false,
					),
				)
			)
		);
	}

	/**
	 * Creates the default type and status terms if they are missing.
	 *
	 * @return void
	 */
	public static function ensure_default_terms(): void {
		foreach ( self::DEFAULT_TERMS as $taxonomy => $terms ) {
			foreach ( $terms as $slug => $label ) {
				if ( ! term_exists( $slug, $taxonomy ) ) {
					wp_insert_term( $label, $taxonomy, array( 'slug' => $slug ) );
				}
			}
		}
	}

	/**
	 * Custom title placeholders in the editor.
	 *
	 * @param string   $placeholder Default placeholder.
	 * @param \WP_Post $post        Current post.
	 * @return string
	 */
	public function title_placeholder( $placeholder, $post ) {
		if ( self::PROPERTY === $post->post_type ) {
			return __( 'Property headline, e.g. "Sunlit loft with river views"', 'keystone-properties' );
		}

		if ( self::AGENT === $post->post_type ) {
			return __( 'Agent full name', 'keystone-properties' );
		}

		return $placeholder;
	}

	/**
	 * Builds a taxonomy label set.
	 *
	 * @param string $plural   Plural name.
	 * @param string $singular Singular name.
	 * @return array<string, string>
	 */
	private function taxonomy_labels( string $plural, string $singular ): array {
		return array(
			'name'          => $plural,
			'singular_name' => $singular,
			/* translators: %s: taxonomy singular name. */
			'add_new_item'  => sprintf( __( 'Add New %s', 'keystone-properties' ), $singular ),
			/* translators: %s: taxonomy singular name. */
			'edit_item'     => sprintf( __( 'Edit %s', 'keystone-properties' ), $singular ),
			/* translators: %s: taxonomy plural name. */
			'search_items'  => sprintf( __( 'Search %s', 'keystone-properties' ), $plural ),
			/* translators: %s: taxonomy plural name. */
			'all_items'     => sprintf( __( 'All %s', 'keystone-properties' ), $plural ),
			'menu_name'     => $plural,
		);
	}
}
