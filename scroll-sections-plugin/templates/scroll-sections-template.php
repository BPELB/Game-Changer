<?php
/**
 * Template Name: Scroll Sections (Full Screen)
 *
 * A full-screen template that renders all scroll sections
 * with no theme header/footer chrome for a fully immersive experience.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php wp_title( '|', true, 'right' ); bloginfo( 'name' ); ?></title>
    <style>
        /* Reset for full-screen immersive mode */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body {
            height: 100%;
            overflow: hidden;
            background: #000;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
        }
    </style>
    <?php wp_head(); ?>
</head>
<body <?php body_class( 'ss-fullscreen' ); ?>>

    <?php echo ss_render_sections(); ?>

    <?php wp_footer(); ?>
</body>
</html>
