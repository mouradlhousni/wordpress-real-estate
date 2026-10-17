<?php
/**
 * Single property: gallery, spec sheet, description, map, agent contact, similar homes.
 *
 * @package Keystone
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();

	if ( ! keystone_theme_has_plugin() ) {
		echo '<div class="container section">';
		keystone_theme_missing_plugin_notice();
		echo '</div>';
		continue;
	}

	$keystone_p      = keystone_get_property();
	$keystone_agent  = keystone_get_agent( $keystone_p['agent'] );
	$keystone_images = array_values( array_unique( array_filter( array_merge( array( (int) get_post_thumbnail_id() ), $keystone_p['gallery'] ) ) ) );
	$keystone_specs  = array(
		__( 'Price', 'keystone' )      => $keystone_p['price_formatted'],
		__( 'Type', 'keystone' )       => $keystone_p['type']['name'] ?? '',
		__( 'Status', 'keystone' )     => $keystone_p['status']['name'] ?? '',
		__( 'Bedrooms', 'keystone' )   => $keystone_p['bedrooms'] ? (string) $keystone_p['bedrooms'] : '',
		__( 'Bathrooms', 'keystone' )  => $keystone_p['bathrooms'] ? (string) $keystone_p['bathrooms'] : '',
		__( 'Surface', 'keystone' )    => $keystone_p['surface'] ? number_format_i18n( $keystone_p['surface'] ) . ' m²' : '',
		__( 'Year built', 'keystone' ) => $keystone_p['year_built'] ? (string) $keystone_p['year_built'] : '',
		__( 'Reference', 'keystone' )  => 'KR-' . str_pad( (string) $keystone_p['id'], 5, '0', STR_PAD_LEFT ),
	);
	?>

	<article class="property">
		<header class="container property__head">
			<nav class="breadcrumbs" aria-label="<?php esc_attr_e( 'Breadcrumb', 'keystone' ); ?>">
				<ol>
					<li><a href="<?php echo esc_url( keystone_theme_search_url() ); ?>"><?php esc_html_e( 'Properties', 'keystone' ); ?></a></li>
					<?php if ( ! empty( $keystone_p['city']['link'] ) ) : ?>
						<li><a href="<?php echo esc_url( $keystone_p['city']['link'] ); ?>"><?php echo esc_html( $keystone_p['city']['name'] ); ?></a></li>
					<?php endif; ?>
					<li aria-current="page"><?php the_title(); ?></li>
				</ol>
			</nav>

			<div class="property__titles">
				<div>
					<?php if ( ! empty( $keystone_p['status']['name'] ) ) : ?>
						<span class="badge badge--<?php echo esc_attr( $keystone_p['status']['slug'] ); ?>"><?php echo esc_html( $keystone_p['status']['name'] ); ?></span>
					<?php endif; ?>
					<h1 class="property__title"><?php the_title(); ?></h1>
					<?php if ( $keystone_p['address'] ) : ?>
						<p class="property__address"><?php keystone_theme_the_icon( 'pin' ); ?><?php echo esc_html( $keystone_p['address'] ); ?></p>
					<?php endif; ?>
				</div>
				<p class="property__price"><?php echo esc_html( $keystone_p['price_formatted'] ); ?></p>
			</div>
		</header>

		<?php if ( $keystone_images ) : ?>
			<div class="container">
				<?php get_template_part( 'template-parts/gallery', null, array( 'images' => $keystone_images ) ); ?>
			</div>
		<?php endif; ?>

		<div class="container property__layout">
			<div class="property__main">
				<?php
				get_template_part(
					'template-parts/specs',
					null,
					array(
						'property' => $keystone_p,
						'variant'  => 'tiles',
					)
				);
				?>

				<section class="property__section" aria-labelledby="about-title">
					<h2 id="about-title" class="property__heading"><?php esc_html_e( 'About this property', 'keystone' ); ?></h2>
					<div class="prose"><?php the_content(); ?></div>
				</section>

				<section class="property__section" aria-labelledby="facts-title">
					<h2 id="facts-title" class="property__heading"><?php esc_html_e( 'Key facts', 'keystone' ); ?></h2>
					<dl class="spec-sheet">
						<?php foreach ( array_filter( $keystone_specs ) as $keystone_label => $keystone_value ) : ?>
							<div class="spec-sheet__row">
								<dt><?php echo esc_html( $keystone_label ); ?></dt>
								<dd><?php echo esc_html( $keystone_value ); ?></dd>
							</div>
						<?php endforeach; ?>
					</dl>
				</section>

				<?php if ( $keystone_p['features'] ) : ?>
					<section class="property__section" aria-labelledby="features-title">
						<h2 id="features-title" class="property__heading"><?php esc_html_e( 'Features', 'keystone' ); ?></h2>
						<ul class="feature-list chip-list">
							<?php foreach ( $keystone_p['features'] as $keystone_feature ) : ?>
								<li class="chip chip--static"><?php keystone_theme_the_icon( 'check' ); ?><?php echo esc_html( $keystone_feature ); ?></li>
							<?php endforeach; ?>
						</ul>
					</section>
				<?php endif; ?>

				<?php if ( $keystone_p['latitude'] || $keystone_p['longitude'] ) : ?>
					<section class="property__section" aria-labelledby="location-title">
						<h2 id="location-title" class="property__heading"><?php esc_html_e( 'Location', 'keystone' ); ?></h2>
						<div class="map map--single" data-map data-map-zoom="15" data-map-markers="<?php echo esc_attr( (string) wp_json_encode( keystone_theme_map_markers( array( $keystone_p ) ) ) ); ?>" aria-label="<?php echo esc_attr( sprintf( /* translators: %s: address. */ __( 'Map showing %s', 'keystone' ), $keystone_p['address'] ) ); ?>" role="region"></div>
					</section>
				<?php endif; ?>
			</div>

			<aside class="property__aside" id="contact-agent" aria-labelledby="contact-title">
				<div class="contact-panel">
					<h2 id="contact-title" class="contact-panel__title"><?php esc_html_e( 'Contact the agent', 'keystone' ); ?></h2>
					<?php
					get_template_part(
						'template-parts/agent-card',
						null,
						array(
							'agent'   => $keystone_agent,
							'compact' => true,
						)
					);
					?>
					<?php
					if ( keystone_is_static_build() ) {
						get_template_part( 'template-parts/static-contact', null, array( 'agent' => $keystone_agent ) );
					} else {
						keystone_lead_form( (int) $keystone_p['id'] );
					}
					?>
				</div>
			</aside>
		</div>

		<?php
		$keystone_similar = keystone_search_properties(
			array(
				'city'     => $keystone_p['city']['slug'] ?? '',
				'exclude'  => array( $keystone_p['id'] ),
				'per_page' => 3,
			)
		)['items'];

		if ( count( $keystone_similar ) < 3 ) {
			$keystone_similar = keystone_search_properties(
				array(
					'type'     => $keystone_p['type']['slug'] ?? '',
					'exclude'  => array( $keystone_p['id'] ),
					'per_page' => 3,
				)
			)['items'];
		}
		?>

		<?php if ( $keystone_similar ) : ?>
			<section class="section section--sand" aria-labelledby="similar-title">
				<div class="container">
					<header class="section__head">
						<h2 id="similar-title" class="section__title">
							<?php
							keystone_theme_the_heading(
								sprintf(
									/* translators: %s: emphasised word. */
									esc_html__( 'Similar %s', 'keystone' ),
									'<em class="serif">' . esc_html__( 'properties', 'keystone' ) . '</em>'
								)
							);
							?>
						</h2>
					</header>
					<div class="card-grid">
						<?php
						foreach ( $keystone_similar as $keystone_property ) {
							keystone_theme_property_card( $keystone_property );
						}
						?>
					</div>
				</div>
			</section>
		<?php endif; ?>
	</article>

	<?php
endwhile;

get_footer();
