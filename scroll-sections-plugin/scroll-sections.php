<?php
/**
 * Plugin Name: Scroll Sections
 * Description: Cinematic full-screen sections with smooth inertia scrolling, parallax backgrounds, inline video embeds, and scroll-triggered animations. Inspired by ericprydz.com. Use the [scroll_sections] shortcode or the included page template.
 * Version: 3.0.0
 * Author: Brandon
 * Text Domain: scroll-sections
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'SCROLL_SECTIONS_VERSION', '3.0.0' );
define( 'SCROLL_SECTIONS_DIR', plugin_dir_path( __FILE__ ) );
define( 'SCROLL_SECTIONS_URL', plugin_dir_url( __FILE__ ) );

/* ==========================================================================
   Custom Post Type
   ========================================================================== */

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

    register_post_type( 'scroll_section', array(
        'labels'       => $labels,
        'public'       => false,
        'show_ui'      => true,
        'show_in_menu' => true,
        'menu_icon'    => 'dashicons-slides',
        'supports'     => array( 'title', 'editor', 'thumbnail' ),
        'has_archive'  => false,
        'rewrite'      => false,
        'show_in_rest' => true,
    ) );
}
add_action( 'init', 'ss_register_post_type' );

function ss_activate() {
    ss_register_post_type();
    flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'ss_activate' );

function ss_deactivate() {
    flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'ss_deactivate' );

/* ==========================================================================
   Meta Boxes
   ========================================================================== */

function ss_add_meta_boxes() {
    add_meta_box( 'ss_section_settings', 'Section Settings', 'ss_render_meta_box', 'scroll_section', 'normal', 'high' );
}
add_action( 'add_meta_boxes', 'ss_add_meta_boxes' );

function ss_render_meta_box( $post ) {
    wp_nonce_field( 'ss_save_meta', 'ss_meta_nonce' );

    $m = array(
        'order'           => get_post_meta( $post->ID, '_ss_order', true ),
        'bg_type'         => get_post_meta( $post->ID, '_ss_bg_type', true ) ?: 'image',
        'bg_video'        => get_post_meta( $post->ID, '_ss_bg_video', true ),
        'overlay'         => get_post_meta( $post->ID, '_ss_overlay', true ) ?: '0.4',
        'overlay_color'   => get_post_meta( $post->ID, '_ss_overlay_color', true ) ?: '#000000',
        'animation'       => get_post_meta( $post->ID, '_ss_animation', true ) ?: 'fade-up',
        'anim_duration'   => get_post_meta( $post->ID, '_ss_anim_duration', true ) ?: '1.2',
        'anim_delay'      => get_post_meta( $post->ID, '_ss_anim_delay', true ) ?: '0',
        'anim_stagger'    => get_post_meta( $post->ID, '_ss_anim_stagger', true ) ?: '0.15',
        'anim_easing'     => get_post_meta( $post->ID, '_ss_anim_easing', true ) ?: 'cubic-bezier(0.25, 0.46, 0.45, 0.94)',
        'text_align'      => get_post_meta( $post->ID, '_ss_text_align', true ) ?: 'center',
        'content_width'   => get_post_meta( $post->ID, '_ss_content_width', true ) ?: '900',
        'content_position'=> get_post_meta( $post->ID, '_ss_content_position', true ) ?: 'center',
        'parallax_speed'  => get_post_meta( $post->ID, '_ss_parallax_speed', true ) ?: '0.3',
        'video_autoplay'  => get_post_meta( $post->ID, '_ss_video_autoplay', true ) ?: 'on_scroll',
        'video_layout'    => get_post_meta( $post->ID, '_ss_video_layout', true ) ?: 'stack',
        'section_height'  => get_post_meta( $post->ID, '_ss_section_height', true ) ?: '100',
        'title_visible'   => get_post_meta( $post->ID, '_ss_title_visible', true ) ?: 'yes',
    );

    // Videos stored as JSON array — migrate old single-video field
    $videos_raw = get_post_meta( $post->ID, '_ss_videos', true );
    $videos = $videos_raw ? json_decode( $videos_raw, true ) : array();
    if ( ! is_array( $videos ) ) $videos = array();

    // Migrate legacy single inline_video field
    $legacy = get_post_meta( $post->ID, '_ss_inline_video', true );
    if ( $legacy && empty( $videos ) ) {
        $legacy_width = get_post_meta( $post->ID, '_ss_video_width', true ) ?: '800';
        $videos = array( array( 'url' => $legacy, 'label' => '', 'width' => $legacy_width ) );
    }
    ?>
    <style>
        .ss-meta-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px 24px; }
        .ss-meta-grid .ss-full { grid-column: 1 / -1; }
        .ss-meta-grid label { font-weight: 600; display: block; margin-bottom: 4px; }
        .ss-meta-grid input, .ss-meta-grid select { width: 100%; }
        .ss-meta-grid small { color: #666; }
        .ss-meta-section { background: #f9f9f9; border: 1px solid #ddd; border-radius: 4px; padding: 16px; margin-bottom: 16px; }
        .ss-meta-section h4 { margin: 0 0 12px; padding-bottom: 8px; border-bottom: 1px solid #ddd; }
    </style>

    <!-- Layout & Order -->
    <div class="ss-meta-section">
        <h4>Layout</h4>
        <div class="ss-meta-grid">
            <p>
                <label for="ss_order">Display Order</label>
                <input type="number" id="ss_order" name="ss_order" value="<?php echo esc_attr( $m['order'] ); ?>" min="0" step="1">
            </p>
            <p>
                <label for="ss_section_height">Section Height (vh)</label>
                <input type="number" id="ss_section_height" name="ss_section_height" value="<?php echo esc_attr( $m['section_height'] ); ?>" min="50" max="200" step="10">
                <small>100 = full screen, 150 = extra tall for scroll room</small>
            </p>
            <p>
                <label for="ss_content_position">Content Position</label>
                <select id="ss_content_position" name="ss_content_position">
                    <?php foreach ( array( 'top' => 'Top', 'center' => 'Center', 'bottom' => 'Bottom' ) as $val => $label ) : ?>
                        <option value="<?php echo $val; ?>" <?php selected( $m['content_position'], $val ); ?>><?php echo $label; ?></option>
                    <?php endforeach; ?>
                </select>
            </p>
            <p>
                <label for="ss_text_align">Text Alignment</label>
                <select id="ss_text_align" name="ss_text_align">
                    <?php foreach ( array( 'left' => 'Left', 'center' => 'Center', 'right' => 'Right' ) as $val => $label ) : ?>
                        <option value="<?php echo $val; ?>" <?php selected( $m['text_align'], $val ); ?>><?php echo $label; ?></option>
                    <?php endforeach; ?>
                </select>
            </p>
            <p>
                <label for="ss_content_width">Content Max Width (px)</label>
                <input type="number" id="ss_content_width" name="ss_content_width" value="<?php echo esc_attr( $m['content_width'] ); ?>" min="300" max="1600" step="50">
            </p>
            <p>
                <label for="ss_title_visible">Show Title</label>
                <select id="ss_title_visible" name="ss_title_visible">
                    <option value="yes" <?php selected( $m['title_visible'], 'yes' ); ?>>Yes</option>
                    <option value="no" <?php selected( $m['title_visible'], 'no' ); ?>>No (content only)</option>
                </select>
            </p>
        </div>
    </div>

    <!-- Background -->
    <div class="ss-meta-section">
        <h4>Background</h4>
        <div class="ss-meta-grid">
            <p>
                <label for="ss_bg_type">Background Type</label>
                <select id="ss_bg_type" name="ss_bg_type">
                    <option value="image" <?php selected( $m['bg_type'], 'image' ); ?>>Featured Image</option>
                    <option value="video" <?php selected( $m['bg_type'], 'video' ); ?>>Video (MP4 URL)</option>
                    <option value="color" <?php selected( $m['bg_type'], 'color' ); ?>>Solid Color</option>
                </select>
            </p>
            <p>
                <label for="ss_parallax_speed">Parallax Speed (0-1)</label>
                <input type="number" id="ss_parallax_speed" name="ss_parallax_speed" value="<?php echo esc_attr( $m['parallax_speed'] ); ?>" min="0" max="1" step="0.05">
                <small>0 = static, 0.3 = subtle, 1 = dramatic</small>
            </p>
            <p class="ss-full ss-toggle-bg-video" style="<?php echo $m['bg_type'] !== 'video' ? 'display:none;' : ''; ?>">
                <label for="ss_bg_video">Background Video URL (MP4)</label>
                <input type="url" id="ss_bg_video" name="ss_bg_video" value="<?php echo esc_url( $m['bg_video'] ); ?>" placeholder="https://example.com/bg-video.mp4">
            </p>
            <p>
                <label for="ss_overlay_color">Overlay Color</label>
                <input type="color" id="ss_overlay_color" name="ss_overlay_color" value="<?php echo esc_attr( $m['overlay_color'] ); ?>">
            </p>
            <p>
                <label for="ss_overlay">Overlay Opacity (0-1)</label>
                <input type="number" id="ss_overlay" name="ss_overlay" value="<?php echo esc_attr( $m['overlay'] ); ?>" min="0" max="1" step="0.05">
            </p>
        </div>
    </div>

    <!-- Videos -->
    <div class="ss-meta-section">
        <h4>Videos (appear within the section content)</h4>
        <style>
            .ss-video-row { display: flex; gap: 10px; align-items: flex-end; margin-bottom: 10px; padding: 10px; background: #fff; border: 1px solid #ddd; border-radius: 4px; }
            .ss-video-row .ss-vr-url { flex: 1; }
            .ss-video-row .ss-vr-label { width: 140px; }
            .ss-video-row .ss-vr-width { width: 90px; }
            .ss-video-row input { width: 100%; }
            .ss-video-row label { font-weight: 600; display: block; margin-bottom: 4px; font-size: 12px; }
            .ss-remove-video { background: #dc3545; color: #fff; border: none; padding: 6px 12px; border-radius: 3px; cursor: pointer; white-space: nowrap; }
            .ss-remove-video:hover { background: #c82333; }
            #ss-add-video { margin-top: 8px; }
        </style>
        <div id="ss-videos-list">
            <?php foreach ( $videos as $vi => $v ) : ?>
            <div class="ss-video-row">
                <div class="ss-vr-url">
                    <label>Video URL</label>
                    <input type="url" name="ss_videos[<?php echo $vi; ?>][url]" value="<?php echo esc_url( $v['url'] ); ?>" placeholder="https://www.youtube.com/watch?v=xxxxx">
                </div>
                <div class="ss-vr-label">
                    <label>Label (optional)</label>
                    <input type="text" name="ss_videos[<?php echo $vi; ?>][label]" value="<?php echo esc_attr( $v['label'] ?? '' ); ?>" placeholder="Video title">
                </div>
                <div class="ss-vr-width">
                    <label>Width (px)</label>
                    <input type="number" name="ss_videos[<?php echo $vi; ?>][width]" value="<?php echo esc_attr( $v['width'] ?? '800' ); ?>" min="200" max="1400" step="50">
                </div>
                <button type="button" class="ss-remove-video">Remove</button>
            </div>
            <?php endforeach; ?>
        </div>
        <button type="button" class="button" id="ss-add-video">+ Add Video</button>
        <p style="margin-top:10px;">
            <small>Paste any YouTube, Vimeo, or MP4 URL. Multiple videos display in a grid within the section.</small>
        </p>
        <div class="ss-meta-grid" style="margin-top: 12px;">
            <p>
                <label for="ss_video_autoplay">Video Behavior</label>
                <select id="ss_video_autoplay" name="ss_video_autoplay">
                    <option value="on_scroll" <?php selected( $m['video_autoplay'], 'on_scroll' ); ?>>Play when scrolled into view</option>
                    <option value="click" <?php selected( $m['video_autoplay'], 'click' ); ?>>Click to play</option>
                    <option value="autoplay" <?php selected( $m['video_autoplay'], 'autoplay' ); ?>>Always autoplay (muted)</option>
                </select>
            </p>
            <p>
                <label for="ss_video_layout">Video Layout</label>
                <select id="ss_video_layout" name="ss_video_layout">
                    <option value="stack" <?php selected( $m['video_layout'], 'stack' ); ?>>Stacked (one per row)</option>
                    <option value="grid-2" <?php selected( $m['video_layout'], 'grid-2' ); ?>>Grid — 2 columns</option>
                    <option value="grid-3" <?php selected( $m['video_layout'], 'grid-3' ); ?>>Grid — 3 columns</option>
                    <option value="featured" <?php selected( $m['video_layout'], 'featured' ); ?>>Featured (1 large + small below)</option>
                </select>
            </p>
        </div>
    </div>

    <!-- Animation -->
    <div class="ss-meta-section">
        <h4>Scroll Animation</h4>
        <div class="ss-meta-grid">
            <p>
                <label for="ss_animation">Animation Type</label>
                <select id="ss_animation" name="ss_animation">
                    <?php
                    $anims = array(
                        'fade-up'    => 'Fade Up',
                        'fade-down'  => 'Fade Down',
                        'fade-left'  => 'Slide from Right',
                        'fade-right' => 'Slide from Left',
                        'zoom-in'    => 'Zoom In',
                        'zoom-out'   => 'Zoom Out',
                        'blur-in'    => 'Blur In',
                        'clip-up'    => 'Clip Reveal (Up)',
                        'clip-left'  => 'Clip Reveal (Left)',
                        'none'       => 'None (instant)',
                    );
                    foreach ( $anims as $val => $label ) :
                    ?>
                        <option value="<?php echo $val; ?>" <?php selected( $m['animation'], $val ); ?>><?php echo $label; ?></option>
                    <?php endforeach; ?>
                </select>
            </p>
            <p>
                <label for="ss_anim_easing">Easing</label>
                <select id="ss_anim_easing" name="ss_anim_easing">
                    <?php
                    $easings = array(
                        'cubic-bezier(0.25, 0.46, 0.45, 0.94)' => 'Ease Out (default)',
                        'cubic-bezier(0.22, 1, 0.36, 1)'       => 'Ease Out Quint (cinematic)',
                        'cubic-bezier(0.16, 1, 0.3, 1)'        => 'Ease Out Expo (dramatic)',
                        'cubic-bezier(0.34, 1.56, 0.64, 1)'    => 'Ease Out Back (bounce)',
                        'ease-in-out'                           => 'Ease In Out',
                        'linear'                                => 'Linear',
                    );
                    foreach ( $easings as $val => $label ) :
                    ?>
                        <option value="<?php echo esc_attr( $val ); ?>" <?php selected( $m['anim_easing'], $val ); ?>><?php echo $label; ?></option>
                    <?php endforeach; ?>
                </select>
            </p>
            <p>
                <label for="ss_anim_duration">Duration (seconds)</label>
                <input type="number" id="ss_anim_duration" name="ss_anim_duration" value="<?php echo esc_attr( $m['anim_duration'] ); ?>" min="0.2" max="4" step="0.1">
            </p>
            <p>
                <label for="ss_anim_delay">Delay (seconds)</label>
                <input type="number" id="ss_anim_delay" name="ss_anim_delay" value="<?php echo esc_attr( $m['anim_delay'] ); ?>" min="0" max="3" step="0.1">
            </p>
            <p>
                <label for="ss_anim_stagger">Child Stagger (seconds)</label>
                <input type="number" id="ss_anim_stagger" name="ss_anim_stagger" value="<?php echo esc_attr( $m['anim_stagger'] ); ?>" min="0" max="1" step="0.05">
                <small>Delay between title, body, and video appearing</small>
            </p>
        </div>
    </div>

    <script>
    jQuery(function($){
        $('#ss_bg_type').on('change', function(){
            $('.ss-toggle-bg-video').toggle($(this).val() === 'video');
        });

        // Repeatable videos
        var videoIdx = <?php echo max( count( $videos ), 0 ); ?>;
        $('#ss-add-video').on('click', function(){
            var row = '<div class="ss-video-row">' +
                '<div class="ss-vr-url"><label>Video URL</label><input type="url" name="ss_videos[' + videoIdx + '][url]" placeholder="https://www.youtube.com/watch?v=xxxxx"></div>' +
                '<div class="ss-vr-label"><label>Label (optional)</label><input type="text" name="ss_videos[' + videoIdx + '][label]" placeholder="Video title"></div>' +
                '<div class="ss-vr-width"><label>Width (px)</label><input type="number" name="ss_videos[' + videoIdx + '][width]" value="800" min="200" max="1400" step="50"></div>' +
                '<button type="button" class="ss-remove-video">Remove</button>' +
                '</div>';
            $('#ss-videos-list').append(row);
            videoIdx++;
        });
        $('#ss-videos-list').on('click', '.ss-remove-video', function(){
            $(this).closest('.ss-video-row').remove();
        });
    });
    </script>
    <?php
}

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
        'ss_order'            => '_ss_order',
        'ss_bg_type'          => '_ss_bg_type',
        'ss_bg_video'         => '_ss_bg_video',
        'ss_overlay'          => '_ss_overlay',
        'ss_overlay_color'    => '_ss_overlay_color',
        'ss_animation'        => '_ss_animation',
        'ss_anim_duration'    => '_ss_anim_duration',
        'ss_anim_delay'       => '_ss_anim_delay',
        'ss_anim_stagger'     => '_ss_anim_stagger',
        'ss_anim_easing'      => '_ss_anim_easing',
        'ss_text_align'       => '_ss_text_align',
        'ss_content_width'    => '_ss_content_width',
        'ss_content_position' => '_ss_content_position',
        'ss_parallax_speed'   => '_ss_parallax_speed',
        'ss_video_autoplay'   => '_ss_video_autoplay',
        'ss_video_layout'     => '_ss_video_layout',
        'ss_section_height'   => '_ss_section_height',
        'ss_title_visible'    => '_ss_title_visible',
    );

    foreach ( $fields as $field => $meta_key ) {
        if ( isset( $_POST[ $field ] ) ) {
            $value = sanitize_text_field( wp_unslash( $_POST[ $field ] ) );
            update_post_meta( $post_id, $meta_key, $value );
        }
    }

    // Save videos array as JSON
    $videos = array();
    if ( isset( $_POST['ss_videos'] ) && is_array( $_POST['ss_videos'] ) ) {
        foreach ( $_POST['ss_videos'] as $v ) {
            $url = isset( $v['url'] ) ? esc_url_raw( wp_unslash( $v['url'] ) ) : '';
            if ( empty( $url ) ) continue;
            $videos[] = array(
                'url'   => $url,
                'label' => isset( $v['label'] ) ? sanitize_text_field( wp_unslash( $v['label'] ) ) : '',
                'width' => isset( $v['width'] ) ? intval( $v['width'] ) : 800,
            );
        }
    }
    update_post_meta( $post_id, '_ss_videos', wp_json_encode( $videos ) );
}
add_action( 'save_post_scroll_section', 'ss_save_meta' );

function ss_ensure_order_meta( $post_id ) {
    if ( get_post_type( $post_id ) !== 'scroll_section' ) {
        return;
    }
    if ( get_post_meta( $post_id, '_ss_order', true ) === '' ) {
        update_post_meta( $post_id, '_ss_order', '0' );
    }
}
add_action( 'save_post', 'ss_ensure_order_meta' );

/* ==========================================================================
   Frontend Assets
   ========================================================================== */

function ss_enqueue_assets() {
    // Append file modification time to guarantee the browser loads fresh files
    $css_file = SCROLL_SECTIONS_DIR . 'assets/css/scroll-sections.css';
    $js_file  = SCROLL_SECTIONS_DIR . 'assets/js/scroll-sections.js';
    $css_ver  = SCROLL_SECTIONS_VERSION . '.' . ( file_exists( $css_file ) ? filemtime( $css_file ) : '' );
    $js_ver   = SCROLL_SECTIONS_VERSION . '.' . ( file_exists( $js_file ) ? filemtime( $js_file ) : '' );

    wp_enqueue_style(
        'scroll-sections',
        SCROLL_SECTIONS_URL . 'assets/css/scroll-sections.css',
        array(),
        $css_ver
    );

    wp_enqueue_script(
        'scroll-sections',
        SCROLL_SECTIONS_URL . 'assets/js/scroll-sections.js',
        array(),
        $js_ver,
        true
    );
}
add_action( 'wp_enqueue_scripts', 'ss_enqueue_assets' );

/* ==========================================================================
   Query
   ========================================================================== */

function ss_get_sections() {
    return new WP_Query( array(
        'post_type'      => 'scroll_section',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'orderby'        => array( 'meta_value_num' => 'ASC', 'date' => 'ASC' ),
        'meta_query'     => array(
            'relation' => 'OR',
            array( 'key' => '_ss_order', 'compare' => 'EXISTS' ),
            array( 'key' => '_ss_order', 'compare' => 'NOT EXISTS' ),
        ),
    ) );
}

/* ==========================================================================
   Render
   ========================================================================== */

function ss_render_sections() {
    $sections = ss_get_sections();

    if ( ! $sections->have_posts() ) {
        return '<p style="color:#fff;text-align:center;padding:4rem;">No scroll sections found. Add sections in the WordPress admin under <strong>Scroll Sections</strong>.</p>';
    }

    $output = '<div class="ss-wrapper" id="ss-wrapper">';
    $output .= '<div class="ss-smooth" id="ss-smooth">';

    // Build section HTML
    $nav_items = array();
    $i = 0;
    while ( $sections->have_posts() ) {
        $sections->the_post();
        $pid = get_the_ID();

        // Gather meta
        $bg_type         = get_post_meta( $pid, '_ss_bg_type', true ) ?: 'image';
        $bg_video        = get_post_meta( $pid, '_ss_bg_video', true );
        $overlay         = get_post_meta( $pid, '_ss_overlay', true ) ?: '0.4';
        $overlay_color   = get_post_meta( $pid, '_ss_overlay_color', true ) ?: '#000000';
        $animation       = get_post_meta( $pid, '_ss_animation', true ) ?: 'fade-up';
        $anim_duration   = get_post_meta( $pid, '_ss_anim_duration', true ) ?: '1.2';
        $anim_delay      = get_post_meta( $pid, '_ss_anim_delay', true ) ?: '0';
        $anim_stagger    = get_post_meta( $pid, '_ss_anim_stagger', true ) ?: '0.15';
        $anim_easing     = get_post_meta( $pid, '_ss_anim_easing', true ) ?: 'cubic-bezier(0.25,0.46,0.45,0.94)';
        $text_align      = get_post_meta( $pid, '_ss_text_align', true ) ?: 'center';
        $content_width   = get_post_meta( $pid, '_ss_content_width', true ) ?: '900';
        $content_pos     = get_post_meta( $pid, '_ss_content_position', true ) ?: 'center';
        $parallax_speed  = get_post_meta( $pid, '_ss_parallax_speed', true ) ?: '0.3';
        $video_autoplay  = get_post_meta( $pid, '_ss_video_autoplay', true ) ?: 'on_scroll';
        $video_layout    = get_post_meta( $pid, '_ss_video_layout', true ) ?: 'stack';
        $section_height  = get_post_meta( $pid, '_ss_section_height', true ) ?: '100';
        $title_visible   = get_post_meta( $pid, '_ss_title_visible', true ) ?: 'yes';

        // Load videos (new JSON format) with legacy fallback
        $videos_raw = get_post_meta( $pid, '_ss_videos', true );
        $videos = $videos_raw ? json_decode( $videos_raw, true ) : array();
        if ( ! is_array( $videos ) ) $videos = array();
        $legacy_video = get_post_meta( $pid, '_ss_inline_video', true );
        if ( $legacy_video && empty( $videos ) ) {
            $videos = array( array( 'url' => $legacy_video, 'label' => '', 'width' => get_post_meta( $pid, '_ss_video_width', true ) ?: 800 ) );
        }

        $title     = get_the_title();
        $content   = wp_kses_post( apply_filters( 'the_content', get_the_content() ) );
        $bg_img    = ( $bg_type === 'image' && has_post_thumbnail( $pid ) ) ? get_the_post_thumbnail_url( $pid, 'full' ) : '';
        $overlay_rgba = ss_hex_to_rgba( $overlay_color, $overlay );

        $nav_items[] = $title;

        // Align class
        $align_class = 'ss-pos-' . $content_pos;

        // Data attributes for JS
        $data = sprintf(
            'data-animation="%s" data-duration="%s" data-delay="%s" data-stagger="%s" data-easing="%s" data-parallax="%s" data-index="%d" data-video-autoplay="%s"',
            esc_attr( $animation ),
            esc_attr( $anim_duration ),
            esc_attr( $anim_delay ),
            esc_attr( $anim_stagger ),
            esc_attr( $anim_easing ),
            esc_attr( $parallax_speed ),
            $i,
            esc_attr( $video_autoplay )
        );

        $output .= '<section class="ss-section ' . $align_class . '" ' . $data . ' style="min-height:' . intval( $section_height ) . 'vh;">';

        // Background
        if ( $bg_type === 'video' && $bg_video ) {
            $output .= '<div class="ss-bg ss-bg-video"><video autoplay muted loop playsinline><source src="' . esc_url( $bg_video ) . '" type="video/mp4"></video></div>';
        } elseif ( $bg_type === 'image' && $bg_img ) {
            $output .= '<div class="ss-bg ss-bg-image" style="background-image:url(' . esc_url( $bg_img ) . ');"></div>';
        } else {
            $output .= '<div class="ss-bg ss-bg-color" style="background-color:' . esc_attr( $overlay_color ) . ';"></div>';
        }

        // Overlay
        $output .= '<div class="ss-overlay" style="background:' . esc_attr( $overlay_rgba ) . ';"></div>';

        // Content container
        $output .= '<div class="ss-content" style="text-align:' . esc_attr( $text_align ) . ';max-width:' . intval( $content_width ) . 'px;">';

        // Title (stagger child 1)
        if ( $title_visible === 'yes' ) {
            $output .= '<h2 class="ss-title ss-stagger">' . esc_html( $title ) . '</h2>';
        }

        // Body text (stagger child 2)
        if ( trim( $content ) ) {
            $output .= '<div class="ss-body ss-stagger">' . $content . '</div>';
        }

        // Videos (stagger child 3+)
        if ( ! empty( $videos ) ) {
            $layout_class = 'ss-video-grid ss-layout-' . esc_attr( $video_layout );
            $output .= '<div class="' . $layout_class . ' ss-stagger">';

            foreach ( $videos as $v ) {
                $vurl   = $v['url'] ?? '';
                $vlabel = $v['label'] ?? '';
                $vwidth = intval( $v['width'] ?? 800 );
                if ( empty( $vurl ) ) continue;

                $output .= '<div class="ss-video-item" style="max-width:' . $vwidth . 'px;">';

                if ( preg_match( '/\.(mp4|webm)(\?.*)?$/i', $vurl ) ) {
                    $output .= '<video class="ss-video-player" ' . ( $video_autoplay === 'autoplay' ? 'autoplay muted' : '' ) . ' loop playsinline controls><source src="' . esc_url( $vurl ) . '" type="video/mp4"></video>';
                } else {
                    $embed_url = ss_to_embed_url( $vurl );
                    $allow = 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share';
                    if ( $video_autoplay === 'autoplay' || $video_autoplay === 'on_scroll' ) {
                        $sep = ( strpos( $embed_url, '?' ) !== false ) ? '&' : '?';
                        if ( strpos( $embed_url, 'youtube.com' ) !== false ) {
                            $embed_url .= $sep . 'autoplay=1&mute=1&loop=1&playsinline=1&rel=0';
                        }
                        if ( strpos( $embed_url, 'vimeo.com' ) !== false ) {
                            $embed_url .= $sep . 'autoplay=1&muted=1&loop=1&playsinline=1';
                        }
                    }
                    $src = esc_url( $embed_url );
                    $output .= '<div class="ss-video-embed"><iframe src="' . $src . '" frameborder="0" allow="' . $allow . '" allowfullscreen loading="lazy"></iframe></div>';
                }

                if ( $vlabel ) {
                    $output .= '<p class="ss-video-label">' . esc_html( $vlabel ) . '</p>';
                }

                $output .= '</div>'; // .ss-video-item
            }

            $output .= '</div>'; // .ss-video-grid
        }

        $output .= '</div>'; // .ss-content
        $output .= '</section>';
        $i++;
    }
    wp_reset_postdata();

    $output .= '</div>'; // .ss-smooth

    // Navigation dots
    $output .= '<nav class="ss-nav" id="ss-nav" aria-label="Section navigation"><ul>';
    foreach ( $nav_items as $idx => $nav_title ) {
        $active = $idx === 0 ? ' ss-nav-active' : '';
        $output .= '<li><button class="ss-nav-dot' . $active . '" data-index="' . $idx . '" aria-label="' . esc_attr( $nav_title ) . '"><span class="ss-nav-tooltip">' . esc_html( $nav_title ) . '</span></button></li>';
    }
    $output .= '</ul></nav>';

    // Progress bar
    $output .= '<div class="ss-progress" id="ss-progress"></div>';

    $output .= '</div>'; // .ss-wrapper

    return $output;
}

function ss_shortcode( $atts ) {
    return ss_render_sections();
}
add_shortcode( 'scroll_sections', 'ss_shortcode' );

/* ==========================================================================
   Helpers
   ========================================================================== */

/**
 * Convert any YouTube or Vimeo URL to its embeddable form.
 * Accepts watch URLs, share URLs, short URLs, and existing embed URLs.
 */
function ss_to_embed_url( $url ) {
    // YouTube: youtube.com/watch?v=ID, youtu.be/ID, youtube.com/embed/ID, youtube.com/shorts/ID
    if ( preg_match( '/(?:youtube\.com\/(?:watch\?.*v=|shorts\/|embed\/)|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $url, $m ) ) {
        return 'https://www.youtube.com/embed/' . $m[1] . '?enablejsapi=1';
    }

    // Vimeo: vimeo.com/ID, player.vimeo.com/video/ID
    if ( preg_match( '/vimeo\.com\/(?:video\/)?(\d+)/', $url, $m ) ) {
        return 'https://player.vimeo.com/video/' . $m[1] . '?api=1';
    }

    // Already an embed or unknown — return as-is
    return $url;
}

function ss_hex_to_rgba( $hex, $alpha ) {
    $hex = ltrim( $hex, '#' );
    if ( strlen( $hex ) === 3 ) {
        $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
    }
    return sprintf( 'rgba(%d,%d,%d,%s)',
        hexdec( substr( $hex, 0, 2 ) ),
        hexdec( substr( $hex, 2, 2 ) ),
        hexdec( substr( $hex, 4, 2 ) ),
        floatval( $alpha )
    );
}

/* ==========================================================================
   Page Template
   ========================================================================== */

function ss_register_template( $templates ) {
    $templates['templates/scroll-sections-template.php'] = 'Scroll Sections (Full Screen)';
    return $templates;
}
add_filter( 'theme_page_templates', 'ss_register_template' );

function ss_load_template( $template ) {
    if ( is_page() ) {
        $slug = get_page_template_slug();
        if ( $slug === 'templates/scroll-sections-template.php' ) {
            $file = SCROLL_SECTIONS_DIR . 'templates/scroll-sections-template.php';
            if ( file_exists( $file ) ) {
                return $file;
            }
        }
    }
    return $template;
}
add_filter( 'template_include', 'ss_load_template' );

/* ==========================================================================
   Admin Columns
   ========================================================================== */

function ss_admin_columns( $columns ) {
    $new = array();
    foreach ( $columns as $key => $val ) {
        $new[ $key ] = $val;
        if ( $key === 'title' ) {
            $new['ss_order']     = 'Order';
            $new['ss_animation'] = 'Animation';
            $new['ss_bg_type']   = 'Background';
            $new['ss_video']     = 'Inline Video';
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
        case 'ss_video':
            $vids = json_decode( get_post_meta( $post_id, '_ss_videos', true ) ?: '[]', true );
            $count = is_array( $vids ) ? count( $vids ) : 0;
            // Legacy fallback
            if ( $count === 0 && get_post_meta( $post_id, '_ss_inline_video', true ) ) $count = 1;
            echo $count ? '<span style="color:green;">' . $count . '</span>' : '—';
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
