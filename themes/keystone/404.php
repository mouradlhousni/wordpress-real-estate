<?php
/**
 * Not found.
 *
 * @package Keystone
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<div class="container section empty-state empty-state--page">
	<p class="empty-state__code" aria-hidden="true">404</p>
	<h1>
		<?php
		keystone_theme_the_heading(
			sprintf(
				/* translators: %s: emphasised words. */
				esc_html__( 'This page has %s', 'keystone' ),
				'<em class="serif">' . esc_html__( 'moved out', 'keystone' ) . '</em>'
			)
		);
		?>
	</h1>
	<p><?php esc_html_e( 'The property may have been sold or taken off the market. Our current listings are one click away.', 'keystone' ); ?></p>
	<a class="button button--go" href="<?php echo esc_url( post_type_exists( 'property' ) ? (string) get_post_type_archive_link( 'property' ) : home_url( '/' ) ); ?>"><?php esc_html_e( 'Browse properties', 'keystone' ); ?><span class="button__go"><?php keystone_theme_the_icon( 'go' ); ?></span></a>
</div>
<?php
get_footer();
