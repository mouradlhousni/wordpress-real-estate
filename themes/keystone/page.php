<?php
/**
 * Page template.
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
		</header>
		<div class="prose prose--page">
			<?php the_content(); ?>
		</div>
	</article>
	<?php
endwhile;

get_footer();
