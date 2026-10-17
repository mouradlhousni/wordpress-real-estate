<?php
/**
 * Fallback template (blog posts, search results, generic archives).
 *
 * @package Keystone
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<div class="container section">
	<header class="page-head">
		<h1 class="page-head__title">
			<?php
			if ( is_search() ) {
				/* translators: %s: search query. */
				printf( esc_html__( 'Results for “%s”', 'keystone' ), esc_html( get_search_query() ) );
			} elseif ( is_archive() ) {
				echo esc_html( wp_strip_all_tags( get_the_archive_title() ) );
			} else {
				esc_html_e( 'Journal', 'keystone' );
			}
			?>
		</h1>
	</header>

	<?php if ( have_posts() ) : ?>
		<div class="post-list">
			<?php
			while ( have_posts() ) :
				the_post();
				?>
				<article <?php post_class( 'post-summary' ); ?>>
					<h2 class="post-summary__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
					<p class="post-summary__meta"><?php echo esc_html( get_the_date() ); ?></p>
					<div class="prose"><?php the_excerpt(); ?></div>
				</article>
			<?php endwhile; ?>
		</div>
		<?php
		the_posts_pagination(
			array(
				'prev_text' => __( 'Previous', 'keystone' ),
				'next_text' => __( 'Next', 'keystone' ),
			)
		);
		?>
	<?php else : ?>
		<p><?php esc_html_e( 'Nothing here yet.', 'keystone' ); ?></p>
	<?php endif; ?>
</div>
<?php
get_footer();
