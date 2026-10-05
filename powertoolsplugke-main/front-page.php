<?php
/**
 * PowerPlug homepage.
 *
 * Renders premium sections from live WooCommerce data. If a static front page
 * with content is set, that content prints above the dynamic sections.
 *
 * @package PowerPlug
 */
defined( 'ABSPATH' ) || exit;

use PowerPlug\Front\Home;

get_header();

Home::hero_row();
Home::trust_strip();

if ( is_page() && have_posts() ) {
	while ( have_posts() ) {
		the_post();
		if ( trim( (string) get_the_content() ) !== '' ) {
			echo '<div class="pp-container pp-section">';
			the_content();
			echo '</div>';
		}
	}
}

Home::featured_categories();
Home::category_sections();
Home::why_choose_us();
Home::testimonials();
Home::faq();
Home::newsletter();

get_footer();
