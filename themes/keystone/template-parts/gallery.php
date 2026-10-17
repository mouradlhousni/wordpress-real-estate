<?php
/**
 * Property gallery mosaic. Each image links to its full size (works without JavaScript);
 * gallery.js upgrades the links to an accessible lightbox.
 *
 * @package Keystone
 *
 * @var array<string, mixed> $args { @type int[] $images Attachment IDs, cover first. }
 */

defined( 'ABSPATH' ) || exit;

$keystone_images = array_map( 'absint', $args['images'] ?? array() );
$keystone_total  = count( $keystone_images );

if ( ! $keystone_total ) {
	return;
}
?>
<div class="gallery" data-gallery>
	<ul class="gallery__grid gallery__grid--<?php echo esc_attr( (string) min( 5, $keystone_total ) ); ?>">
		<?php foreach ( array_slice( $keystone_images, 0, 5 ) as $keystone_index => $keystone_id ) : ?>
			<?php
			$keystone_full = wp_get_attachment_image_src( $keystone_id, 'full' );
			if ( ! $keystone_full ) {
				continue;
			}
			$keystone_alt = (string) get_post_meta( $keystone_id, '_wp_attachment_image_alt', true );
			?>
			<li class="gallery__item">
				<a
					href="<?php echo esc_url( $keystone_full[0] ); ?>"
					data-gallery-index="<?php echo esc_attr( (string) $keystone_index ); ?>"
					aria-label="<?php echo esc_attr( sprintf( /* translators: 1: photo number, 2: total photos. */ __( 'Open photo %1$d of %2$d', 'keystone' ), $keystone_index + 1, $keystone_total ) ); ?>"
				>
					<?php
					echo wp_get_attachment_image(
						$keystone_id,
						0 === $keystone_index ? 'large' : 'keystone-card',
						false,
						array(
							'sizes'         => 0 === $keystone_index ? '(min-width: 1000px) 60vw, 100vw' : '(min-width: 1000px) 20vw, 50vw',
							'loading'       => 0 === $keystone_index ? 'eager' : 'lazy',
							'fetchpriority' => 0 === $keystone_index ? 'high' : 'auto',
							'decoding'      => 'async',
						)
					);
					?>
				</a>
			</li>
		<?php endforeach; ?>
	</ul>

	<?php if ( $keystone_total > 1 ) : ?>
		<button class="button button--small gallery__all" type="button" data-gallery-open hidden>
			<?php keystone_theme_the_icon( 'expand' ); ?>
			<?php
			/* translators: %d: number of photos. */
			echo esc_html( sprintf( _n( 'View %d photo', 'View all %d photos', $keystone_total, 'keystone' ), $keystone_total ) );
			?>
		</button>
	<?php endif; ?>

	<script type="application/json" data-gallery-data>
		<?php
		$keystone_data = array();
		foreach ( $keystone_images as $keystone_id ) {
			$keystone_src = wp_get_attachment_image_src( $keystone_id, 'full' );
			if ( $keystone_src ) {
				$keystone_data[] = array(
					'src'    => $keystone_src[0],
					'srcset' => (string) wp_get_attachment_image_srcset( $keystone_id, 'full' ),
					'width'  => (int) $keystone_src[1],
					'height' => (int) $keystone_src[2],
					'alt'    => (string) get_post_meta( $keystone_id, '_wp_attachment_image_alt', true ),
				);
			}
		}
		echo wp_json_encode( $keystone_data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_SLASHES );
		?>
	</script>
</div>
