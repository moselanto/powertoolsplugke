<?php
/**
 * Default template for static Pages (About, policies, Contact, etc.).
 *
 * @package PowerPlug
 */
defined( 'ABSPATH' ) || exit;

get_header();
?>
<div class="pp-container pp-page">
	<?php while ( have_posts() ) : the_post(); ?>
		<article <?php post_class( 'pp-page__article' ); ?>>
			<header class="pp-page__header">
				<h1 class="pp-page__title"><?php the_title(); ?></h1>
			</header>
			<div class="pp-page__content pp-richtext">
				<?php the_content(); ?>
				<?php wp_link_pages( array( 'before' => '<div class="pp-pagelinks">', 'after' => '</div>' ) ); ?>
			</div>
		</article>
	<?php endwhile; ?>
</div>
<?php
get_footer();
