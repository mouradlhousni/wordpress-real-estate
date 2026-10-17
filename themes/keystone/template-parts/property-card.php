<?php
/**
 * Property card.
 *
 * The same markup is produced client-side by assets/js/search.js (renderCard): keep both in sync.
 *
 * @package Keystone
 *
 * @var array<string, mixed> $args {
 *     @type array $property      Normalized property.
 *     @type int   $heading_level Heading level.
 *     @type bool  $eager         Load the image eagerly (above the fold).
 * }
 */

defined( 'ABSPATH' ) || exit;

$keystone_p     = $args['property'] ?? array();
$keystone_level = min( 6, max( 2, (int) ( $args['heading_level'] ?? 3 ) ) );

if ( ! $keystone_p ) {
	return;
}

$keystone_status = $keystone_p['status']['slug'] ?? '';
$keystone_photos = (int) ( $keystone_p['photo_count'] ?? 0 );
$keystone_agent  = $keystone_p['agent_brief'] ?? null;
?>
<article class="property-card" data-property-id="<?php echo esc_attr( (string) $keystone_p['id'] ); ?>" data-type="<?php echo esc_attr( $keystone_p['type']['slug'] ?? '' ); ?>">
	<div class="property-card__media">
		<?php
		if ( ! empty( $keystone_p['image']['id'] ) ) {
			echo wp_get_attachment_image(
				(int) $keystone_p['image']['id'],
				'keystone-card',
				false,
				array(
					'class'         => 'property-card__img',
					'sizes'         => '(min-width: 1200px) 380px, (min-width: 700px) 50vw, 100vw',
					'loading'       => empty( $args['eager'] ) ? 'lazy' : 'eager',
					'fetchpriority' => empty( $args['eager'] ) ? 'auto' : 'high',
					'decoding'      => 'async',
				)
			);
		}
		?>
		<span class="property-card__go" aria-hidden="true"><?php keystone_theme_the_icon( 'go' ); ?></span>
		<?php if ( $keystone_photos > 1 ) : ?>
			<span class="property-card__photos"><?php keystone_theme_the_icon( 'camera' ); ?><?php echo esc_html( (string) $keystone_photos ); ?><span class="screen-reader-text"> <?php esc_html_e( 'photos', 'keystone' ); ?></span></span>
		<?php endif; ?>
		<?php if ( ! empty( $keystone_agent['name'] ) ) : ?>
			<span class="property-card__agent">
				<?php if ( ! empty( $keystone_agent['avatar'] ) ) : ?>
					<img src="<?php echo esc_url( $keystone_agent['avatar'] ); ?>" width="32" height="32" alt="" loading="lazy" decoding="async">
				<?php endif; ?>
				<span><?php echo esc_html( $keystone_agent['name'] ); ?></span>
			</span>
		<?php endif; ?>
	</div>

	<div class="property-card__body">
		<p class="property-card__meta">
			<?php if ( ! empty( $keystone_p['status']['name'] ) ) : ?>
				<span class="badge badge--<?php echo esc_attr( $keystone_status ); ?>"><?php echo esc_html( $keystone_p['status']['name'] ); ?></span>
			<?php endif; ?>
			<?php if ( ! empty( $keystone_p['type']['name'] ) ) : ?>
				<span class="property-card__type"><?php echo esc_html( $keystone_p['type']['name'] ); ?></span>
			<?php endif; ?>
		</p>
		<p class="property-card__price"><?php echo esc_html( $keystone_p['price_formatted'] ); ?></p>
		<h<?php echo (int) $keystone_level; ?> class="property-card__title">
			<a class="property-card__link" href="<?php echo esc_url( $keystone_p['link'] ); ?>"><?php echo esc_html( $keystone_p['title'] ); ?></a>
		</h<?php echo (int) $keystone_level; ?>>
		<p class="property-card__place">
			<?php keystone_theme_the_icon( 'pin' ); ?>
			<?php echo esc_html( $keystone_p['city']['name'] ?? '' ); ?>
		</p>
		<?php get_template_part( 'template-parts/specs', null, array( 'property' => $keystone_p ) ); ?>
	</div>
</article>
