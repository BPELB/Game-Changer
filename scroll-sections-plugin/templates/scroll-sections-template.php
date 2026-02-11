<?php
/**
 * Template Name: Scroll Sections (Full Screen)
 *
 * Renders all scroll sections within the active theme's
 * header and footer so navigation and branding remain visible.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header(); ?>

<?php echo ss_render_sections(); ?>

<?php get_footer(); ?>
