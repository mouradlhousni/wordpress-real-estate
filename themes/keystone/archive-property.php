<?php
/**
 * Property search page (archive + city/type/status taxonomy pages).
 *
 * Results are rendered server-side (works without JavaScript, crawlable, shareable),
 * then search.js takes over for live filtering against the REST API.
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

$keystone_filters = keystone_theme_request_filters();
$keystone_results = keystone_search_properties( $keystone_filters );
$keystone_term    = get_queried_object();
$keystone_title   = sprintf(
	/* translators: %s: emphasised word. */
	esc_html__( 'Find your %s', 'keystone' ),
	'<em class="serif">' . esc_html__( 'place', 'keystone' ) . '</em>'
);

if ( $keystone_term instanceof WP_Term ) {
	$keystone_title = 'property_city' === $keystone_term->taxonomy
		? sprintf(
			/* translators: %s: city name. */
			esc_html__( 'Homes in %s', 'keystone' ),
			'<em class="serif">' . esc_html( $keystone_term->name ) . '</em>'
		)
		: esc_html( $keystone_term->name );
}
?>

<section class="search-head">
	<div class="container">
		<div class="search-head__top">
			<h1 class="search-head__title"><?php keystone_theme_the_heading( $keystone_title ); ?></h1>
			<?php if ( $keystone_term instanceof WP_Term && $keystone_term->description ) : ?>
				<p class="search-head__lead"><?php echo esc_html( $keystone_term->description ); ?></p>
			<?php endif; ?>
		</div>
		<?php get_template_part( 'template-parts/search-filters', null, array( 'filters' => $keystone_filters ) ); ?>
	</div>
</section>

<section class="container search-layout" data-search-root data-view="list">
	<div class="search-results">
		<div class="search-results__bar">
			<p class="search-results__count" data-search-count aria-live="polite" aria-atomic="true">
				<?php echo esc_html( keystone_theme_count_label( (int) $keystone_results['total'] ) ); ?>
			</p>

			<div class="search-results__tools">
				<label class="search-results__sort-label" for="search-sort"><?php esc_html_e( 'Sort by', 'keystone' ); ?></label>
				<select id="search-sort" name="sort" form="keystone-filters-form" data-search-sort>
					<?php foreach ( keystone_sort_options() as $keystone_value => $keystone_label ) : ?>
						<option value="<?php echo esc_attr( $keystone_value ); ?>" <?php selected( $keystone_filters['sort'] ?? 'newest', $keystone_value ); ?>><?php echo esc_html( $keystone_label ); ?></option>
					<?php endforeach; ?>
				</select>

				<button class="button button--ghost button--small view-toggle" type="button" data-view-toggle aria-pressed="false">
					<?php keystone_theme_the_icon( 'map' ); ?>
					<span data-view-toggle-label><?php esc_html_e( 'Show map', 'keystone' ); ?></span>
				</button>
			</div>
		</div>

		<div class="card-grid card-grid--search" data-search-results aria-busy="false">
			<?php
			if ( $keystone_results['items'] ) {
				foreach ( $keystone_results['items'] as $keystone_index => $keystone_property ) {
					keystone_theme_property_card(
						$keystone_property,
						array(
							'heading_level' => 2,
							'eager'         => $keystone_index < 2,
						)
					);
				}
			}
			?>
		</div>

		<div class="empty-state" data-search-empty<?php echo $keystone_results['items'] ? ' hidden' : ''; ?>>
			<h2><?php esc_html_e( 'No properties match these filters', 'keystone' ); ?></h2>
			<p><?php esc_html_e( 'Try another city, a higher budget or fewer bedrooms.', 'keystone' ); ?></p>
			<a class="button button--ghost" href="<?php echo esc_url( keystone_theme_search_url() ); ?>" data-search-reset><?php esc_html_e( 'Clear filters', 'keystone' ); ?></a>
		</div>

		<div data-search-pagination>
			<?php keystone_theme_search_pagination( (int) $keystone_results['page'], (int) $keystone_results['total_pages'], $keystone_filters ); ?>
		</div>
	</div>

	<aside class="search-map" aria-label="<?php esc_attr_e( 'Map of the results', 'keystone' ); ?>">
		<div class="map" data-map data-map-markers="<?php echo esc_attr( (string) wp_json_encode( keystone_theme_map_markers( $keystone_results['items'] ) ) ); ?>"></div>
	</aside>
</section>

<?php
get_footer();
