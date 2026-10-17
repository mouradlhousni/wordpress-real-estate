<?php
/**
 * Single blog post.
 *
 * @package Keystone
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	?>
	<article <?php post_class( 'container section page' ); ?>>
		<header class="page-head">
			<h1 class="page-head__title"><?php the_title(); ?></h1>
			<p class="post-summary__meta"><?php echo esc_html( get_the_date() ); ?></p>
		</header>
		<div class="prose prose--page">
			<?php the_content(); ?>
		</div>
	</article>
	<?php
endwhile;

get_footer();
