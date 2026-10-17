<?php
/**
 * "Contact the agent" form: markup, assets and no-JS fallback.
 *
 * @package Keystone\Properties
 */

namespace Keystone\Properties\Leads;

defined( 'ABSPATH' ) || exit;

/**
 * Renders the lead form and handles classic (non-JS) submissions via admin-post.php.
 *
 * With JavaScript, the same form is submitted to the REST endpoint without a reload.
 */
final class FormHandler {

	private const ACTION = 'keystone_lead';

	/**
	 * Hooks.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action( 'admin_post_nopriv_' . self::ACTION, array( $this, 'handle_post' ) );
		add_action( 'admin_post_' . self::ACTION, array( $this, 'handle_post' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ) );
	}

	/**
	 * Registers (does not enqueue) the form script; it is enqueued only when the form renders.
	 *
	 * @return void
	 */
	public function register_assets(): void {
		wp_register_script(
			'keystone-lead-form',
			KEYSTONE_PROPERTIES_URL . 'assets/js/lead-form.js',
			array(),
			KEYSTONE_PROPERTIES_VERSION,
			array(
				'strategy'  => 'defer',
				'in_footer' => true,
			)
		);

		wp_localize_script(
			'keystone-lead-form',
			'keystoneLeadForm',
			array(
				'endpoint'  => esc_url_raw( rest_url( 'keystone/v1/leads' ) ),
				// Keeps cookie authentication consistent for logged-in visitors (the form nonce is user-bound).
				'restNonce' => wp_create_nonce( 'wp_rest' ),
				'sending'   => __( 'Sending…', 'keystone-properties' ),
				'error'     => __( 'Something went wrong. Please try again.', 'keystone-properties' ),
			)
		);
	}

	/**
	 * Renders the form for a property.
	 *
	 * @param int $property_id Property ID.
	 * @return void
	 */
	public static function render( int $property_id ): void {
		wp_enqueue_script( 'keystone-lead-form' );

		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- read-only status flag set by our own redirect.
		$status = isset( $_GET['keystone_lead'] ) ? sanitize_key( wp_unslash( $_GET['keystone_lead'] ) ) : '';
		$notice = '';
		if ( 'sent' === $status ) {
			$notice = __( 'Thank you! Your message has been sent. The agent will get back to you shortly.', 'keystone-properties' );
		} elseif ( 'demo' === $status ) {
			$notice = __( 'Demo mode: the form works, but no data is stored or sent.', 'keystone-properties' );
		} elseif ( '' !== $status ) {
			$notice = LeadService::message_for_code( $status );
		}
		// phpcs:enable

		$title   = get_the_title( $property_id );
		$default = sprintf(
			/* translators: %s: property title. */
			__( 'Hello, I am interested in "%s". Could you tell me more and arrange a viewing?', 'keystone-properties' ),
			$title
		);
		$uid = 'keystone-lead-' . $property_id;
		?>
		<form class="lead-form" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" novalidate data-keystone-lead-form>
			<input type="hidden" name="action" value="<?php echo esc_attr( self::ACTION ); ?>" />
			<input type="hidden" name="property_id" value="<?php echo esc_attr( (string) $property_id ); ?>" />
			<?php wp_nonce_field( LeadService::NONCE_ACTION, 'keystone_lead_nonce', false ); ?>

			<div class="lead-form__field">
				<label for="<?php echo esc_attr( $uid ); ?>-name"><?php esc_html_e( 'Full name', 'keystone-properties' ); ?> <span aria-hidden="true">*</span></label>
				<input type="text" id="<?php echo esc_attr( $uid ); ?>-name" name="name" autocomplete="name" required minlength="2" maxlength="100" aria-describedby="<?php echo esc_attr( $uid ); ?>-name-error" />
				<p class="lead-form__error" id="<?php echo esc_attr( $uid ); ?>-name-error" data-error-for="name"></p>
			</div>

			<div class="lead-form__field">
				<label for="<?php echo esc_attr( $uid ); ?>-email"><?php esc_html_e( 'Email', 'keystone-properties' ); ?> <span aria-hidden="true">*</span></label>
				<input type="email" id="<?php echo esc_attr( $uid ); ?>-email" name="email" autocomplete="email" required aria-describedby="<?php echo esc_attr( $uid ); ?>-email-error" />
				<p class="lead-form__error" id="<?php echo esc_attr( $uid ); ?>-email-error" data-error-for="email"></p>
			</div>

			<div class="lead-form__field">
				<label for="<?php echo esc_attr( $uid ); ?>-phone"><?php esc_html_e( 'Phone (optional)', 'keystone-properties' ); ?></label>
				<input type="tel" id="<?php echo esc_attr( $uid ); ?>-phone" name="phone" autocomplete="tel" pattern="[0-9+().\-\s]{6,25}" aria-describedby="<?php echo esc_attr( $uid ); ?>-phone-error" />
				<p class="lead-form__error" id="<?php echo esc_attr( $uid ); ?>-phone-error" data-error-for="phone"></p>
			</div>

			<div class="lead-form__field">
				<label for="<?php echo esc_attr( $uid ); ?>-message"><?php esc_html_e( 'Message', 'keystone-properties' ); ?> <span aria-hidden="true">*</span></label>
				<textarea id="<?php echo esc_attr( $uid ); ?>-message" name="message" rows="5" required minlength="10" maxlength="2000" aria-describedby="<?php echo esc_attr( $uid ); ?>-message-error"><?php echo esc_textarea( $default ); ?></textarea>
				<p class="lead-form__error" id="<?php echo esc_attr( $uid ); ?>-message-error" data-error-for="message"></p>
			</div>

			<?php // Honeypot: visually hidden, skipped by keyboard and assistive tech. ?>
			<div class="lead-form__hp" aria-hidden="true">
				<label for="<?php echo esc_attr( $uid ); ?>-website"><?php esc_html_e( 'Leave this field empty', 'keystone-properties' ); ?></label>
				<input type="text" id="<?php echo esc_attr( $uid ); ?>-website" name="<?php echo esc_attr( LeadService::HONEYPOT ); ?>" tabindex="-1" autocomplete="off" />
			</div>

			<button type="submit" class="button button--primary button--block"><?php esc_html_e( 'Send message', 'keystone-properties' ); ?></button>

			<p class="lead-form__status<?php echo $notice ? ' is-visible' : ''; ?>" role="status" aria-live="polite" data-lead-status><?php echo esc_html( $notice ); ?></p>

			<?php if ( keystone_is_demo_mode() ) : ?>
				<p class="lead-form__note"><?php esc_html_e( 'This is a demo site: the form is fully validated, but nothing is stored or emailed.', 'keystone-properties' ); ?></p>
			<?php endif; ?>
		</form>
		<?php
	}

	/**
	 * Classic POST fallback (JavaScript disabled).
	 *
	 * @return void
	 */
	public function handle_post(): void {
		// The nonce is verified inside LeadService::submit(); inputs are sanitized there too.
		$data   = wp_unslash( $_POST ); // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$result = ( new LeadService() )->submit( is_array( $data ) ? $data : array(), LeadService::client_ip() );

		$property_id = isset( $data['property_id'] ) ? absint( $data['property_id'] ) : 0;
		$back        = $property_id ? get_permalink( $property_id ) : home_url( '/' );

		if ( is_wp_error( $result ) ) {
			// Only a known error code travels in the URL, never free text.
			$url = add_query_arg( 'keystone_lead', LeadService::code_for_error( $result ), $back );
		} else {
			$url = add_query_arg( 'keystone_lead', $result['demo'] ? 'demo' : 'sent', $back );
		}

		wp_safe_redirect( $url . '#contact-agent', 303 );
		exit;
	}
}
