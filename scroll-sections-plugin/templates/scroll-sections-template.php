<?php
/**
 * Template Name: Scroll Sections (Full Screen)
 *
 * Uses the theme's header and footer so site navigation stays visible.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

get_header();

echo ss_render_sections();

get_footer();
