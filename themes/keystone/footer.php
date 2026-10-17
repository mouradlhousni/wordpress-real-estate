<?php
/**
 * Site footer.
 *
 * @package Keystone
 */

defined( 'ABSPATH' ) || exit;

$keystone_cities = taxonomy_exists( 'property_city' ) ? keystone_theme_terms( 'property_city' ) : array();
?>
</main>

<footer class="site-footer">
	<div class="container">
		<div class="site-footer__top">
			<div class="site-footer__brand">
				<p class="site-footer__name"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></p>
				<p class="site-footer__tagline"><?php echo esc_html( get_bloginfo( 'description' ) ); ?></p>
				<?php if ( post_type_exists( 'property' ) ) : ?>
					<a class="button button--light button--go" href="<?php echo esc_url( keystone_theme_search_url() ); ?>">
						<?php esc_html_e( 'Find a property', 'keystone' ); ?>
						<span class="button__go"><?php keystone_theme_the_icon( 'go' ); ?></span>
					</a>
				<?php endif; ?>
			</div>

			<?php if ( $keystone_cities ) : ?>
				<nav class="site-footer__col" aria-label="<?php esc_attr_e( 'Cities', 'keystone' ); ?>">
					<p class="site-footer__heading"><?php esc_html_e( 'Cities', 'keystone' ); ?></p>
					<ul>
						<?php foreach ( $keystone_cities as $keystone_city ) : ?>
							<li><a href="<?php echo esc_url( (string) get_term_link( $keystone_city ) ); ?>"><?php echo esc_html( $keystone_city->name ); ?></a></li>
						<?php endforeach; ?>
					</ul>
				</nav>
			<?php endif; ?>

			<nav class="site-footer__col" aria-label="<?php esc_attr_e( 'Explore', 'keystone' ); ?>">
				<p class="site-footer__heading"><?php esc_html_e( 'Explore', 'keystone' ); ?></p>
				<ul>
					<?php if ( post_type_exists( 'property' ) ) : ?>
						<li><a href="<?php echo esc_url( keystone_theme_search_url( array( 'status' => 'for-sale' ) ) ); ?>"><?php esc_html_e( 'Homes for sale', 'keystone' ); ?></a></li>
						<li><a href="<?php echo esc_url( keystone_theme_search_url( array( 'status' => 'for-rent' ) ) ); ?>"><?php esc_html_e( 'Homes for rent', 'keystone' ); ?></a></li>
						<li><a href="<?php echo esc_url( (string) get_post_type_archive_link( 'agent' ) ); ?>"><?php esc_html_e( 'Our agents', 'keystone' ); ?></a></li>
					<?php endif; ?>
				</ul>
			</nav>
		</div>

		<p class="site-footer__wordmark" aria-hidden="true"><?php echo esc_html( strtok( get_bloginfo( 'name' ), ' ' ) ); ?></p>

		<div class="site-footer__legal">
			<p>
				<?php
				/* translators: %s: year. */
				printf( esc_html__( '© %s Keystone Realty — a fictitious agency built as a WordPress portfolio project.', 'keystone' ), esc_html( gmdate( 'Y' ) ) );
				?>
			</p>
		</div>
	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
