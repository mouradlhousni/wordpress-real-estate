<?php
/**
 * Post meta registration, schema and sanitization.
 *
 * @package Keystone\Properties
 */

namespace Keystone\Properties;

defined( 'ABSPATH' ) || exit;

/**
 * Single source of truth for every custom field of the plugin.
 *
 * Fields are registered with `register_post_meta()` so they are typed,
 * sanitized on every write path (admin, REST, WP-CLI) and exposed to the REST API.
 */
final class Meta {

	/**
	 * Property field definitions.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function property_fields(): array {
		return array(
			'keystone_price'      => array(
				'type'  => 'number',
				'label' => __( 'Price', 'keystone-properties' ),
				'min'   => 0,
				'max'   => 1000000000,
			),
			'keystone_bedrooms'   => array(
				'type'  => 'integer',
				'label' => __( 'Bedrooms', 'keystone-properties' ),
				'min'   => 0,
				'max'   => 50,
			),
			'keystone_bathrooms'  => array(
				'type'  => 'integer',
				'label' => __( 'Bathrooms', 'keystone-properties' ),
				'min'   => 0,
				'max'   => 50,
			),
			'keystone_surface'    => array(
				'type'  => 'number',
				'label' => __( 'Surface (m²)', 'keystone-properties' ),
				'min'   => 0,
				'max'   => 100000,
			),
			'keystone_year_built' => array(
				'type'  => 'integer',
				'label' => __( 'Year built', 'keystone-properties' ),
				'min'   => 1700,
				'max'   => (int) gmdate( 'Y' ) + 5,
			),
			'keystone_address'    => array(
				'type'  => 'string',
				'label' => __( 'Address', 'keystone-properties' ),
			),
			'keystone_latitude'   => array(
				'type'  => 'number',
				'label' => __( 'Latitude', 'keystone-properties' ),
				'min'   => -90,
				'max'   => 90,
			),
			'keystone_longitude'  => array(
				'type'  => 'number',
				'label' => __( 'Longitude', 'keystone-properties' ),
				'min'   => -180,
				'max'   => 180,
			),
			'keystone_features'   => array(
				'type'  => 'array',
				'items' => 'string',
				'label' => __( 'Features', 'keystone-properties' ),
			),
			'keystone_gallery'    => array(
				'type'  => 'array',
				'items' => 'integer',
				'label' => __( 'Gallery', 'keystone-properties' ),
			),
			'keystone_agent'      => array(
				'type'  => 'integer',
				'label' => __( 'Agent', 'keystone-properties' ),
				'min'   => 0,
			),
			'keystone_featured'   => array(
				'type'  => 'boolean',
				'label' => __( 'Featured on the home page', 'keystone-properties' ),
			),
		);
	}

	/**
	 * Agent field definitions.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function agent_fields(): array {
		return array(
			'keystone_position' => array(
				'type'  => 'string',
				'label' => __( 'Job title', 'keystone-properties' ),
			),
			'keystone_phone'    => array(
				'type'  => 'string',
				'label' => __( 'Phone', 'keystone-properties' ),
			),
			'keystone_email'    => array(
				'type'  => 'string',
				'label' => __( 'Email', 'keystone-properties' ),
			),
		);
	}

	/**
	 * Lead field definitions (private data, never exposed to REST).
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function lead_fields(): array {
		return array(
			'keystone_lead_name'     => array(
				'type'  => 'string',
				'label' => __( 'Name', 'keystone-properties' ),
			),
			'keystone_lead_email'    => array(
				'type'  => 'string',
				'label' => __( 'Email', 'keystone-properties' ),
			),
			'keystone_lead_phone'    => array(
				'type'  => 'string',
				'label' => __( 'Phone', 'keystone-properties' ),
			),
			'keystone_lead_message'  => array(
				'type'  => 'string',
				'label' => __( 'Message', 'keystone-properties' ),
			),
			'keystone_lead_property' => array(
				'type'  => 'integer',
				'label' => __( 'Property', 'keystone-properties' ),
			),
			'keystone_lead_agent'    => array(
				'type'  => 'integer',
				'label' => __( 'Agent', 'keystone-properties' ),
			),
		);
	}

	/**
	 * Hooks.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action( 'init', array( $this, 'register' ), 6 );
	}

	/**
	 * Registers every meta key.
	 *
	 * @return void
	 */
	public function register(): void {
		$this->register_group( PostTypes::PROPERTY, self::property_fields(), true );
		$this->register_group( PostTypes::AGENT, self::agent_fields(), true );
		$this->register_group( PostTypes::LEAD, self::lead_fields(), false );

		register_term_meta(
			PostTypes::TAX_CITY,
			'keystone_city_image',
			array(
				'type'              => 'integer',
				'description'       => __( 'Cover image of the city.', 'keystone-properties' ),
				'single'            => true,
				'sanitize_callback' => 'absint',
				'auth_callback'     => static fn() => current_user_can( 'manage_categories' ),
				'show_in_rest'      => true,
			)
		);
	}

	/**
	 * Registers a group of fields for one post type.
	 *
	 * @param string                              $post_type Post type.
	 * @param array<string, array<string, mixed>> $fields    Definitions.
	 * @param bool                                $in_rest   Whether to expose to the REST API.
	 * @return void
	 */
	private function register_group( string $post_type, array $fields, bool $in_rest ): void {
		foreach ( $fields as $key => $field ) {
			$args = array(
				'type'              => $field['type'],
				'description'       => $field['label'],
				'single'            => true,
				'default'           => self::default_for( $field ),
				'sanitize_callback' => static fn( $value ) => self::sanitize( $key, $value ),
				'auth_callback'     => static fn( $allowed, $meta_key, $object_id ) => current_user_can( 'edit_post', (int) $object_id ),
				'show_in_rest'      => false,
			);

			if ( $in_rest ) {
				$args['show_in_rest'] = 'array' === $field['type']
					? array(
						'schema' => array(
							'type'  => 'array',
							'items' => array( 'type' => $field['items'] ),
						),
					)
					: true;
			}

			register_post_meta( $post_type, $key, $args );
		}
	}

	/**
	 * Default value for a field definition.
	 *
	 * @param array<string, mixed> $field Field definition.
	 * @return mixed
	 */
	private static function default_for( array $field ) {
		switch ( $field['type'] ) {
			case 'integer':
				return 0;
			case 'number':
				return 0.0;
			case 'boolean':
				return false;
			case 'array':
				return array();
			default:
				return '';
		}
	}

	/**
	 * Returns the definition of any known field.
	 *
	 * @param string $key Meta key.
	 * @return array<string, mixed>|null
	 */
	public static function field( string $key ): ?array {
		$all = array_merge( self::property_fields(), self::agent_fields(), self::lead_fields() );

		return $all[ $key ] ?? null;
	}

	/**
	 * Sanitizes a value according to its field definition.
	 *
	 * @param string $key   Meta key.
	 * @param mixed  $value Raw value.
	 * @return mixed
	 */
	public static function sanitize( string $key, $value ) {
		$field = self::field( $key );

		if ( null === $field ) {
			return sanitize_text_field( (string) $value );
		}

		switch ( $field['type'] ) {
			case 'integer':
				$value = (int) $value;
				return self::clamp( $value, $field );

			case 'number':
				$value = is_numeric( $value ) ? (float) $value : 0.0;
				return self::clamp( $value, $field );

			case 'boolean':
				return (bool) rest_sanitize_boolean( $value );

			case 'array':
				$value = is_array( $value ) ? $value : array();
				if ( 'integer' === $field['items'] ) {
					return array_values( array_filter( array_map( 'absint', $value ) ) );
				}
				return array_values( array_filter( array_map( 'sanitize_text_field', $value ), 'strlen' ) );
		}

		if ( 'keystone_email' === $key || 'keystone_lead_email' === $key ) {
			return sanitize_email( (string) $value );
		}

		if ( 'keystone_lead_message' === $key ) {
			return sanitize_textarea_field( (string) $value );
		}

		return sanitize_text_field( (string) $value );
	}

	/**
	 * Clamps a numeric value to the field's bounds.
	 *
	 * @param int|float            $value Value.
	 * @param array<string, mixed> $field Field definition.
	 * @return int|float
	 */
	private static function clamp( $value, array $field ) {
		if ( isset( $field['min'] ) && $value < $field['min'] ) {
			$value = $field['min'];
		}

		if ( isset( $field['max'] ) && $value > $field['max'] ) {
			$value = $field['max'];
		}

		return $value;
	}

	/**
	 * Validates a raw value and returns an error message, or null when valid.
	 *
	 * Used by the admin meta boxes to tell editors what was corrected.
	 *
	 * @param string $key   Meta key.
	 * @param mixed  $value Raw value.
	 * @return string|null
	 */
	public static function validate( string $key, $value ): ?string {
		$field = self::field( $key );

		if ( null === $field || '' === $value || null === $value ) {
			return null;
		}

		if ( in_array( $field['type'], array( 'integer', 'number' ), true ) ) {
			if ( ! is_numeric( $value ) ) {
				/* translators: %s: field label. */
				return sprintf( __( '%s must be a number.', 'keystone-properties' ), $field['label'] );
			}

			$number = (float) $value;
			if ( ( isset( $field['min'] ) && $number < $field['min'] ) || ( isset( $field['max'] ) && $number > $field['max'] ) ) {
				/* translators: 1: field label, 2: minimum, 3: maximum. */
				return sprintf( __( '%1$s must be between %2$s and %3$s.', 'keystone-properties' ), $field['label'], $field['min'] ?? '-∞', $field['max'] ?? '∞' );
			}
		}

		if ( 'keystone_email' === $key && ! is_email( $value ) ) {
			return __( 'The agent email address is not valid.', 'keystone-properties' );
		}

		return null;
	}
}
