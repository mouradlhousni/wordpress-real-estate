<?php
/**
 * Compact search bar (home hero and "Property Search Bar" block). Submits to the property search page.
 *
 * @package Keystone
 *
 * @var array<string, mixed> $args {
 *     @type string $id      Unique id prefix.
 *     @type string $variant "hero" | "block".
 * }
 */

defined( 'ABSPATH' ) || exit;

if ( ! keystone_theme_has_plugin() ) {
	return;
}

$keystone_id      = sanitize_html_class( $args['id'] ?? 'search' );
$keystone_variant = sanitize_html_class( $args['variant'] ?? 'hero' );
?>
<form class="search-bar search-bar--<?php echo esc_attr( $keystone_variant ); ?>" action="<?php echo esc_url( keystone_theme_search_url() ); ?>" method="get" role="search" aria-label="<?php esc_attr_e( 'Search properties', 'keystone' ); ?>">
	<fieldset class="segmented search-bar__tabs">
		<legend class="screen-reader-text"><?php esc_html_e( 'Buy or rent', 'keystone' ); ?></legend>
		<?php
		$keystone_tabs = array(
			'for-sale' => __( 'Buy', 'keystone' ),
			'for-rent' => __( 'Rent', 'keystone' ),
			''         => __( 'All', 'keystone' ),
		);
		foreach ( $keystone_tabs as $keystone_value => $keystone_label ) :
			$keystone_tab_id = $keystone_id . '-status-' . ( $keystone_value ? $keystone_value : 'any' );
			?>
			<input class="segmented__input" type="radio" id="<?php echo esc_attr( $keystone_tab_id ); ?>" name="status" value="<?php echo esc_attr( $keystone_value ); ?>" <?php checked( 'for-sale', $keystone_value ); ?>>
			<label class="segmented__label" for="<?php echo esc_attr( $keystone_tab_id ); ?>"><?php echo esc_html( $keystone_label ); ?></label>
		<?php endforeach; ?>
	</fieldset>

	<div class="search-bar__fields">
		<div class="search-bar__field">
			<label for="<?php echo esc_attr( $keystone_id ); ?>-city"><?php esc_html_e( 'Location', 'keystone' ); ?></label>
			<select id="<?php echo esc_attr( $keystone_id ); ?>-city" name="city">
				<option value=""><?php esc_html_e( 'All cities', 'keystone' ); ?></option>
				<?php foreach ( keystone_theme_terms( 'property_city' ) as $keystone_term ) : ?>
					<option value="<?php echo esc_attr( $keystone_term->slug ); ?>"><?php echo esc_html( $keystone_term->name ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>

		<div class="search-bar__field">
			<label for="<?php echo esc_attr( $keystone_id ); ?>-type"><?php esc_html_e( 'Property type', 'keystone' ); ?></label>
			<select id="<?php echo esc_attr( $keystone_id ); ?>-type" name="type">
				<option value=""><?php esc_html_e( 'Any type', 'keystone' ); ?></option>
				<?php foreach ( keystone_theme_terms( 'property_type' ) as $keystone_term ) : ?>
					<option value="<?php echo esc_attr( $keystone_term->slug ); ?>"><?php echo esc_html( $keystone_term->name ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>

		<div class="search-bar__field">
			<label for="<?php echo esc_attr( $keystone_id ); ?>-max"><?php esc_html_e( 'Max price', 'keystone' ); ?></label>
			<select id="<?php echo esc_attr( $keystone_id ); ?>-max" name="max_price">
				<option value=""><?php esc_html_e( 'No limit', 'keystone' ); ?></option>
				<?php foreach ( keystone_theme_price_steps() as $keystone_step ) : ?>
					<option value="<?php echo esc_attr( (string) $keystone_step ); ?>"><?php echo esc_html( keystone_theme_short_price( $keystone_step ) ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>

		<button class="button button--accent search-bar__submit" type="submit">
			<?php keystone_theme_the_icon( 'search' ); ?>
			<span><?php esc_html_e( 'Search', 'keystone' ); ?></span>
		</button>
	</div>
</form>
