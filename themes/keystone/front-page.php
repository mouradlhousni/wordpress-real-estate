<?php
/**
 * Home page: framed hero with search, key figures, featured homes, cities, agents, testimonials.
 *
 * @package Keystone
 */

defined( 'ABSPATH' ) || exit;

get_header();

if ( ! keystone_theme_has_plugin() ) {
	echo '<div class="container section">';
	keystone_theme_missing_plugin_notice();
	echo '</div>';
	get_footer();
	return;
}

$keystone_featured = keystone_search_properties(
	array(
		'featured' => true,
		'per_page' => 7,
		'sort'     => 'price-desc',
	)
)['items'];
$keystone_hero     = $keystone_featured ? array_shift( $keystone_featured ) : null;
$keystone_featured = array_slice( $keystone_featured, 0, 6 );
$keystone_bento    = keystone_search_properties(
	array(
		'per_page' => 1,
		'sort'     => 'surface',
		'exclude'  => array_merge( array( (int) ( $keystone_hero['id'] ?? 0 ) ), wp_list_pluck( $keystone_featured, 'id' ) ),
	)
)['items'][0] ?? null;
$keystone_cities   = keystone_theme_terms( 'property_city' );
$keystone_types    = array_values(
	array_unique(
		array_filter(
			array_map(
				static fn( $item ) => $item['type']['slug'] ?? '',
				$keystone_featured
			)
		)
	)
);
$keystone_agents   = get_posts(
	array(
		'post_type'      => 'agent',
		'posts_per_page' => 8,
		'orderby'        => array(
			'menu_order' => 'ASC',
			'title'      => 'ASC',
		),
		'no_found_rows'  => true,
	)
);
$keystone_stats    = array(
	array(
		'value' => number_format_i18n( (int) wp_count_posts( 'property' )->publish ),
		'label' => __( 'Homes on the market', 'keystone' ),
	),
	array(
		'value' => number_format_i18n( count( $keystone_cities ) ),
		'label' => __( 'Cities, one local team each', 'keystone' ),
	),
	array(
		'value' => number_format_i18n( (int) wp_count_posts( 'agent' )->publish ),
		'label' => __( 'Senior agents, no juniors', 'keystone' ),
	),
);
$keystone_stats    = array_merge( $keystone_stats, keystone_theme_agency_facts() );
?>

<section class="hero">
	<div class="container">
		<div class="hero__frame">
			<?php
			if ( $keystone_hero && ! empty( $keystone_hero['image']['id'] ) ) {
				echo wp_get_attachment_image(
					(int) $keystone_hero['image']['id'],
					'full',
					false,
					array(
						'class'         => 'hero__img',
						'sizes'         => '(min-width: 1280px) 1240px, 100vw',
						'loading'       => 'eager',
						'fetchpriority' => 'high',
						'decoding'      => 'async',
						'alt'           => '',
					)
				);
			}
			?>
			<div class="hero__content">
				<?php if ( $keystone_cities ) : ?>
					<p class="eyebrow eyebrow--light"><?php echo esc_html( implode( ' · ', wp_list_pluck( $keystone_cities, 'name' ) ) ); ?></p>
				<?php endif; ?>
				<h1 class="hero__title">
					<?php
					keystone_theme_the_heading(
						sprintf(
							/* translators: %s: emphasised word. */
							esc_html__( 'Homes with %s, in cities that have it.', 'keystone' ),
							'<em class="serif">' . esc_html__( 'character', 'keystone' ) . '</em>'
						)
					);
					?>
				</h1>
				<p class="hero__lead"><?php esc_html_e( 'A small portfolio chosen by agents who live in each neighbourhood. Search by city, budget or the way you want to live.', 'keystone' ); ?></p>
				<?php
				get_template_part(
					'template-parts/search-bar',
					null,
					array(
						'id'      => 'hero-search',
						'variant' => 'hero',
					)
				);
				?>
			</div>

			<?php if ( $keystone_hero ) : ?>
				<a class="hero__feature" href="<?php echo esc_url( $keystone_hero['link'] ); ?>">
					<span class="hero__feature-label"><?php esc_html_e( 'Featured home', 'keystone' ); ?></span>
					<span class="hero__feature-title"><?php echo esc_html( $keystone_hero['title'] ); ?></span>
					<span class="hero__feature-price"><?php echo esc_html( $keystone_hero['price_formatted'] ); ?></span>
					<span class="hero__feature-go" aria-hidden="true"><?php keystone_theme_the_icon( 'go' ); ?></span>
				</a>
			<?php endif; ?>
		</div>

		<?php $keystone_type_terms = keystone_theme_terms( 'property_type' ); ?>
		<?php if ( $keystone_type_terms ) : ?>
			<nav class="hero__types" aria-label="<?php esc_attr_e( 'Browse by property type', 'keystone' ); ?>">
				<ul class="chip-list">
					<?php foreach ( $keystone_type_terms as $keystone_type ) : ?>
						<li><a class="chip" href="<?php echo esc_url( keystone_theme_search_url( array( 'type' => $keystone_type->slug ) ) ); ?>"><?php echo esc_html( $keystone_type->name ); ?></a></li>
					<?php endforeach; ?>
				</ul>
			</nav>
		<?php endif; ?>
	</div>
</section>

<section class="section" aria-labelledby="advisors-title">
	<div class="container">
		<header class="split-head">
			<h2 id="advisors-title" class="section__title">
				<?php
				keystone_theme_the_heading(
					sprintf(
						/* translators: %s: emphasised word. */
						esc_html__( 'Your trusted %s property advisors', 'keystone' ),
						'<em class="serif">' . esc_html__( 'local', 'keystone' ) . '</em>'
					)
				);
				?>
			</h2>
			<p class="split-head__lead"><?php esc_html_e( 'We keep the portfolio deliberately small, so every listing gets honest pricing advice, professional photography and an agent who answers the phone.', 'keystone' ); ?></p>
		</header>

		<div class="bento">
			<?php if ( $keystone_bento && ! empty( $keystone_bento['image']['id'] ) ) : ?>
				<a class="bento__photo" href="<?php echo esc_url( $keystone_bento['link'] ); ?>">
					<?php
					echo wp_get_attachment_image(
						(int) $keystone_bento['image']['id'],
						'large',
						false,
						array(
							'sizes'    => '(min-width: 1000px) 560px, 100vw',
							'loading'  => 'lazy',
							'decoding' => 'async',
						)
					);
					?>
					<span class="bento__caption">
						<span><?php echo esc_html( $keystone_bento['title'] ); ?></span>
						<span class="bento__go" aria-hidden="true"><?php keystone_theme_the_icon( 'go' ); ?></span>
					</span>
				</a>
			<?php endif; ?>
			<dl class="bento__stats">
				<?php foreach ( array_slice( $keystone_stats, 0, 4 ) as $keystone_index => $keystone_stat ) : ?>
					<div class="stat<?php echo 1 === $keystone_index ? ' stat--ink' : ''; ?>">
						<dt><?php echo esc_html( $keystone_stat['label'] ); ?></dt>
						<dd><?php echo esc_html( $keystone_stat['value'] ); ?></dd>
					</div>
				<?php endforeach; ?>
			</dl>
		</div>
	</div>
</section>

<?php if ( $keystone_featured ) : ?>
	<section class="section section--sand" aria-labelledby="featured-title">
		<div class="container">
			<header class="section__head">
				<h2 id="featured-title" class="section__title">
					<?php
					keystone_theme_the_heading(
						sprintf(
							/* translators: %s: emphasised word. */
							esc_html__( 'Featured %s', 'keystone' ),
							'<em class="serif">' . esc_html__( 'homes', 'keystone' ) . '</em>'
						)
					);
					?>
				</h2>
				<?php if ( count( $keystone_types ) > 1 ) : ?>
					<div class="chip-list" role="group" aria-label="<?php esc_attr_e( 'Filter featured homes by type', 'keystone' ); ?>" data-card-filter="featured-grid" hidden>
						<button class="chip" type="button" aria-pressed="true" data-type=""><?php esc_html_e( 'All', 'keystone' ); ?></button>
						<?php foreach ( keystone_theme_terms( 'property_type' ) as $keystone_type ) : ?>
							<?php if ( in_array( $keystone_type->slug, $keystone_types, true ) ) : ?>
								<button class="chip" type="button" aria-pressed="false" data-type="<?php echo esc_attr( $keystone_type->slug ); ?>"><?php echo esc_html( $keystone_type->name ); ?></button>
							<?php endif; ?>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</header>
			<div class="card-grid" id="featured-grid">
				<?php
				foreach ( $keystone_featured as $keystone_property ) {
					keystone_theme_property_card( $keystone_property );
				}
				?>
			</div>
			<p class="section__more">
				<a class="button button--go" href="<?php echo esc_url( keystone_theme_search_url() ); ?>">
					<?php esc_html_e( 'View all properties', 'keystone' ); ?>
					<span class="button__go"><?php keystone_theme_the_icon( 'go' ); ?></span>
				</a>
			</p>
		</div>
	</section>
<?php endif; ?>

<?php if ( $keystone_cities ) : ?>
	<section class="section" aria-labelledby="cities-title">
		<div class="container">
			<header class="section__head">
				<h2 id="cities-title" class="section__title">
					<?php
					keystone_theme_the_heading(
						sprintf(
							/* translators: %s: emphasised word. */
							esc_html__( 'Browse by %s', 'keystone' ),
							'<em class="serif">' . esc_html__( 'city', 'keystone' ) . '</em>'
						)
					);
					?>
				</h2>
			</header>
			<ul class="city-mosaic">
				<?php foreach ( $keystone_cities as $keystone_city ) : ?>
					<li class="city-tile">
						<a href="<?php echo esc_url( (string) get_term_link( $keystone_city ) ); ?>">
							<?php
							$keystone_cover = keystone_city_image_id( $keystone_city->term_id );
							if ( $keystone_cover ) {
								echo wp_get_attachment_image(
									$keystone_cover,
									'large',
									false,
									array(
										'class'   => 'city-tile__img',
										'sizes'   => '(min-width: 1000px) 420px, 100vw',
										'loading' => 'lazy',
										'alt'     => '',
									)
								);
							}
							?>
							<span class="city-tile__label">
								<span class="city-tile__name"><?php echo esc_html( $keystone_city->name ); ?></span>
								<span class="city-tile__count"><?php echo esc_html( keystone_theme_count_label( (int) $keystone_city->count ) ); ?></span>
							</span>
							<span class="city-tile__go" aria-hidden="true"><?php keystone_theme_the_icon( 'go' ); ?></span>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	</section>
<?php endif; ?>

<?php if ( $keystone_agents ) : ?>
	<section class="section section--sand" aria-labelledby="agents-title">
		<div class="container">
			<header class="section__head">
				<h2 id="agents-title" class="section__title">
					<?php
					keystone_theme_the_heading(
						sprintf(
							/* translators: %s: emphasised word. */
							esc_html__( 'Meet the %s', 'keystone' ),
							'<em class="serif">' . esc_html__( 'agents', 'keystone' ) . '</em>'
						)
					);
					?>
				</h2>
				<div class="rail-nav" data-rail-nav="agent-rail" hidden>
					<button class="icon-button" type="button" data-rail-prev aria-controls="agent-rail">
						<?php keystone_theme_the_icon( 'arrow', 'icon--flip' ); ?>
						<span class="screen-reader-text"><?php esc_html_e( 'Previous agents', 'keystone' ); ?></span>
					</button>
					<button class="icon-button" type="button" data-rail-next aria-controls="agent-rail">
						<?php keystone_theme_the_icon( 'arrow' ); ?>
						<span class="screen-reader-text"><?php esc_html_e( 'Next agents', 'keystone' ); ?></span>
					</button>
				</div>
			</header>
			<div class="rail" id="agent-rail" tabindex="-1">
				<?php
				foreach ( $keystone_agents as $keystone_agent ) {
					get_template_part( 'template-parts/agent-card', null, array( 'agent' => keystone_get_agent( $keystone_agent ) ) );
				}
				?>
			</div>
			<p class="section__more">
				<a class="button button--ghost button--go" href="<?php echo esc_url( (string) get_post_type_archive_link( 'agent' ) ); ?>">
					<?php esc_html_e( 'All agents', 'keystone' ); ?>
					<span class="button__go"><?php keystone_theme_the_icon( 'go' ); ?></span>
				</a>
			</p>
		</div>
	</section>
<?php endif; ?>

<section class="section" aria-labelledby="testimonials-title">
	<div class="container">
		<header class="section__head section__head--center">
			<h2 id="testimonials-title" class="section__title">
				<?php
				keystone_theme_the_heading(
					sprintf(
						/* translators: %s: emphasised word. */
						esc_html__( 'What our clients %s', 'keystone' ),
						'<em class="serif">' . esc_html__( 'are saying', 'keystone' ) . '</em>'
					)
				);
				?>
			</h2>
		</header>
		<div class="quote-grid">
			<?php foreach ( keystone_theme_testimonials() as $keystone_quote ) : ?>
				<figure class="quote">
					<?php keystone_theme_the_icon( 'quote', 'quote__mark' ); ?>
					<blockquote><p><?php echo esc_html( $keystone_quote['quote'] ); ?></p></blockquote>
					<figcaption>
						<span class="quote__avatar" aria-hidden="true"><?php echo esc_html( mb_substr( $keystone_quote['name'], 0, 1 ) ); ?></span>
						<span class="quote__who">
							<span class="quote__name"><?php echo esc_html( $keystone_quote['name'] ); ?></span>
							<span class="quote__detail"><?php echo esc_html( $keystone_quote['detail'] ); ?></span>
						</span>
					</figcaption>
				</figure>
			<?php endforeach; ?>
		</div>
	</div>
</section>

<section class="section section--flush" aria-labelledby="cta-title">
	<div class="container">
		<div class="cta">
			<div class="cta__text">
				<h2 id="cta-title" class="cta__title">
					<?php
					keystone_theme_the_heading(
						sprintf(
							/* translators: %s: emphasised word. */
							esc_html__( 'Let’s find your %s', 'keystone' ),
							'<em class="serif">' . esc_html__( 'next address', 'keystone' ) . '</em>'
						)
					);
					?>
				</h2>
				<p><?php esc_html_e( 'Tell us the city, the budget and the light you like in the morning. An agent who lives there will shortlist homes within 48 hours.', 'keystone' ); ?></p>
				<div class="cta__actions">
					<a class="button button--accent button--go" href="<?php echo esc_url( keystone_theme_search_url() ); ?>">
						<?php esc_html_e( 'Start searching', 'keystone' ); ?>
						<span class="button__go"><?php keystone_theme_the_icon( 'go' ); ?></span>
					</a>
					<a class="button button--light-ghost" href="<?php echo esc_url( (string) get_post_type_archive_link( 'agent' ) ); ?>"><?php esc_html_e( 'Talk to an agent', 'keystone' ); ?></a>
				</div>
			</div>
			<?php if ( $keystone_cities ) : ?>
				<ul class="cta__cities">
					<?php foreach ( $keystone_cities as $keystone_city ) : ?>
						<li><a href="<?php echo esc_url( (string) get_term_link( $keystone_city ) ); ?>"><?php keystone_theme_the_icon( 'pin' ); ?><?php echo esc_html( $keystone_city->name ); ?></a></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
	</div>
</section>

<?php
get_footer();
