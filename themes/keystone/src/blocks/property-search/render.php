<?php
/**
 * Server render of keystone/property-search.
 *
 * @package Keystone
 *
 * @var array<string, mixed> $attributes Block attributes.
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'keystone_search_properties' ) ) {
	return;
}
?>
<div <?php echo get_block_wrapper_attributes(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core-escaped attributes. ?>>
	<?php
	get_template_part(
		'template-parts/search-bar',
		null,
		array(
			'id'      => wp_unique_id( 'block-search-' ),
			'variant' => 'block',
		)
	);
	?>
</div>
