<?php
/**
 * REST endpoint: GET keystone/v1/properties.
 *
 * @package Keystone\Properties
 */

namespace Keystone\Properties\Rest;

use Keystone\Properties\PostTypes;
use Keystone\Properties\PropertyRepository;

defined( 'ABSPATH' ) || exit;

/**
 * Public, read-only, cached property search used by the front-end filters.
 */
final class PropertiesController {

	public const NAMESPACE = 'keystone/v1';

	/**
	 * Hooks.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Registers the route.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		register_rest_route(
			self::NAMESPACE,
			'/properties',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => '__return_true',
					'args'                => $this->get_collection_params(),
				),
				'schema' => array( $this, 'get_item_schema' ),
			)
		);
	}

	/**
	 * Handles a search request.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public function get_items( \WP_REST_Request $request ): \WP_REST_Response {
		$filters = array();

		foreach ( array_keys( $this->get_collection_params() ) as $param ) {
			if ( null !== $request->get_param( $param ) ) {
				$filters[ $param ] = $request->get_param( $param );
			}
		}

		if ( isset( $filters['min_price'], $filters['max_price'] ) && $filters['max_price'] > 0 && $filters['min_price'] > $filters['max_price'] ) {
			// Be forgiving: swap reversed bounds instead of returning nothing.
			list( $filters['min_price'], $filters['max_price'] ) = array( $filters['max_price'], $filters['min_price'] );
		}

		$result   = PropertyRepository::search( $filters );
		$response = rest_ensure_response( $result );

		$response->header( 'X-WP-Total', (string) $result['total'] );
		$response->header( 'X-WP-TotalPages', (string) $result['total_pages'] );
		$response->header( 'Cache-Control', 'public, max-age=60' );

		return $response;
	}

	/**
	 * Query parameters, validated and sanitized by the REST server.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public function get_collection_params(): array {
		$slug = array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_title',
			'validate_callback' => 'rest_validate_request_arg',
		);

		return array(
			'city'      => array_merge( $slug, array( 'description' => __( 'City term slug.', 'keystone-properties' ) ) ),
			'type'      => array_merge( $slug, array( 'description' => __( 'Property type term slug.', 'keystone-properties' ) ) ),
			'status'    => array_merge( $slug, array( 'description' => __( 'Status term slug.', 'keystone-properties' ) ) ),
			'min_price' => array(
				'description' => __( 'Minimum price.', 'keystone-properties' ),
				'type'        => 'number',
				'minimum'     => 0,
			),
			'max_price' => array(
				'description' => __( 'Maximum price.', 'keystone-properties' ),
				'type'        => 'number',
				'minimum'     => 0,
			),
			'min_beds'  => array(
				'description' => __( 'Minimum number of bedrooms.', 'keystone-properties' ),
				'type'        => 'integer',
				'minimum'     => 0,
				'maximum'     => 20,
			),
			'sort'      => array(
				'description' => __( 'Sort order.', 'keystone-properties' ),
				'type'        => 'string',
				'enum'        => array_keys( PropertyRepository::SORTS ),
				'default'     => 'newest',
			),
			'featured'  => array(
				'description' => __( 'Only featured properties.', 'keystone-properties' ),
				'type'        => 'boolean',
			),
			'exclude'   => array(
				'description' => __( 'Property IDs to exclude.', 'keystone-properties' ),
				'type'        => 'array',
				'items'       => array( 'type' => 'integer' ),
				'maxItems'    => 50,
			),
			'page'      => array(
				'description' => __( 'Page number.', 'keystone-properties' ),
				'type'        => 'integer',
				'minimum'     => 1,
				'default'     => 1,
			),
			'per_page'  => array(
				'description' => __( 'Results per page.', 'keystone-properties' ),
				'type'        => 'integer',
				'minimum'     => 1,
				'maximum'     => PropertyRepository::MAX_PER_PAGE,
				'default'     => 9,
			),
		);
	}

	/**
	 * Response schema (for discoverability via OPTIONS).
	 *
	 * @return array<string, mixed>
	 */
	public function get_item_schema(): array {
		$term = array(
			'type'       => array( 'object', 'null' ),
			'properties' => array(
				'slug' => array( 'type' => 'string' ),
				'name' => array( 'type' => 'string' ),
				'link' => array( 'type' => 'string' ),
			),
		);

		return array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => PostTypes::PROPERTY . '-search',
			'type'       => 'object',
			'properties' => array(
				'items'       => array(
					'type'  => 'array',
					'items' => array(
						'type'       => 'object',
						'properties' => array(
							'id'              => array( 'type' => 'integer' ),
							'title'           => array( 'type' => 'string' ),
							'link'            => array( 'type' => 'string' ),
							'price'           => array( 'type' => 'number' ),
							'price_formatted' => array( 'type' => 'string' ),
							'bedrooms'        => array( 'type' => 'integer' ),
							'bathrooms'       => array( 'type' => 'integer' ),
							'surface'         => array( 'type' => 'number' ),
							'latitude'        => array( 'type' => 'number' ),
							'longitude'       => array( 'type' => 'number' ),
							'city'            => $term,
							'type'            => $term,
							'status'          => $term,
							'image'           => array( 'type' => array( 'object', 'null' ) ),
							'photo_count'     => array( 'type' => 'integer' ),
							'agent_brief'     => array(
								'type'       => array( 'object', 'null' ),
								'properties' => array(
									'name'   => array( 'type' => 'string' ),
									'avatar' => array( 'type' => 'string' ),
								),
							),
						),
					),
				),
				'total'       => array( 'type' => 'integer' ),
				'total_pages' => array( 'type' => 'integer' ),
				'page'        => array( 'type' => 'integer' ),
				'per_page'    => array( 'type' => 'integer' ),
			),
		);
	}
}
