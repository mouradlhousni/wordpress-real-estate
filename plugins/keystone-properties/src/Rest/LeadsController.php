<?php
/**
 * REST endpoint: POST keystone/v1/leads.
 *
 * @package Keystone\Properties
 */

namespace Keystone\Properties\Rest;

use Keystone\Properties\Leads\LeadService;

defined( 'ABSPATH' ) || exit;

/**
 * Receives the "Contact the agent" form submitted with JavaScript.
 *
 * The route is public by nature (visitors are anonymous): protection comes from the form
 * nonce, the honeypot, server-side validation and per-IP rate limiting in LeadService.
 */
final class LeadsController {

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
			PropertiesController::NAMESPACE,
			'/leads',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'create_item' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'name'                => array(
						'type'     => 'string',
						'required' => true,
					),
					'email'               => array(
						'type'     => 'string',
						'required' => true,
					),
					'phone'               => array( 'type' => 'string' ),
					'message'             => array(
						'type'     => 'string',
						'required' => true,
					),
					'property_id'         => array(
						'type'     => 'integer',
						'required' => true,
					),
					'keystone_lead_nonce' => array(
						'type'     => 'string',
						'required' => true,
					),
					LeadService::HONEYPOT => array( 'type' => 'string' ),
				),
			)
		);
	}

	/**
	 * Handles a submission.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function create_item( \WP_REST_Request $request ) {
		$result = ( new LeadService() )->submit( $request->get_params(), LeadService::client_ip() );

		if ( is_wp_error( $result ) ) {
			// Expose per-field messages so the form can highlight each invalid input.
			$fields = array();
			foreach ( $result->get_error_codes() as $code ) {
				$fields[ $code ] = $result->get_error_message( $code );
			}
			$result->add_data(
				array(
					'status' => $result->get_error_data()['status'] ?? 400,
					'fields' => $fields,
				)
			);

			return $result;
		}

		$response = rest_ensure_response( $result );
		$response->header( 'Cache-Control', 'no-store' );

		return $response;
	}
}
