<?php
/**
 * Template Name: Landing Page — Category (Ads)
 *
 * Conversion-focused, mobile-first landing page for a single product category,
 * built for Meta (Facebook/Instagram) ad traffic. Pulls live WooCommerce
 * products (price, stock, image) from the category chosen in the
 * "Landing Page (Ads) settings" box. Drives the real cart/checkout (COD) plus
 * a pre-filled WhatsApp order. One template powers every category page.
 *
 * @package PowerPlug
 */

defined( 'ABSPATH' ) || exit;

get_header();

\PowerPlug\Front\LandingPage::render();

get_footer();
