<?php
/**
 * Server render of keystone/featured-properties.
 *
 * @package Keystone
 *
 * @var array<string, mixed> $attributes Block attributes.
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'keystone_search_properties' ) ) {
	return;
}

$keystone_block_items = keystone_search_properties(
	array(
		'per_page' => max( 1, min( 12, (int) ( $attributes['count'] ?? 3 ) ) ),
		'city'     => (string) ( $attributes['city'] ?? '' ),
		'status'   => (string) ( $attributes['status'] ?? '' ),
		'featured' => ! empty( $attributes['featuredOnly'] ),
	)
)['items'];

if ( ! $keystone_block_items ) {
	printf(
		'<p %1$s>%2$s</p>',
		get_block_wrapper_attributes(), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core-escaped attributes.
		esc_html__( 'No properties match these settings yet.', 'keystone' )
	);
	return;
}
?>
<div <?php echo get_block_wrapper_attributes( array( 'class' => 'card-grid' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core-escaped attributes. ?>>
	<?php
	foreach ( $keystone_block_items as $keystone_block_item ) {
		keystone_theme_property_card( $keystone_block_item );
	}
	?>
</div>
