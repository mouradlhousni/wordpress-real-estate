<?php
/**
 * Admin meta boxes for properties, agents and leads.
 *
 * @package Keystone\Properties
 */

namespace Keystone\Properties\Admin;

use Keystone\Properties\Meta;
use Keystone\Properties\PostTypes;

defined( 'ABSPATH' ) || exit;

/**
 * Hand-built meta boxes (no ACF): nonce-protected, capability-checked, validated and sanitized.
 */
final class MetaBoxes {

	private const NONCE_ACTION = 'keystone_save_meta';
	private const NONCE_FIELD  = 'keystone_meta_nonce';
	private const ERRORS_KEY   = 'keystone_meta_errors_';

	/**
	 * Hooks.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_filter( 'use_block_editor_for_post_type', array( $this, 'use_classic_editor' ), 10, 2 );
		add_action( 'add_meta_boxes_' . PostTypes::PROPERTY, array( $this, 'add_property_boxes' ) );
		add_action( 'add_meta_boxes_' . PostTypes::AGENT, array( $this, 'add_agent_boxes' ) );
		add_action( 'add_meta_boxes_' . PostTypes::LEAD, array( $this, 'add_lead_boxes' ) );
		add_action( 'save_post_' . PostTypes::PROPERTY, array( $this, 'save_property' ) );
		add_action( 'save_post_' . PostTypes::AGENT, array( $this, 'save_agent' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
		add_action( 'admin_notices', array( $this, 'render_errors' ) );
	}

	/**
	 * Properties and agents are structured records: a focused form beats a free-form canvas.
	 *
	 * @param bool   $use_block_editor Default.
	 * @param string $post_type        Post type.
	 * @return bool
	 */
	public function use_classic_editor( $use_block_editor, $post_type ) {
		if ( in_array( $post_type, array( PostTypes::PROPERTY, PostTypes::AGENT, PostTypes::LEAD ), true ) ) {
			return false;
		}

		return $use_block_editor;
	}

	/**
	 * Registers the property meta boxes.
	 *
	 * @return void
	 */
	public function add_property_boxes(): void {
		add_meta_box( 'keystone-property-details', __( 'Property details', 'keystone-properties' ), array( $this, 'render_property_details' ), PostTypes::PROPERTY, 'normal', 'high' );
		add_meta_box( 'keystone-property-location', __( 'Location', 'keystone-properties' ), array( $this, 'render_property_location' ), PostTypes::PROPERTY, 'normal', 'default' );
		add_meta_box( 'keystone-property-gallery', __( 'Gallery', 'keystone-properties' ), array( $this, 'render_property_gallery' ), PostTypes::PROPERTY, 'normal', 'default' );
		add_meta_box( 'keystone-property-agent', __( 'Listing agent', 'keystone-properties' ), array( $this, 'render_property_agent' ), PostTypes::PROPERTY, 'side', 'default' );
	}

	/**
	 * Registers the agent meta box.
	 *
	 * @return void
	 */
	public function add_agent_boxes(): void {
		add_meta_box( 'keystone-agent-contact', __( 'Contact details', 'keystone-properties' ), array( $this, 'render_agent_contact' ), PostTypes::AGENT, 'normal', 'high' );
	}

	/**
	 * Registers the read-only lead meta box.
	 *
	 * @return void
	 */
	public function add_lead_boxes(): void {
		add_meta_box( 'keystone-lead-details', __( 'Enquiry', 'keystone-properties' ), array( $this, 'render_lead_details' ), PostTypes::LEAD, 'normal', 'high' );
	}

	/**
	 * Property details box.
	 *
	 * @param \WP_Post $post Post.
	 * @return void
	 */
	public function render_property_details( \WP_Post $post ): void {
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_FIELD );
		$fields = Meta::property_fields();

		echo '<div class="keystone-grid">';
		foreach ( array( 'keystone_price', 'keystone_surface', 'keystone_bedrooms', 'keystone_bathrooms', 'keystone_year_built' ) as $key ) {
			$this->number_input( $post, $key, $fields[ $key ] );
		}
		echo '</div>';

		$features = (array) get_post_meta( $post->ID, 'keystone_features', true );
		printf(
			'<p class="keystone-field"><label for="keystone_features">%1$s</label><textarea id="keystone_features" name="keystone[keystone_features]" rows="5" class="widefat" aria-describedby="keystone_features_help">%2$s</textarea><span id="keystone_features_help" class="description">%3$s</span></p>',
			esc_html( $fields['keystone_features']['label'] ),
			esc_textarea( implode( "\n", $features ) ),
			esc_html__( 'One feature per line, e.g. "Private terrace".', 'keystone-properties' )
		);

		printf(
			'<p class="keystone-field keystone-field--inline"><input type="checkbox" id="keystone_featured" name="keystone[keystone_featured]" value="1" %1$s /> <label for="keystone_featured">%2$s</label></p>',
			checked( (bool) get_post_meta( $post->ID, 'keystone_featured', true ), true, false ),
			esc_html( $fields['keystone_featured']['label'] )
		);
	}

	/**
	 * Location box.
	 *
	 * @param \WP_Post $post Post.
	 * @return void
	 */
	public function render_property_location( \WP_Post $post ): void {
		$fields = Meta::property_fields();

		printf(
			'<p class="keystone-field"><label for="keystone_address">%1$s</label><input type="text" id="keystone_address" name="keystone[keystone_address]" value="%2$s" class="widefat" autocomplete="off" /></p>',
			esc_html( $fields['keystone_address']['label'] ),
			esc_attr( (string) get_post_meta( $post->ID, 'keystone_address', true ) )
		);

		echo '<div class="keystone-grid">';
		$this->number_input( $post, 'keystone_latitude', $fields['keystone_latitude'], '0.000001' );
		$this->number_input( $post, 'keystone_longitude', $fields['keystone_longitude'], '0.000001' );
		echo '</div>';

		printf( '<p class="description">%s</p>', esc_html__( 'Coordinates are used for the map. Tip: right-click a location on openstreetmap.org and choose "Show address" to read them.', 'keystone-properties' ) );
	}

	/**
	 * Gallery box: media-library picker with drag & drop ordering.
	 *
	 * @param \WP_Post $post Post.
	 * @return void
	 */
	public function render_property_gallery( \WP_Post $post ): void {
		$ids = array_map( 'absint', (array) get_post_meta( $post->ID, 'keystone_gallery', true ) );

		echo '<div class="keystone-gallery" data-keystone-gallery>';
		printf( '<input type="hidden" name="keystone[keystone_gallery]" value="%s" data-keystone-gallery-input />', esc_attr( implode( ',', $ids ) ) );
		echo '<ul class="keystone-gallery__list" data-keystone-gallery-list>';
		foreach ( $ids as $id ) {
			$thumb = wp_get_attachment_image( $id, 'thumbnail' );
			if ( ! $thumb ) {
				continue;
			}
			printf(
				'<li class="keystone-gallery__item" data-id="%1$d">%2$s<button type="button" class="keystone-gallery__remove" aria-label="%3$s">&times;</button></li>',
				(int) $id,
				$thumb, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core-generated, escaped markup.
				esc_attr__( 'Remove image', 'keystone-properties' )
			);
		}
		echo '</ul>';
		printf( '<button type="button" class="button" data-keystone-gallery-add>%s</button>', esc_html__( 'Add images', 'keystone-properties' ) );
		printf( '<p class="description">%s</p>', esc_html__( 'Drag images to reorder them. The featured image is shown first on the listing.', 'keystone-properties' ) );
		echo '</div>';
	}

	/**
	 * Agent relation box.
	 *
	 * @param \WP_Post $post Post.
	 * @return void
	 */
	public function render_property_agent( \WP_Post $post ): void {
		$current = (int) get_post_meta( $post->ID, 'keystone_agent', true );
		$agents  = get_posts(
			array(
				'post_type'      => PostTypes::AGENT,
				'post_status'    => 'publish',
				'posts_per_page' => 100,
				'orderby'        => 'title',
				'order'          => 'ASC',
				'no_found_rows'  => true,
			)
		);

		printf( '<label class="screen-reader-text" for="keystone_agent">%s</label>', esc_html__( 'Listing agent', 'keystone-properties' ) );
		echo '<select id="keystone_agent" name="keystone[keystone_agent]" class="widefat">';
		printf( '<option value="0">%s</option>', esc_html__( '— No agent —', 'keystone-properties' ) );
		foreach ( $agents as $agent ) {
			printf( '<option value="%1$d" %2$s>%3$s</option>', (int) $agent->ID, selected( $current, $agent->ID, false ), esc_html( get_the_title( $agent ) ) );
		}
		echo '</select>';
	}

	/**
	 * Agent contact box.
	 *
	 * @param \WP_Post $post Post.
	 * @return void
	 */
	public function render_agent_contact( \WP_Post $post ): void {
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_FIELD );
		$types = array(
			'keystone_position' => 'text',
			'keystone_phone'    => 'tel',
			'keystone_email'    => 'email',
		);

		echo '<div class="keystone-grid">';
		foreach ( Meta::agent_fields() as $key => $field ) {
			printf(
				'<p class="keystone-field"><label for="%1$s">%2$s</label><input type="%3$s" id="%1$s" name="keystone[%1$s]" value="%4$s" class="widefat" /></p>',
				esc_attr( $key ),
				esc_html( $field['label'] ),
				esc_attr( $types[ $key ] ),
				esc_attr( (string) get_post_meta( $post->ID, $key, true ) )
			);
		}
		echo '</div>';
		printf( '<p class="description">%s</p>', esc_html__( 'Use the main editor for the biography and the featured image for the photo.', 'keystone-properties' ) );
	}

	/**
	 * Read-only lead details.
	 *
	 * @param \WP_Post $post Post.
	 * @return void
	 */
	public function render_lead_details( \WP_Post $post ): void {
		$property_id = (int) get_post_meta( $post->ID, 'keystone_lead_property', true );
		$agent_id    = (int) get_post_meta( $post->ID, 'keystone_lead_agent', true );
		$email       = (string) get_post_meta( $post->ID, 'keystone_lead_email', true );

		echo '<table class="form-table keystone-lead"><tbody>';
		$this->lead_row( __( 'Name', 'keystone-properties' ), esc_html( (string) get_post_meta( $post->ID, 'keystone_lead_name', true ) ) );
		$this->lead_row( __( 'Email', 'keystone-properties' ), sprintf( '<a href="%1$s">%2$s</a>', esc_url( 'mailto:' . $email ), esc_html( $email ) ) );
		$this->lead_row( __( 'Phone', 'keystone-properties' ), esc_html( (string) get_post_meta( $post->ID, 'keystone_lead_phone', true ) ) );
		$this->lead_row( __( 'Property', 'keystone-properties' ), $property_id ? sprintf( '<a href="%1$s">%2$s</a>', esc_url( (string) get_edit_post_link( $property_id ) ), esc_html( get_the_title( $property_id ) ) ) : '—' );
		$this->lead_row( __( 'Agent', 'keystone-properties' ), $agent_id ? esc_html( get_the_title( $agent_id ) ) : '—' );
		$this->lead_row( __( 'Received', 'keystone-properties' ), esc_html( get_the_date( 'Y-m-d H:i', $post ) ) );
		$this->lead_row( __( 'Message', 'keystone-properties' ), nl2br( esc_html( (string) get_post_meta( $post->ID, 'keystone_lead_message', true ) ) ) );
		echo '</tbody></table>';
	}

	/**
	 * Saves property fields.
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	public function save_property( $post_id ): void {
		$input = $this->verified_input( (int) $post_id );

		if ( null === $input ) {
			return;
		}

		// Normalize the non-scalar inputs into the shape the schema expects.
		$input['keystone_features'] = isset( $input['keystone_features'] ) ? preg_split( '/\r\n|\r|\n/', (string) $input['keystone_features'] ) : array();
		$input['keystone_gallery']  = isset( $input['keystone_gallery'] ) && '' !== $input['keystone_gallery'] ? explode( ',', (string) $input['keystone_gallery'] ) : array();
		$input['keystone_featured'] = ! empty( $input['keystone_featured'] );

		if ( ! empty( $input['keystone_agent'] ) && PostTypes::AGENT !== get_post_type( (int) $input['keystone_agent'] ) ) {
			$input['keystone_agent'] = 0;
		}

		$this->persist( (int) $post_id, Meta::property_fields(), $input );
	}

	/**
	 * Saves agent fields.
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	public function save_agent( $post_id ): void {
		$input = $this->verified_input( (int) $post_id );

		if ( null === $input ) {
			return;
		}

		$this->persist( (int) $post_id, Meta::agent_fields(), $input );
	}

	/**
	 * Enqueues admin assets on our edit screens only.
	 *
	 * @param string $hook_suffix Admin page.
	 * @return void
	 */
	public function enqueue( $hook_suffix ): void {
		$screen = get_current_screen();

		if ( ! $screen || ! in_array( $hook_suffix, array( 'post.php', 'post-new.php' ), true ) || ! in_array( $screen->post_type, array( PostTypes::PROPERTY, PostTypes::AGENT, PostTypes::LEAD ), true ) ) {
			return;
		}

		wp_enqueue_style( 'keystone-admin', KEYSTONE_PROPERTIES_URL . 'assets/css/admin.css', array(), KEYSTONE_PROPERTIES_VERSION );

		if ( PostTypes::PROPERTY === $screen->post_type ) {
			wp_enqueue_media();
			wp_enqueue_script( 'keystone-admin-gallery', KEYSTONE_PROPERTIES_URL . 'assets/js/admin-gallery.js', array( 'jquery', 'jquery-ui-sortable' ), KEYSTONE_PROPERTIES_VERSION, true );
			wp_localize_script(
				'keystone-admin-gallery',
				'keystoneGallery',
				array(
					'title'  => __( 'Property gallery', 'keystone-properties' ),
					'button' => __( 'Use these images', 'keystone-properties' ),
					'remove' => __( 'Remove image', 'keystone-properties' ),
				)
			);
		}
	}

	/**
	 * Displays validation messages collected during the last save.
	 *
	 * @return void
	 */
	public function render_errors(): void {
		$key    = self::ERRORS_KEY . get_current_user_id();
		$errors = get_transient( $key );

		if ( ! is_array( $errors ) || ! $errors ) {
			return;
		}

		delete_transient( $key );

		echo '<div class="notice notice-warning is-dismissible"><p><strong>' . esc_html__( 'Some values were corrected:', 'keystone-properties' ) . '</strong></p><ul>';
		foreach ( $errors as $error ) {
			echo '<li>' . esc_html( $error ) . '</li>';
		}
		echo '</ul></div>';
	}

	/**
	 * Verifies nonce, autosave and capability, then returns the unslashed input.
	 *
	 * @param int $post_id Post ID.
	 * @return array<string, mixed>|null Null when the request must be ignored.
	 */
	private function verified_input( int $post_id ): ?array {
		if ( ! isset( $_POST[ self::NONCE_FIELD ] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST[ self::NONCE_FIELD ] ) ), self::NONCE_ACTION ) ) {
			return null;
		}

		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) ) {
			return null;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return null;
		}

		// Each value is sanitized field by field in persist() through Meta::sanitize().
		$input = isset( $_POST['keystone'] ) && is_array( $_POST['keystone'] ) ? wp_unslash( $_POST['keystone'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		return $input;
	}

	/**
	 * Validates, sanitizes and stores a set of fields.
	 *
	 * @param int                                 $post_id Post ID.
	 * @param array<string, array<string, mixed>> $fields  Field definitions.
	 * @param array<string, mixed>                $input   Raw input.
	 * @return void
	 */
	private function persist( int $post_id, array $fields, array $input ): void {
		$errors = array();

		foreach ( $fields as $key => $field ) {
			$raw   = $input[ $key ] ?? null;
			$error = is_scalar( $raw ) ? Meta::validate( $key, $raw ) : null;

			if ( $error ) {
				$errors[] = $error;
			}

			// update_post_meta() runs the registered sanitize_callback.
			update_post_meta( $post_id, $key, $raw ?? Meta::sanitize( $key, null ) );
		}

		if ( $errors ) {
			set_transient( self::ERRORS_KEY . get_current_user_id(), $errors, MINUTE_IN_SECONDS );
		}
	}

	/**
	 * Outputs a number field.
	 *
	 * @param \WP_Post             $post  Post.
	 * @param string               $key   Meta key.
	 * @param array<string, mixed> $field Field definition.
	 * @param string               $step  Step attribute.
	 * @return void
	 */
	private function number_input( \WP_Post $post, string $key, array $field, string $step = '1' ): void {
		$value = get_post_meta( $post->ID, $key, true );

		printf(
			'<p class="keystone-field"><label for="%1$s">%2$s</label><input type="number" id="%1$s" name="keystone[%1$s]" value="%3$s" step="%4$s" %5$s %6$s class="widefat" /></p>',
			esc_attr( $key ),
			esc_html( $field['label'] ),
			esc_attr( '' === $value || 0.0 === (float) $value ? '' : (string) $value ),
			esc_attr( $step ),
			isset( $field['min'] ) ? 'min="' . esc_attr( (string) $field['min'] ) . '"' : '',
			isset( $field['max'] ) ? 'max="' . esc_attr( (string) $field['max'] ) . '"' : ''
		);
	}

	/**
	 * Outputs a lead table row. `$value` must already be escaped.
	 *
	 * @param string $label Label.
	 * @param string $value Escaped HTML value.
	 * @return void
	 */
	private function lead_row( string $label, string $value ): void {
		printf( '<tr><th scope="row">%1$s</th><td>%2$s</td></tr>', esc_html( $label ), $value ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by caller.
	}
}
