<?php
/**
 * Agents archive.
 *
 * @package Keystone
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<div class="container section">
	<header class="page-head">
		<h1 class="page-head__title">
			<?php
			keystone_theme_the_heading(
				sprintf(
					/* translators: %s: emphasised word. */
					esc_html__( 'Our %s', 'keystone' ),
					'<em class="serif">' . esc_html__( 'agents', 'keystone' ) . '</em>'
				)
			);
			?>
		</h1>
		<p class="page-head__lead"><?php esc_html_e( 'Each agent lives in the city they cover. Write to them directly from any listing.', 'keystone' ); ?></p>
	</header>

	<?php if ( have_posts() && keystone_theme_has_plugin() ) : ?>
		<div class="agent-grid">
			<?php
			while ( have_posts() ) :
				the_post();
				get_template_part(
					'template-parts/agent-card',
					null,
					array(
						'agent'         => keystone_get_agent( get_post() ),
						'heading_level' => 2,
					)
				);
			endwhile;
			?>
		</div>
	<?php else : ?>
		<p><?php esc_html_e( 'No agents yet.', 'keystone' ); ?></p>
	<?php endif; ?>
</div>
<?php
get_footer();
