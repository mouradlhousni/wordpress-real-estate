<?php
/**
 * Compact specs row: bedrooms, bathrooms, surface.
 *
 * @package Keystone
 *
 * @var array<string, mixed> $args {
 *     @type array  $property Normalized property.
 *     @type string $variant  Optional modifier, e.g. "tiles".
 * }
 */

defined( 'ABSPATH' ) || exit;

$keystone_p = $args['property'] ?? array();
$keystone_v = sanitize_html_class( $args['variant'] ?? '' );
?>
<ul class="specs<?php echo $keystone_v ? ' specs--' . esc_attr( $keystone_v ) : ''; ?>">
	<?php if ( ! empty( $keystone_p['bedrooms'] ) ) : ?>
		<li>
			<?php keystone_theme_the_icon( 'bed' ); ?>
			<?php
			/* translators: %d: number of bedrooms. */
			echo esc_html( sprintf( _n( '%d bed', '%d beds', (int) $keystone_p['bedrooms'], 'keystone' ), (int) $keystone_p['bedrooms'] ) );
			?>
		</li>
	<?php endif; ?>
	<?php if ( ! empty( $keystone_p['bathrooms'] ) ) : ?>
		<li>
			<?php keystone_theme_the_icon( 'bath' ); ?>
			<?php
			/* translators: %d: number of bathrooms. */
			echo esc_html( sprintf( _n( '%d bath', '%d baths', (int) $keystone_p['bathrooms'], 'keystone' ), (int) $keystone_p['bathrooms'] ) );
			?>
		</li>
	<?php endif; ?>
	<?php if ( ! empty( $keystone_p['surface'] ) ) : ?>
		<li>
			<?php keystone_theme_the_icon( 'area' ); ?>
			<?php echo esc_html( number_format_i18n( (float) $keystone_p['surface'] ) . ' m²' ); ?>
		</li>
	<?php endif; ?>
</ul>
