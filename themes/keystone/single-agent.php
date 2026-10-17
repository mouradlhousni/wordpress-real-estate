<?php
/**
 * Agent profile with their listings.
 *
 * @package Keystone
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();

	if ( ! keystone_theme_has_plugin() ) {
		continue;
	}

	$keystone_agent = keystone_get_agent( get_post() );
	$keystone_query = new WP_Query(
		array(
			'post_type'      => 'property',
			'posts_per_page' => 12,
			'no_found_rows'  => true,
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- indexed by key, small result set.
				array(
					'key'   => 'keystone_agent',
					'value' => get_the_ID(),
					'type'  => 'NUMERIC',
				),
			),
		)
	);
	?>
	<article class="container section agent-profile">
		<div class="agent-profile__grid">
			<?php if ( ! empty( $keystone_agent['photo']['id'] ) ) : ?>
				<div class="agent-profile__photo">
					<?php
					echo wp_get_attachment_image(
						(int) $keystone_agent['photo']['id'],
						'keystone-portrait',
						false,
						array(
							'loading' => 'eager',
							'alt'     => '',
						)
					);
					?>
				</div>
			<?php endif; ?>
			<div>
				<p class="eyebrow eyebrow--accent"><?php echo esc_html( $keystone_agent['position'] ); ?></p>
				<h1 class="page-head__title"><?php the_title(); ?></h1>
				<div class="prose"><?php the_content(); ?></div>
				<ul class="agent-card__contact agent-card__contact--large">
					<?php if ( $keystone_agent['phone'] ) : ?>
						<li><a href="<?php echo esc_url( 'tel:' . preg_replace( '/[^0-9+]/', '', $keystone_agent['phone'] ) ); ?>"><?php keystone_theme_the_icon( 'phone' ); ?><?php echo esc_html( $keystone_agent['phone'] ); ?></a></li>
					<?php endif; ?>
					<?php if ( $keystone_agent['email'] ) : ?>
						<li><a href="<?php echo esc_url( 'mailto:' . $keystone_agent['email'] ); ?>"><?php keystone_theme_the_icon( 'mail' ); ?><?php echo esc_html( $keystone_agent['email'] ); ?></a></li>
					<?php endif; ?>
				</ul>
			</div>
		</div>

		<?php if ( $keystone_query->posts ) : ?>
			<section class="section--tight" aria-labelledby="agent-listings">
				<h2 id="agent-listings" class="section__title">
					<?php
					/* translators: %s: agent first name. */
					printf( esc_html__( 'Listings by %s', 'keystone' ), esc_html( strtok( get_the_title(), ' ' ) ) );
					?>
				</h2>
				<div class="card-grid">
					<?php
					foreach ( $keystone_query->posts as $keystone_post ) {
						keystone_theme_property_card( keystone_get_property( $keystone_post ) );
					}
					?>
				</div>
			</section>
		<?php endif; ?>
	</article>
	<?php
endwhile;

get_footer();
