<?php
/**
 * Full filter form of the property search page.
 *
 * Works as a plain GET form without JavaScript; search.js upgrades it to live filtering.
 *
 * @package Keystone
 *
 * @var array<string, mixed> $args { @type array $filters Active filters. }
 */

defined( 'ABSPATH' ) || exit;

$keystone_f = $args['filters'] ?? array();

/**
 * Prints `selected` when a filter matches.
 *
 * @param array<string, mixed> $filters Filters.
 * @param string               $key     Key.
 * @param string|int           $value   Option value.
 * @return void
 */
$keystone_selected = static function ( array $filters, string $key, $value ): void {
	selected( isset( $filters[ $key ] ) && (string) $filters[ $key ] === (string) $value );
};
?>
<form class="filters" id="keystone-filters-form" action="<?php echo esc_url( keystone_theme_search_url() ); ?>" method="get" data-search-form aria-label="<?php esc_attr_e( 'Filter properties', 'keystone' ); ?>">
	<fieldset class="tabs filters__status">
		<legend class="screen-reader-text"><?php esc_html_e( 'Status', 'keystone' ); ?></legend>
		<input class="tabs__input" type="radio" id="filter-status-any" name="status" value="" <?php checked( empty( $keystone_f['status'] ) ); ?>>
		<label class="tabs__label" for="filter-status-any"><?php esc_html_e( 'All', 'keystone' ); ?></label>
		<?php foreach ( keystone_theme_terms( 'property_status' ) as $keystone_term ) : ?>
			<input class="tabs__input" type="radio" id="filter-status-<?php echo esc_attr( $keystone_term->slug ); ?>" name="status" value="<?php echo esc_attr( $keystone_term->slug ); ?>" <?php checked( ( $keystone_f['status'] ?? '' ) === $keystone_term->slug ); ?>>
			<label class="tabs__label" for="filter-status-<?php echo esc_attr( $keystone_term->slug ); ?>"><?php echo esc_html( $keystone_term->name ); ?></label>
		<?php endforeach; ?>
	</fieldset>

	<div class="filters__bar">
		<div class="field field--pill">
			<label for="filter-city"><?php keystone_theme_the_icon( 'pin' ); ?><span class="screen-reader-text"><?php esc_html_e( 'City', 'keystone' ); ?></span></label>
			<select id="filter-city" name="city">
				<option value=""><?php esc_html_e( 'All cities', 'keystone' ); ?></option>
				<?php foreach ( keystone_theme_terms( 'property_city' ) as $keystone_term ) : ?>
					<option value="<?php echo esc_attr( $keystone_term->slug ); ?>" <?php $keystone_selected( $keystone_f, 'city', $keystone_term->slug ); ?>><?php echo esc_html( $keystone_term->name ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>

		<div class="field field--pill">
			<label for="filter-type"><?php keystone_theme_the_icon( 'home' ); ?><span class="screen-reader-text"><?php esc_html_e( 'Type', 'keystone' ); ?></span></label>
			<select id="filter-type" name="type">
				<option value=""><?php esc_html_e( 'Any type', 'keystone' ); ?></option>
				<?php foreach ( keystone_theme_terms( 'property_type' ) as $keystone_term ) : ?>
					<option value="<?php echo esc_attr( $keystone_term->slug ); ?>" <?php $keystone_selected( $keystone_f, 'type', $keystone_term->slug ); ?>><?php echo esc_html( $keystone_term->name ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>

		<details class="filters__more"<?php echo ( ! empty( $keystone_f['min_price'] ) || ! empty( $keystone_f['max_price'] ) || ! empty( $keystone_f['min_beds'] ) ) ? ' open' : ''; ?>>
			<summary class="filters__more-toggle"><?php keystone_theme_the_icon( 'sliders' ); ?><?php esc_html_e( 'Price & rooms', 'keystone' ); ?></summary>
			<div class="filters__grid">
				<div class="field">
					<label for="filter-min-price"><?php esc_html_e( 'Min price', 'keystone' ); ?></label>
					<select id="filter-min-price" name="min_price">
						<option value=""><?php esc_html_e( 'No minimum', 'keystone' ); ?></option>
						<?php foreach ( keystone_theme_price_steps() as $keystone_step ) : ?>
							<option value="<?php echo esc_attr( (string) $keystone_step ); ?>" <?php $keystone_selected( $keystone_f, 'min_price', $keystone_step ); ?>><?php echo esc_html( keystone_theme_short_price( $keystone_step ) ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>

				<div class="field">
					<label for="filter-max-price"><?php esc_html_e( 'Max price', 'keystone' ); ?></label>
					<select id="filter-max-price" name="max_price">
						<option value=""><?php esc_html_e( 'No maximum', 'keystone' ); ?></option>
						<?php foreach ( keystone_theme_price_steps() as $keystone_step ) : ?>
							<option value="<?php echo esc_attr( (string) $keystone_step ); ?>" <?php $keystone_selected( $keystone_f, 'max_price', $keystone_step ); ?>><?php echo esc_html( keystone_theme_short_price( $keystone_step ) ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>

				<div class="field">
					<label for="filter-beds"><?php esc_html_e( 'Bedrooms', 'keystone' ); ?></label>
					<select id="filter-beds" name="min_beds">
						<option value=""><?php esc_html_e( 'Any', 'keystone' ); ?></option>
						<?php foreach ( array( 1, 2, 3, 4, 5 ) as $keystone_beds ) : ?>
							<option value="<?php echo esc_attr( (string) $keystone_beds ); ?>" <?php $keystone_selected( $keystone_f, 'min_beds', $keystone_beds ); ?>>
								<?php
								/* translators: %d: minimum number of bedrooms. */
								echo esc_html( sprintf( __( '%d+', 'keystone' ), $keystone_beds ) );
								?>
							</option>
						<?php endforeach; ?>
					</select>
				</div>
			</div>
		</details>

		<div class="filters__actions">
			<button class="button button--accent" type="submit" data-search-submit><?php keystone_theme_the_icon( 'search' ); ?><?php esc_html_e( 'Show results', 'keystone' ); ?></button>
			<a class="button button--ghost" href="<?php echo esc_url( keystone_theme_search_url() ); ?>" data-search-reset><?php esc_html_e( 'Clear', 'keystone' ); ?></a>
		</div>
	</div>
</form>
