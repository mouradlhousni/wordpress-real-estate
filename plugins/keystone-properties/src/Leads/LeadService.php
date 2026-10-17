<?php
/**
 * "Contact the agent" business logic.
 *
 * @package Keystone\Properties
 */

namespace Keystone\Properties\Leads;

use Keystone\Properties\Meta;
use Keystone\Properties\PostTypes;

defined( 'ABSPATH' ) || exit;

/**
 * Validates, rate-limits, stores and emails a lead.
 *
 * Transport-agnostic: used by both the REST endpoint (JS submit) and the admin-post
 * fallback (no-JS submit), so both paths share exactly the same rules.
 */
final class LeadService {

	public const NONCE_ACTION = 'keystone_lead';
	public const HONEYPOT     = 'keystone_website';

	private const RATE_LIMIT  = 3;
	private const RATE_WINDOW = 10 * MINUTE_IN_SECONDS;

	/**
	 * Processes a submission.
	 *
	 * @param array<string, mixed> $data Raw, unslashed submission.
	 * @param string               $ip   Client IP address.
	 * @return array{message: string, demo: bool}|\WP_Error
	 */
	public function submit( array $data, string $ip ) {
		$nonce = isset( $data['keystone_lead_nonce'] ) ? sanitize_text_field( (string) $data['keystone_lead_nonce'] ) : '';

		if ( ! wp_verify_nonce( $nonce, self::NONCE_ACTION ) ) {
			return new \WP_Error( 'keystone_invalid_nonce', __( 'Your session has expired. Please reload the page and try again.', 'keystone-properties' ), array( 'status' => 403 ) );
		}

		// Honeypot: bots fill every field. Pretend success so they learn nothing.
		if ( ! empty( $data[ self::HONEYPOT ] ) ) {
			return array(
				'message' => $this->success_message(),
				'demo'    => false,
			);
		}

		$lead = $this->sanitize( $data );
		$errs = $this->validate( $lead );

		if ( $errs->has_errors() ) {
			return $errs;
		}

		/*
		 * Demo mode short-circuit: everything above (nonce, honeypot, validation) ran for real,
		 * but nothing is written to the database (not even the rate-limit counter) and no email is sent.
		 */
		if ( keystone_is_demo_mode() ) {
			return array(
				'message' => __( 'Demo mode: the form works, but no data is stored or sent.', 'keystone-properties' ),
				'demo'    => true,
			);
		}

		if ( $this->is_rate_limited( $ip ) ) {
			return new \WP_Error( 'keystone_rate_limited', __( 'You have sent several messages in a short time. Please try again in a few minutes.', 'keystone-properties' ), array( 'status' => 429 ) );
		}

		$lead_id = $this->store( $lead );

		if ( is_wp_error( $lead_id ) ) {
			return $lead_id;
		}

		$this->hit_rate_limit( $ip );
		$this->notify( $lead, $lead_id );

		/**
		 * Fires after a lead has been stored and notified.
		 *
		 * @param int                  $lead_id Lead post ID.
		 * @param array<string, mixed> $lead    Sanitized lead data.
		 */
		do_action( 'keystone_lead_created', $lead_id, $lead );

		return array(
			'message' => $this->success_message(),
			'demo'    => false,
		);
	}

	/**
	 * Sanitizes the raw submission.
	 *
	 * @param array<string, mixed> $data Raw data.
	 * @return array<string, mixed>
	 */
	public function sanitize( array $data ): array {
		return array(
			'name'        => sanitize_text_field( (string) ( $data['name'] ?? '' ) ),
			'email'       => sanitize_email( (string) ( $data['email'] ?? '' ) ),
			'phone'       => preg_replace( '/[^0-9+().\-\s]/', '', sanitize_text_field( (string) ( $data['phone'] ?? '' ) ) ),
			'message'     => sanitize_textarea_field( (string) ( $data['message'] ?? '' ) ),
			'property_id' => absint( $data['property_id'] ?? 0 ),
		);
	}

	/**
	 * Validates sanitized data. Error codes are field names so the UI can highlight them.
	 *
	 * @param array<string, mixed> $lead Sanitized lead.
	 * @return \WP_Error
	 */
	public function validate( array $lead ): \WP_Error {
		$errors = new \WP_Error();

		$name_length = mb_strlen( $lead['name'] );
		if ( $name_length < 2 || $name_length > 100 ) {
			$errors->add( 'name', __( 'Please enter your name.', 'keystone-properties' ) );
		}

		if ( ! is_email( $lead['email'] ) ) {
			$errors->add( 'email', __( 'Please enter a valid email address.', 'keystone-properties' ) );
		}

		if ( '' !== $lead['phone'] && ! preg_match( '/^[0-9+().\-\s]{6,25}$/', $lead['phone'] ) ) {
			$errors->add( 'phone', __( 'Please enter a valid phone number, or leave it empty.', 'keystone-properties' ) );
		}

		$message_length = mb_strlen( $lead['message'] );
		if ( $message_length < 10 || $message_length > 2000 ) {
			$errors->add( 'message', __( 'Your message should be between 10 and 2,000 characters.', 'keystone-properties' ) );
		}

		$property = get_post( $lead['property_id'] );
		if ( ! $property || PostTypes::PROPERTY !== $property->post_type || 'publish' !== $property->post_status ) {
			$errors->add( 'property_id', __( 'This property is no longer available.', 'keystone-properties' ) );
		}

		if ( $errors->has_errors() ) {
			$errors->add_data( array( 'status' => 422 ) );
		}

		return $errors;
	}

	/**
	 * Whether this IP exhausted its quota.
	 *
	 * @param string $ip IP address.
	 * @return bool
	 */
	private function is_rate_limited( string $ip ): bool {
		return (int) get_transient( $this->rate_key( $ip ) ) >= self::RATE_LIMIT;
	}

	/**
	 * Counts one submission for this IP.
	 *
	 * @param string $ip IP address.
	 * @return void
	 */
	private function hit_rate_limit( string $ip ): void {
		$key = $this->rate_key( $ip );
		set_transient( $key, (int) get_transient( $key ) + 1, self::RATE_WINDOW );
	}

	/**
	 * Transient key for an IP. The IP is hashed: we never store it in clear.
	 *
	 * @param string $ip IP address.
	 * @return string
	 */
	private function rate_key( string $ip ): string {
		return 'keystone_rl_' . substr( hash_hmac( 'sha256', $ip, wp_salt( 'nonce' ) ), 0, 32 );
	}

	/**
	 * Saves the lead as a private post.
	 *
	 * @param array<string, mixed> $lead Sanitized lead.
	 * @return int|\WP_Error
	 */
	private function store( array $lead ) {
		$agent_id = (int) get_post_meta( $lead['property_id'], 'keystone_agent', true );

		$lead_id = wp_insert_post(
			array(
				'post_type'   => PostTypes::LEAD,
				'post_status' => 'private',
				/* translators: 1: sender name, 2: property title. */
				'post_title'  => sprintf( __( '%1$s about %2$s', 'keystone-properties' ), $lead['name'], get_the_title( $lead['property_id'] ) ),
				'meta_input'  => array(
					'keystone_lead_name'     => $lead['name'],
					'keystone_lead_email'    => $lead['email'],
					'keystone_lead_phone'    => $lead['phone'],
					'keystone_lead_message'  => $lead['message'],
					'keystone_lead_property' => $lead['property_id'],
					'keystone_lead_agent'    => $agent_id,
				),
			),
			true
		);

		if ( is_wp_error( $lead_id ) ) {
			return new \WP_Error( 'keystone_lead_failed', __( 'We could not send your message. Please try again later.', 'keystone-properties' ), array( 'status' => 500 ) );
		}

		return (int) $lead_id;
	}

	/**
	 * Emails the listing agent (or the site admin when no agent is set).
	 *
	 * @param array<string, mixed> $lead    Sanitized lead.
	 * @param int                  $lead_id Lead ID.
	 * @return void
	 */
	private function notify( array $lead, int $lead_id ): void {
		$agent_id = (int) get_post_meta( $lead['property_id'], 'keystone_agent', true );
		$to       = $agent_id ? (string) get_post_meta( $agent_id, 'keystone_email', true ) : '';
		$to       = is_email( $to ) ? $to : (string) get_option( 'admin_email' );

		/* translators: %s: property title. */
		$subject = sprintf( __( 'New enquiry: %s', 'keystone-properties' ), get_the_title( $lead['property_id'] ) );

		$body = implode(
			"\n",
			array(
				/* translators: %s: name. */
				sprintf( __( 'Name: %s', 'keystone-properties' ), $lead['name'] ),
				/* translators: %s: email. */
				sprintf( __( 'Email: %s', 'keystone-properties' ), $lead['email'] ),
				/* translators: %s: phone. */
				sprintf( __( 'Phone: %s', 'keystone-properties' ), $lead['phone'] ? $lead['phone'] : '—' ),
				/* translators: %s: URL. */
				sprintf( __( 'Property: %s', 'keystone-properties' ), get_permalink( $lead['property_id'] ) ),
				'',
				$lead['message'],
				'',
				/* translators: %s: admin URL. */
				sprintf( __( 'View in WordPress: %s', 'keystone-properties' ), admin_url( 'post.php?post=' . $lead_id . '&action=edit' ) ),
			)
		);

		$headers = array( sprintf( 'Reply-To: "%1$s" <%2$s>', str_replace( array( '"', '\\' ), '', $lead['name'] ), $lead['email'] ) );

		wp_mail( $to, $subject, $body, $headers );
	}

	/**
	 * Generic success message.
	 *
	 * @return string
	 */
	private function success_message(): string {
		return __( 'Thank you! Your message has been sent. The agent will get back to you shortly.', 'keystone-properties' );
	}

	/**
	 * Maps an error to a short code safe to pass in a redirect URL.
	 *
	 * @param \WP_Error $error Error.
	 * @return string
	 */
	public static function code_for_error( \WP_Error $error ): string {
		$map = array(
			'keystone_invalid_nonce' => 'expired',
			'keystone_rate_limited'  => 'limited',
			'keystone_lead_failed'   => 'failed',
		);

		return $map[ $error->get_error_code() ] ?? 'invalid';
	}

	/**
	 * User-facing message for a redirect code.
	 *
	 * @param string $code Code.
	 * @return string
	 */
	public static function message_for_code( string $code ): string {
		switch ( $code ) {
			case 'expired':
				return __( 'Your session has expired. Please reload the page and try again.', 'keystone-properties' );
			case 'limited':
				return __( 'You have sent several messages in a short time. Please try again in a few minutes.', 'keystone-properties' );
			case 'invalid':
				return __( 'Please check the form: name, a valid email and a message of at least 10 characters are required.', 'keystone-properties' );
			default:
				return __( 'Something went wrong. Please try again.', 'keystone-properties' );
		}
	}

	/**
	 * Best-effort client IP (REMOTE_ADDR only: forwarded headers are trivially spoofable).
	 *
	 * @return string
	 */
	public static function client_ip(): string {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';

		return filter_var( $ip, FILTER_VALIDATE_IP ) ? $ip : '0.0.0.0';
	}
}
