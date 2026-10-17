<?php
/**
 * Agent card.
 *
 * @package Keystone
 *
 * @var array<string, mixed> $args {
 *     @type array|null $agent   Normalized agent (keystone_get_agent()).
 *     @type bool       $compact       Sidebar variant (single property).
 *     @type int        $heading_level Heading level of the name (default 3).
 * }
 */

defined( 'ABSPATH' ) || exit;

$keystone_agent = $args['agent'] ?? null;

if ( ! $keystone_agent ) {
	return;
}

$keystone_compact = ! empty( $args['compact'] );
$keystone_level   = min( 6, max( 2, (int) ( $args['heading_level'] ?? 3 ) ) );
?>
<article class="agent-card<?php echo $keystone_compact ? ' agent-card--compact' : ''; ?>">
	<?php if ( ! empty( $keystone_agent['photo']['id'] ) ) : ?>
		<div class="agent-card__photo">
			<?php
			echo wp_get_attachment_image(
				(int) $keystone_agent['photo']['id'],
				'keystone-portrait',
				false,
				array(
					'sizes'   => $keystone_compact ? '96px' : '(min-width: 1000px) 280px, 50vw',
					'loading' => 'lazy',
					'alt'     => '',
				)
			);
			?>
		</div>
	<?php endif; ?>
	<div class="agent-card__body">
		<h<?php echo (int) $keystone_level; ?> class="agent-card__name"><a href="<?php echo esc_url( $keystone_agent['link'] ); ?>"><?php echo esc_html( $keystone_agent['name'] ); ?></a></h<?php echo (int) $keystone_level; ?>>
		<?php if ( $keystone_agent['position'] ) : ?>
			<p class="agent-card__role"><?php echo esc_html( $keystone_agent['position'] ); ?></p>
		<?php endif; ?>
		<ul class="agent-card__contact">
			<?php if ( $keystone_agent['phone'] ) : ?>
				<li><a href="<?php echo esc_url( 'tel:' . preg_replace( '/[^0-9+]/', '', $keystone_agent['phone'] ) ); ?>"><?php keystone_theme_the_icon( 'phone' ); ?><?php echo esc_html( $keystone_agent['phone'] ); ?></a></li>
			<?php endif; ?>
			<?php if ( $keystone_agent['email'] ) : ?>
				<li><a href="<?php echo esc_url( 'mailto:' . $keystone_agent['email'] ); ?>"><?php keystone_theme_the_icon( 'mail' ); ?><?php echo esc_html( $keystone_agent['email'] ); ?></a></li>
			<?php endif; ?>
		</ul>
	</div>
</article>
