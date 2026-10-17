<?php
/**
 * Static export: the contact form is replaced by a contact card and a link to the Playground demo.
 *
 * @package Keystone
 *
 * @var array<string, mixed> $args { @type array|null $agent Normalized agent. }
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="static-contact">
	<p><?php esc_html_e( 'This is the static showcase of the site: forms are disabled here.', 'keystone' ); ?></p>
	<p><?php esc_html_e( 'Try the working enquiry form, the admin and the REST API in a disposable WordPress that runs in your browser:', 'keystone' ); ?></p>
	<a class="button button--primary button--block" href="<?php echo esc_url( keystone_playground_url() ); ?>" rel="noopener" target="_blank">
		<?php esc_html_e( 'Open the live demo in WordPress Playground', 'keystone' ); ?>
	</a>
</div>
