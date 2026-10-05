<?php
/**
 * @package PowerPlug
 */
defined( 'ABSPATH' ) || exit;
get_header();
?>
<div class="pp-container">
	<?php if ( have_posts() ) : ?>
		<div class="pp-posts">
			<?php while ( have_posts() ) : the_post(); ?>
				<article <?php post_class( 'pp-post' ); ?>>
					<h2 class="pp-post__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
					<div class="pp-post__excerpt"><?php the_excerpt(); ?></div>
				</article>
			<?php endwhile; ?>
		</div>
		<?php the_posts_pagination(); ?>
	<?php else : ?>
		<p><?php esc_html_e( 'Nothing found.', 'powerplug' ); ?></p>
	<?php endif; ?>
</div>
<?php
get_footer();
