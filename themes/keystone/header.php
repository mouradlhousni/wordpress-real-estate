<?php
/**
 * Site header.
 *
 * @package Keystone
 */

defined( 'ABSPATH' ) || exit;
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="theme-color" content="#16130f">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?><?php echo ( keystone_theme_has_plugin() && keystone_is_static_build() ) ? ' data-static-build="1"' : ''; ?>>
<?php wp_body_open(); ?>
<a class="skip-link" href="#content"><?php esc_html_e( 'Skip to content', 'keystone' ); ?></a>

<header class="site-header">
	<div class="container site-header__inner">
		<a class="brand" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
			<span class="brand__mark" aria-hidden="true">
				<svg viewBox="0 0 24 24" width="20" height="20" focusable="false"><path d="M4 21V10.5L12 4l8 6.5V21h-5.5v-6h-5v6Z" fill="currentColor"/></svg>
			</span>
			<span class="brand__name"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></span>
		</a>

		<button class="nav-toggle" type="button" aria-expanded="false" aria-controls="primary-nav">
			<?php keystone_theme_the_icon( 'menu' ); ?>
			<span class="screen-reader-text"><?php esc_html_e( 'Menu', 'keystone' ); ?></span>
		</button>

		<nav class="primary-nav" id="primary-nav" aria-label="<?php esc_attr_e( 'Primary', 'keystone' ); ?>">
			<?php
			if ( has_nav_menu( 'primary' ) ) {
				wp_nav_menu(
					array(
						'theme_location' => 'primary',
						'container'      => false,
						'menu_class'     => 'primary-nav__list',
						'depth'          => 1,
					)
				);
			} else {
				echo '<ul class="primary-nav__list">';
				if ( post_type_exists( 'property' ) ) {
					printf( '<li><a href="%1$s">%2$s</a></li>', esc_url( (string) get_post_type_archive_link( 'property' ) ), esc_html__( 'Properties', 'keystone' ) );
					printf( '<li><a href="%1$s">%2$s</a></li>', esc_url( (string) get_post_type_archive_link( 'agent' ) ), esc_html__( 'Agents', 'keystone' ) );
				}
				echo '</ul>';
			}
			?>
			<?php if ( post_type_exists( 'property' ) ) : ?>
				<a class="button button--small button--go primary-nav__cta" href="<?php echo esc_url( (string) get_post_type_archive_link( 'property' ) ); ?>">
					<?php esc_html_e( 'Search homes', 'keystone' ); ?>
					<span class="button__go"><?php keystone_theme_the_icon( 'go' ); ?></span>
				</a>
			<?php endif; ?>
		</nav>
	</div>
</header>

<main id="content" class="site-main" tabindex="-1">
