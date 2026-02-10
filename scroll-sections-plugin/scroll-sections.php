<?php
/**
 * Plugin Name: Scroll Sections
 * Description: Full-screen scroll-snapping sections with parallax backgrounds and scroll-triggered animations. Inspired by ericprydz.com. Use the [scroll_sections] shortcode or the included page template.
 * Version: 1.0.0
 * Author: Brandon
 * Text Domain: scroll-sections
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'SCROLL_SECTIONS_VERSION', '1.0.0' );
define( 'SCROLL_SECTIONS_DIR', plugin_dir_path( __FILE__ ) );
define( 'SCROLL_SECTIONS_URL', plugin_dir_url( __FILE__ ) );

/**
 * Register the "scroll_section" custom post type.
 */
function ss_register_post_type() {
    $labels = array(
        'name'               => 'Scroll Sections',
        'singular_name'      => 'Scroll Section',
        'add_new'            => 'Add New Section',
        'add_new_item'       => 'Add New Scroll Section',
        'edit_item'          => 'Edit Scroll Section',
        'new_item'           => 'New Scroll Section',
        'view_item'          => 'View Scroll Section',
        'search_items'       => 'Search Scroll Sections',
        'not_found'          => 'No scroll sections found',
        'not_found_in_trash' => 'No scroll sections found in Trash',
        'menu_name'          => 'Scroll Sections',
    );

    $args = array(
        'labels'             => $labels,
        'public'             => false,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'menu_icon'          => 'dashicons-slides',
        'supports'           => array( 'title', 'editor', 'thumbnail' ),
        'has_archive'        => false,
        'rewrite'            => false,
        'show_in_rest'       => true,
    );

    register_post_type( 'scroll_section', $args );
}
add_action( 'init', 'ss_register_post_type' );

/**
 * Add meta boxes for section settings.
 */
function ss_add_meta_boxes() {
    add_meta_box(
        'ss_section_settings',
        'Section Settings',
        'ss_render_meta_box',
        'scroll_section',
        'side',
        'default'
    );
}
add_action( 'add_meta_boxes', 'ss_add_meta_boxes' );

/**
 * Render the section settings meta box.
 */
function ss_render_meta_box( $post ) {
    wp_nonce_field( 'ss_save_meta', 'ss_meta_nonce' );

    $order          = get_post_meta( $post->ID, '_ss_order', true );
    $bg_type        = get_post_meta( $post->ID, '_ss_bg_type', true ) ?: 'image';
    $bg_video       = get_post_meta( $post->ID, '_ss_bg_video', true );
    $overlay        = get_post_meta( $post->ID, '_ss_overlay', true ) ?: '0.5';
    $overlay_color  = get_post_meta( $post->ID, '_ss_overlay_color', true ) ?: '#000000';
    $animation      = get_post_meta( $post->ID, '_ss_animation', true ) ?: 'fade-up';
    $text_align     = get_post_meta( $post->ID, '_ss_text_align', true ) ?: 'center';
    $content_width  = get_post_meta( $post->ID, '_ss_content_width', true ) ?: '800';
    $parallax_speed = get_post_meta( $post->ID, '_ss_parallax_speed', true ) ?: '0.3';
    ?>
    <p>
        <label for="ss_order"><strong>Display Order</strong></label><br>
        <input type="number" id="ss_order" name="ss_order" value="<?php echo esc_attr( $order ); ?>" min="0" step="1" style="width:100%;">
    </p>

    <p>
        <label for="ss_bg_type"><strong>Background Type</strong></label><br>
        <select id="ss_bg_type" name="ss_bg_type" style="width:100%;">
            <option value="image" <?php selected( $bg_type, 'image' ); ?>>Featured Image</option>
            <option value="video" <?php selected( $bg_type, 'video' ); ?>>Video URL</option>
            <option value="color" <?php selected( $bg_type, 'color' ); ?>>Solid Color</option>
        </select>
    </p>

    <p id="ss_video_field" style="<?php echo $bg_type !== 'video' ? 'display:none;' : ''; ?>">
        <label for="ss_bg_video"><strong>Video URL (MP4)</strong></label><br>
        <input type="url" id="ss_bg_video" name="ss_bg_video" value="<?php echo esc_url( $bg_video ); ?>" style="width:100%;" placeholder="https://example.com/video.mp4">
    </p>

    <p>
        <label for="ss_overlay"><strong>Overlay Opacity (0-1)</strong></label><br>
        <input type="number" id="ss_overlay" name="ss_overlay" value="<?php echo esc_attr( $overlay ); ?>" min="0" max="1" step="0.05" style="width:100%;">
    </p>

    <p>
        <label for="ss_overlay_color"><strong>Overlay Color</strong></label><br>
        <input type="color" id="ss_overlay_color" name="ss_overlay_color" value="<?php echo esc_attr( $overlay_color ); ?>" style="width:100%;">
    </p>

    <p>
        <label for="ss_animation"><strong>Content Animation</strong></label><br>
        <select id="ss_animation" name="ss_animation" style="width:100%;">
            <option value="fade-up" <?php selected( $animation, 'fade-up' ); ?>>Fade Up</option>
            <option value="fade-down" <?php selected( $animation, 'fade-down' ); ?>>Fade Down</option>
            <option value="fade-left" <?php selected( $animation, 'fade-left' ); ?>>Fade Left</option>
            <option value="fade-right" <?php selected( $animation, 'fade-right' ); ?>>Fade Right</option>
            <option value="zoom-in" <?php selected( $animation, 'zoom-in' ); ?>>Zoom In</option>
            <option value="blur-in" <?php selected( $animation, 'blur-in' ); ?>>Blur In</option>
            <option value="none" <?php selected( $animation, 'none' ); ?>>None</option>
        </select>
    </p>

    <p>
        <label for="ss_text_align"><strong>Text Alignment</strong></label><br>
        <select id="ss_text_align" name="ss_text_align" style="width:100%;">
            <option value="left" <?php selected( $text_align, 'left' ); ?>>Left</option>
            <option value="center" <?php selected( $text_align, 'center' ); ?>>Center</option>
            <option value="right" <?php selected( $text_align, 'right' ); ?>>Right</option>
        </select>
    </p>

    <p>
        <label for="ss_content_width"><strong>Content Max Width (px)</strong></label><br>
        <input type="number" id="ss_content_width" name="ss_content_width" value="<?php echo esc_attr( $content_width ); ?>" min="300" max="1600" step="50" style="width:100%;">
    </p>

    <p>
        <label for="ss_parallax_speed"><strong>Parallax Speed (0-1)</strong></label><br>
        <input type="number" id="ss_parallax_speed" name="ss_parallax_speed" value="<?php echo esc_attr( $parallax_speed ); ?>" min="0" max="1" step="0.05" style="width:100%;">
        <small>0 = no parallax, 1 = maximum parallax</small>
    </p>

    <script>
    jQuery(function($){
        $('#ss_bg_type').on('change', function(){
            $('#ss_video_field').toggle($(this).val() === 'video');
        });
    });
    </script>
    <?php
}

/**
 * Save meta box data.
 */
function ss_save_meta( $post_id ) {
    if ( ! isset( $_POST['ss_meta_nonce'] ) || ! wp_verify_nonce( $_POST['ss_meta_nonce'], 'ss_save_meta' ) ) {
        return;
    }
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    $fields = array(
        'ss_order'          => '_ss_order',
        'ss_bg_type'        => '_ss_bg_type',
        'ss_bg_video'       => '_ss_bg_video',
        'ss_overlay'        => '_ss_overlay',
        'ss_overlay_color'  => '_ss_overlay_color',
        'ss_animation'      => '_ss_animation',
        'ss_text_align'     => '_ss_text_align',
        'ss_content_width'  => '_ss_content_width',
        'ss_parallax_speed' => '_ss_parallax_speed',
    );

    foreach ( $fields as $field => $meta_key ) {
        if ( isset( $_POST[ $field ] ) ) {
            $value = sanitize_text_field( wp_unslash( $_POST[ $field ] ) );
            update_post_meta( $post_id, $meta_key, $value );
        }
    }
}
add_action( 'save_post_scroll_section', 'ss_save_meta' );

/**
 * Enqueue frontend assets.
 */
function ss_enqueue_assets() {
    if ( ! ss_should_load_assets() ) {
        return;
    }

    wp_enqueue_style(
        'scroll-sections',
        SCROLL_SECTIONS_URL . 'assets/css/scroll-sections.css',
        array(),
        SCROLL_SECTIONS_VERSION
    );

    wp_enqueue_script(
        'scroll-sections',
        SCROLL_SECTIONS_URL . 'assets/js/scroll-sections.js',
        array(),
        SCROLL_SECTIONS_VERSION,
        true
    );
}
add_action( 'wp_enqueue_scripts', 'ss_enqueue_assets' );

/**
 * Determine if assets should load on the current page.
 */
function ss_should_load_assets() {
    global $post;

    if ( is_page_template( 'templates/scroll-sections-template.php' ) ) {
        return true;
    }

    if ( $post && has_shortcode( $post->post_content, 'scroll_sections' ) ) {
        return true;
    }

    return false;
}

/**
 * Query all published scroll sections ordered by the display order meta.
 */
function ss_get_sections() {
    $args = array(
        'post_type'      => 'scroll_section',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'meta_key'       => '_ss_order',
        'orderby'        => 'meta_value_num',
        'order'          => 'ASC',
    );

    return new WP_Query( $args );
}

/**
 * Render scroll sections HTML.
 */
function ss_render_sections() {
    $sections = ss_get_sections();

    if ( ! $sections->have_posts() ) {
        return '<p>No scroll sections found. Add sections in the WordPress admin under Scroll Sections.</p>';
    }

    $output = '<div class="ss-container" id="ss-container">';

    // Navigation dots
    $output .= '<nav class="ss-nav" id="ss-nav" aria-label="Section navigation"><ul>';
    $i = 0;
    while ( $sections->have_posts() ) {
        $sections->the_post();
        $title = get_the_title();
        $active = $i === 0 ? ' ss-nav-active' : '';
        $output .= '<li><button class="ss-nav-dot' . $active . '" data-index="' . $i . '" aria-label="' . esc_attr( $title ) . '"><span class="ss-nav-tooltip">' . esc_html( $title ) . '</span></button></li>';
        $i++;
    }
    $output .= '</ul></nav>';

    // Sections
    $sections->rewind_posts();
    $i = 0;
    while ( $sections->have_posts() ) {
        $sections->the_post();
        $post_id = get_the_ID();

        $bg_type        = get_post_meta( $post_id, '_ss_bg_type', true ) ?: 'image';
        $bg_video       = get_post_meta( $post_id, '_ss_bg_video', true );
        $overlay        = get_post_meta( $post_id, '_ss_overlay', true ) ?: '0.5';
        $overlay_color  = get_post_meta( $post_id, '_ss_overlay_color', true ) ?: '#000000';
        $animation      = get_post_meta( $post_id, '_ss_animation', true ) ?: 'fade-up';
        $text_align     = get_post_meta( $post_id, '_ss_text_align', true ) ?: 'center';
        $content_width  = get_post_meta( $post_id, '_ss_content_width', true ) ?: '800';
        $parallax_speed = get_post_meta( $post_id, '_ss_parallax_speed', true ) ?: '0.3';

        $bg_image_url = '';
        if ( $bg_type === 'image' && has_post_thumbnail( $post_id ) ) {
            $bg_image_url = get_the_post_thumbnail_url( $post_id, 'full' );
        }

        // Build section data attributes
        $data_attrs = sprintf(
            'data-animation="%s" data-parallax-speed="%s" data-index="%d"',
            esc_attr( $animation ),
            esc_attr( $parallax_speed ),
            $i
        );

        // Overlay style
        $overlay_rgba = ss_hex_to_rgba( $overlay_color, $overlay );

        $output .= '<section class="ss-section" ' . $data_attrs . '>';

        // Background layer
        if ( $bg_type === 'video' && $bg_video ) {
            $output .= '<div class="ss-bg ss-bg-video">';
            $output .= '<video autoplay muted loop playsinline><source src="' . esc_url( $bg_video ) . '" type="video/mp4"></video>';
            $output .= '</div>';
        } elseif ( $bg_type === 'image' && $bg_image_url ) {
            $output .= '<div class="ss-bg ss-bg-image" style="background-image:url(' . esc_url( $bg_image_url ) . ');"></div>';
        } else {
            $output .= '<div class="ss-bg ss-bg-color" style="background-color:' . esc_attr( $overlay_color ) . ';"></div>';
        }

        // Overlay
        $output .= '<div class="ss-overlay" style="background-color:' . esc_attr( $overlay_rgba ) . ';"></div>';

        // Content
        $output .= '<div class="ss-content ss-anim" style="text-align:' . esc_attr( $text_align ) . ';max-width:' . intval( $content_width ) . 'px;">';
        $output .= '<h2 class="ss-title">' . esc_html( get_the_title() ) . '</h2>';
        $output .= '<div class="ss-body">' . wp_kses_post( apply_filters( 'the_content', get_the_content() ) ) . '</div>';
        $output .= '</div>';

        $output .= '</section>';
        $i++;
    }

    wp_reset_postdata();

    $output .= '</div>'; // .ss-container

    return $output;
}

/**
 * Shortcode: [scroll_sections]
 */
function ss_shortcode( $atts ) {
    return ss_render_sections();
}
add_shortcode( 'scroll_sections', 'ss_shortcode' );

/**
 * Convert hex color to rgba string.
 */
function ss_hex_to_rgba( $hex, $alpha ) {
    $hex = ltrim( $hex, '#' );
    if ( strlen( $hex ) === 3 ) {
        $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
    }
    $r = hexdec( substr( $hex, 0, 2 ) );
    $g = hexdec( substr( $hex, 2, 2 ) );
    $b = hexdec( substr( $hex, 4, 2 ) );
    return sprintf( 'rgba(%d,%d,%d,%s)', $r, $g, $b, floatval( $alpha ) );
}

/**
 * Register the page template.
 */
function ss_register_template( $templates ) {
    $templates['templates/scroll-sections-template.php'] = 'Scroll Sections (Full Screen)';
    return $templates;
}
add_filter( 'theme_page_templates', 'ss_register_template' );

/**
 * Load the page template from the plugin.
 */
function ss_load_template( $template ) {
    if ( is_page() ) {
        $page_template = get_page_template_slug();
        if ( $page_template === 'templates/scroll-sections-template.php' ) {
            $plugin_template = SCROLL_SECTIONS_DIR . 'templates/scroll-sections-template.php';
            if ( file_exists( $plugin_template ) ) {
                return $plugin_template;
            }
        }
    }
    return $template;
}
add_filter( 'template_include', 'ss_load_template' );

/**
 * Add admin columns for display order.
 */
function ss_admin_columns( $columns ) {
    $new = array();
    foreach ( $columns as $key => $val ) {
        $new[ $key ] = $val;
        if ( $key === 'title' ) {
            $new['ss_order']     = 'Order';
            $new['ss_animation'] = 'Animation';
            $new['ss_bg_type']   = 'Background';
        }
    }
    return $new;
}
add_filter( 'manage_scroll_section_posts_columns', 'ss_admin_columns' );

function ss_admin_column_content( $column, $post_id ) {
    switch ( $column ) {
        case 'ss_order':
            echo esc_html( get_post_meta( $post_id, '_ss_order', true ) ?: '0' );
            break;
        case 'ss_animation':
            echo esc_html( get_post_meta( $post_id, '_ss_animation', true ) ?: 'fade-up' );
            break;
        case 'ss_bg_type':
            echo esc_html( ucfirst( get_post_meta( $post_id, '_ss_bg_type', true ) ?: 'image' ) );
            break;
    }
}
add_action( 'manage_scroll_section_posts_custom_column', 'ss_admin_column_content', 10, 2 );

function ss_sortable_columns( $columns ) {
    $columns['ss_order'] = 'ss_order';
    return $columns;
}
add_filter( 'manage_edit-scroll_section_sortable_columns', 'ss_sortable_columns' );

function ss_orderby( $query ) {
    if ( ! is_admin() || ! $query->is_main_query() ) {
        return;
    }
    if ( $query->get( 'orderby' ) === 'ss_order' ) {
        $query->set( 'meta_key', '_ss_order' );
        $query->set( 'orderby', 'meta_value_num' );
    }
}
add_action( 'pre_get_posts', 'ss_orderby' );
