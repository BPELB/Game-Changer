<?php
/**
 * Plugin Name: Scroll Sections
 * Description: Cinematic full-screen scroll sections with parallax, animations, and inline video embeds. Use [scroll_sections] shortcode or the included page template.
 * Version: 4.0.0
 * Author: Brandon
 * Text Domain: scroll-sections
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'SS_VER', '4.0.0' );
define( 'SS_DIR', plugin_dir_path( __FILE__ ) );
define( 'SS_URL', plugin_dir_url( __FILE__ ) );

/* =========================================================================
   1. Custom Post Type
   ========================================================================= */

add_action( 'init', function () {
    register_post_type( 'scroll_section', array(
        'labels' => array(
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
        ),
        'public'       => false,
        'show_ui'      => true,
        'show_in_menu' => true,
        'menu_icon'    => 'dashicons-slides',
        'supports'     => array( 'title', 'editor', 'thumbnail' ),
        'has_archive'  => false,
        'rewrite'      => false,
        'show_in_rest' => true,
    ) );
} );

register_activation_hook( __FILE__, function () {
    // Force post type registration then flush
    do_action( 'init' );
    flush_rewrite_rules();
} );

register_deactivation_hook( __FILE__, function () {
    flush_rewrite_rules();
} );

/* =========================================================================
   2. Meta Boxes
   ========================================================================= */

add_action( 'add_meta_boxes', function () {
    add_meta_box( 'ss_settings', 'Section Settings', 'ss_render_meta_box', 'scroll_section', 'normal', 'high' );
} );

function ss_render_meta_box( $post ) {
    wp_nonce_field( 'ss_save', 'ss_nonce' );

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
        'anim_easing'     => get_post_meta( $post->ID, '_ss_anim_easing', true ) ?: 'cubic-bezier(0.25,0.46,0.45,0.94)',
        'text_align'      => get_post_meta( $post->ID, '_ss_text_align', true ) ?: 'center',
        'content_width'   => get_post_meta( $post->ID, '_ss_content_width', true ) ?: '900',
        'content_position'=> get_post_meta( $post->ID, '_ss_content_position', true ) ?: 'center',
        'parallax_speed'  => get_post_meta( $post->ID, '_ss_parallax_speed', true ) ?: '0.3',
        'video_autoplay'  => get_post_meta( $post->ID, '_ss_video_autoplay', true ) ?: 'on_scroll',
        'video_layout'    => get_post_meta( $post->ID, '_ss_video_layout', true ) ?: 'stack',
        'section_height'  => get_post_meta( $post->ID, '_ss_section_height', true ) ?: '100',
        'title_visible'   => get_post_meta( $post->ID, '_ss_title_visible', true ) ?: 'yes',
    );

    $videos_raw = get_post_meta( $post->ID, '_ss_videos', true );
    $videos = $videos_raw ? json_decode( $videos_raw, true ) : array();
    if ( ! is_array( $videos ) ) $videos = array();

    // Legacy migration
    $legacy = get_post_meta( $post->ID, '_ss_inline_video', true );
    if ( $legacy && empty( $videos ) ) {
        $w = get_post_meta( $post->ID, '_ss_video_width', true ) ?: '800';
        $videos = array( array( 'url' => $legacy, 'label' => '', 'width' => $w ) );
    }
    ?>
    <style>
        .ss-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px 20px}
        .ss-grid .ss-span{grid-column:1/-1}
        .ss-grid label{font-weight:600;display:block;margin-bottom:3px}
        .ss-grid input,.ss-grid select{width:100%}
        .ss-grid small{color:#666}
        .ss-box{background:#f9f9f9;border:1px solid #ddd;border-radius:4px;padding:14px;margin-bottom:14px}
        .ss-box h4{margin:0 0 10px;padding-bottom:8px;border-bottom:1px solid #ddd}
        .ss-vrow{display:flex;gap:8px;align-items:flex-end;margin-bottom:8px;padding:8px;background:#fff;border:1px solid #ddd;border-radius:4px}
        .ss-vrow .ss-vu{flex:1} .ss-vrow .ss-vl{width:130px} .ss-vrow .ss-vw{width:80px}
        .ss-vrow input{width:100%} .ss-vrow label{font-weight:600;display:block;margin-bottom:3px;font-size:12px}
        .ss-vdel{background:#dc3545;color:#fff;border:none;padding:5px 10px;border-radius:3px;cursor:pointer;white-space:nowrap}
        .ss-vdel:hover{background:#c82333}
    </style>

    <!-- Layout -->
    <div class="ss-box">
        <h4>Layout</h4>
        <div class="ss-grid">
            <p><label>Display Order</label>
                <input type="number" name="ss_order" value="<?php echo esc_attr( $m['order'] ); ?>" min="0" step="1"></p>
            <p><label>Section Height (vh)</label>
                <input type="number" name="ss_section_height" value="<?php echo esc_attr( $m['section_height'] ); ?>" min="50" max="200" step="10">
                <small>100 = full screen</small></p>
            <p><label>Content Position</label>
                <select name="ss_content_position">
                    <?php foreach ( array( 'top'=>'Top','center'=>'Center','bottom'=>'Bottom' ) as $v => $l ) : ?>
                        <option value="<?php echo $v; ?>" <?php selected( $m['content_position'], $v ); ?>><?php echo $l; ?></option>
                    <?php endforeach; ?>
                </select></p>
            <p><label>Text Alignment</label>
                <select name="ss_text_align">
                    <?php foreach ( array( 'left'=>'Left','center'=>'Center','right'=>'Right' ) as $v => $l ) : ?>
                        <option value="<?php echo $v; ?>" <?php selected( $m['text_align'], $v ); ?>><?php echo $l; ?></option>
                    <?php endforeach; ?>
                </select></p>
            <p><label>Content Max Width (px)</label>
                <input type="number" name="ss_content_width" value="<?php echo esc_attr( $m['content_width'] ); ?>" min="300" max="1600" step="50"></p>
            <p><label>Show Title</label>
                <select name="ss_title_visible">
                    <option value="yes" <?php selected( $m['title_visible'], 'yes' ); ?>>Yes</option>
                    <option value="no" <?php selected( $m['title_visible'], 'no' ); ?>>No</option>
                </select></p>
        </div>
    </div>

    <!-- Background -->
    <div class="ss-box">
        <h4>Background</h4>
        <div class="ss-grid">
            <p><label>Background Type</label>
                <select name="ss_bg_type" id="ss_bg_type">
                    <option value="image" <?php selected( $m['bg_type'], 'image' ); ?>>Featured Image</option>
                    <option value="video" <?php selected( $m['bg_type'], 'video' ); ?>>Video (MP4)</option>
                    <option value="color" <?php selected( $m['bg_type'], 'color' ); ?>>Solid Color</option>
                </select></p>
            <p><label>Parallax Speed (0–1)</label>
                <input type="number" name="ss_parallax_speed" value="<?php echo esc_attr( $m['parallax_speed'] ); ?>" min="0" max="1" step="0.05">
                <small>0 = none, 0.3 = subtle, 1 = dramatic</small></p>
            <p class="ss-span ss-bgv" style="<?php echo $m['bg_type'] !== 'video' ? 'display:none' : ''; ?>">
                <label>Background Video URL (MP4)</label>
                <input type="url" name="ss_bg_video" value="<?php echo esc_url( $m['bg_video'] ); ?>" placeholder="https://example.com/video.mp4"></p>
            <p><label>Overlay Color</label>
                <input type="color" name="ss_overlay_color" value="<?php echo esc_attr( $m['overlay_color'] ); ?>"></p>
            <p><label>Overlay Opacity (0–1)</label>
                <input type="number" name="ss_overlay" value="<?php echo esc_attr( $m['overlay'] ); ?>" min="0" max="1" step="0.05"></p>
        </div>
    </div>

    <!-- Videos -->
    <div class="ss-box">
        <h4>Inline Videos</h4>
        <div id="ss-vlist">
            <?php foreach ( $videos as $vi => $v ) : ?>
            <div class="ss-vrow">
                <div class="ss-vu"><label>URL</label>
                    <input type="url" name="ss_videos[<?php echo $vi; ?>][url]" value="<?php echo esc_url( $v['url'] ); ?>" placeholder="YouTube, Vimeo, or MP4 URL"></div>
                <div class="ss-vl"><label>Label</label>
                    <input type="text" name="ss_videos[<?php echo $vi; ?>][label]" value="<?php echo esc_attr( $v['label'] ?? '' ); ?>"></div>
                <div class="ss-vw"><label>Width</label>
                    <input type="number" name="ss_videos[<?php echo $vi; ?>][width]" value="<?php echo esc_attr( $v['width'] ?? 800 ); ?>" min="200" max="1400" step="50"></div>
                <button type="button" class="ss-vdel">Remove</button>
            </div>
            <?php endforeach; ?>
        </div>
        <button type="button" class="button" id="ss-vadd">+ Add Video</button>
        <p><small>Paste any YouTube, Vimeo, or MP4 URL. Multiple videos display in a grid.</small></p>
        <div class="ss-grid" style="margin-top:10px">
            <p><label>Video Behavior</label>
                <select name="ss_video_autoplay">
                    <option value="on_scroll" <?php selected( $m['video_autoplay'], 'on_scroll' ); ?>>Play when scrolled into view</option>
                    <option value="click" <?php selected( $m['video_autoplay'], 'click' ); ?>>Click to play</option>
                    <option value="autoplay" <?php selected( $m['video_autoplay'], 'autoplay' ); ?>>Always autoplay (muted)</option>
                </select></p>
            <p><label>Video Layout</label>
                <select name="ss_video_layout">
                    <option value="stack" <?php selected( $m['video_layout'], 'stack' ); ?>>Stacked (one per row)</option>
                    <option value="grid-2" <?php selected( $m['video_layout'], 'grid-2' ); ?>>Grid — 2 columns</option>
                    <option value="grid-3" <?php selected( $m['video_layout'], 'grid-3' ); ?>>Grid — 3 columns</option>
                    <option value="featured" <?php selected( $m['video_layout'], 'featured' ); ?>>Featured (1 large + small)</option>
                </select></p>
        </div>
    </div>

    <!-- Animation -->
    <div class="ss-box">
        <h4>Scroll Animation</h4>
        <div class="ss-grid">
            <p><label>Animation</label>
                <select name="ss_animation">
                    <?php foreach ( array(
                        'fade-up'=>'Fade Up','fade-down'=>'Fade Down',
                        'fade-left'=>'Slide from Right','fade-right'=>'Slide from Left',
                        'zoom-in'=>'Zoom In','zoom-out'=>'Zoom Out',
                        'blur-in'=>'Blur In','clip-up'=>'Clip Reveal (Up)',
                        'clip-left'=>'Clip Reveal (Left)','none'=>'None',
                    ) as $v => $l ) : ?>
                        <option value="<?php echo $v; ?>" <?php selected( $m['animation'], $v ); ?>><?php echo $l; ?></option>
                    <?php endforeach; ?>
                </select></p>
            <p><label>Easing</label>
                <select name="ss_anim_easing">
                    <?php foreach ( array(
                        'cubic-bezier(0.25,0.46,0.45,0.94)'=>'Ease Out (default)',
                        'cubic-bezier(0.22,1,0.36,1)'=>'Ease Out Quint',
                        'cubic-bezier(0.16,1,0.3,1)'=>'Ease Out Expo',
                        'cubic-bezier(0.34,1.56,0.64,1)'=>'Ease Out Back',
                        'ease-in-out'=>'Ease In Out',
                        'linear'=>'Linear',
                    ) as $v => $l ) : ?>
                        <option value="<?php echo esc_attr($v); ?>" <?php selected( $m['anim_easing'], $v ); ?>><?php echo $l; ?></option>
                    <?php endforeach; ?>
                </select></p>
            <p><label>Duration (s)</label>
                <input type="number" name="ss_anim_duration" value="<?php echo esc_attr( $m['anim_duration'] ); ?>" min="0.2" max="4" step="0.1"></p>
            <p><label>Delay (s)</label>
                <input type="number" name="ss_anim_delay" value="<?php echo esc_attr( $m['anim_delay'] ); ?>" min="0" max="3" step="0.1"></p>
            <p><label>Stagger (s)</label>
                <input type="number" name="ss_anim_stagger" value="<?php echo esc_attr( $m['anim_stagger'] ); ?>" min="0" max="1" step="0.05">
                <small>Delay between title, body, video appearing</small></p>
        </div>
    </div>

    <script>
    jQuery(function($){
        $('#ss_bg_type').on('change',function(){ $('.ss-bgv').toggle(this.value==='video'); });
        var vi=<?php echo max(count($videos),0); ?>;
        $('#ss-vadd').on('click',function(){
            $('#ss-vlist').append(
                '<div class="ss-vrow">'+
                '<div class="ss-vu"><label>URL</label><input type="url" name="ss_videos['+vi+'][url]" placeholder="YouTube, Vimeo, or MP4 URL"></div>'+
                '<div class="ss-vl"><label>Label</label><input type="text" name="ss_videos['+vi+'][label]"></div>'+
                '<div class="ss-vw"><label>Width</label><input type="number" name="ss_videos['+vi+'][width]" value="800" min="200" max="1400" step="50"></div>'+
                '<button type="button" class="ss-vdel">Remove</button></div>'
            );
            vi++;
        });
        $('#ss-vlist').on('click','.ss-vdel',function(){ $(this).closest('.ss-vrow').remove(); });
    });
    </script>
    <?php
}

/* =========================================================================
   3. Save Meta
   ========================================================================= */

add_action( 'save_post_scroll_section', function ( $post_id ) {
    if ( ! isset( $_POST['ss_nonce'] ) || ! wp_verify_nonce( $_POST['ss_nonce'], 'ss_save' ) ) return;
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    if ( ! current_user_can( 'edit_post', $post_id ) ) return;

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

    foreach ( $fields as $field => $key ) {
        if ( isset( $_POST[ $field ] ) ) {
            update_post_meta( $post_id, $key, sanitize_text_field( wp_unslash( $_POST[ $field ] ) ) );
        }
    }

    $videos = array();
    if ( isset( $_POST['ss_videos'] ) && is_array( $_POST['ss_videos'] ) ) {
        foreach ( $_POST['ss_videos'] as $v ) {
            $url = isset( $v['url'] ) ? esc_url_raw( wp_unslash( $v['url'] ) ) : '';
            if ( ! $url ) continue;
            $videos[] = array(
                'url'   => $url,
                'label' => isset( $v['label'] ) ? sanitize_text_field( wp_unslash( $v['label'] ) ) : '',
                'width' => isset( $v['width'] ) ? intval( $v['width'] ) : 800,
            );
        }
    }
    update_post_meta( $post_id, '_ss_videos', wp_json_encode( $videos ) );
} );

// Auto-set order meta so queries always find sections
add_action( 'save_post', function ( $post_id ) {
    if ( get_post_type( $post_id ) === 'scroll_section' && get_post_meta( $post_id, '_ss_order', true ) === '' ) {
        update_post_meta( $post_id, '_ss_order', '0' );
    }
} );

/* =========================================================================
   4. Enqueue Frontend Assets
   ========================================================================= */

add_action( 'wp_enqueue_scripts', function () {
    $css = SS_DIR . 'assets/css/scroll-sections.css';
    $js  = SS_DIR . 'assets/js/scroll-sections.js';

    wp_enqueue_style(
        'scroll-sections-css',
        SS_URL . 'assets/css/scroll-sections.css',
        array(),
        SS_VER . '.' . ( file_exists( $css ) ? filemtime( $css ) : time() )
    );

    wp_enqueue_script(
        'scroll-sections-js',
        SS_URL . 'assets/js/scroll-sections.js',
        array(),
        SS_VER . '.' . ( file_exists( $js ) ? filemtime( $js ) : time() ),
        true
    );
} );

/* =========================================================================
   5. Query Sections
   ========================================================================= */

function ss_get_sections() {
    return new WP_Query( array(
        'post_type'      => 'scroll_section',
        'posts_per_page' => 50,
        'post_status'    => 'publish',
        'orderby'        => 'meta_value_num',
        'meta_key'       => '_ss_order',
        'order'          => 'ASC',
        'meta_query'     => array(
            'relation' => 'OR',
            array( 'key' => '_ss_order', 'compare' => 'EXISTS' ),
            array( 'key' => '_ss_order', 'compare' => 'NOT EXISTS' ),
        ),
    ) );
}

/* =========================================================================
   6. Render HTML
   ========================================================================= */

function ss_render_sections() {
    $q = ss_get_sections();
    if ( ! $q->have_posts() ) {
        return '<p style="text-align:center;padding:4rem 2rem;color:#999;">No scroll sections found. Create them under <strong>Scroll Sections</strong> in the admin.</p>';
    }

    $html = '';
    $nav  = array();
    $i    = 0;

    while ( $q->have_posts() ) {
        $q->the_post();
        $id = get_the_ID();

        // Meta
        $bg_type        = get_post_meta( $id, '_ss_bg_type', true ) ?: 'image';
        $bg_video       = get_post_meta( $id, '_ss_bg_video', true );
        $overlay        = floatval( get_post_meta( $id, '_ss_overlay', true ) ?: 0.4 );
        $overlay_color  = get_post_meta( $id, '_ss_overlay_color', true ) ?: '#000000';
        $animation      = get_post_meta( $id, '_ss_animation', true ) ?: 'fade-up';
        $anim_dur       = get_post_meta( $id, '_ss_anim_duration', true ) ?: '1.2';
        $anim_del       = get_post_meta( $id, '_ss_anim_delay', true ) ?: '0';
        $anim_stag      = get_post_meta( $id, '_ss_anim_stagger', true ) ?: '0.15';
        $anim_ease      = get_post_meta( $id, '_ss_anim_easing', true ) ?: 'cubic-bezier(0.25,0.46,0.45,0.94)';
        $text_align     = get_post_meta( $id, '_ss_text_align', true ) ?: 'center';
        $content_w      = intval( get_post_meta( $id, '_ss_content_width', true ) ?: 900 );
        $content_pos    = get_post_meta( $id, '_ss_content_position', true ) ?: 'center';
        $parallax       = get_post_meta( $id, '_ss_parallax_speed', true ) ?: '0.3';
        $vid_auto       = get_post_meta( $id, '_ss_video_autoplay', true ) ?: 'on_scroll';
        $vid_layout     = get_post_meta( $id, '_ss_video_layout', true ) ?: 'stack';
        $sec_height     = intval( get_post_meta( $id, '_ss_section_height', true ) ?: 100 );
        $show_title     = get_post_meta( $id, '_ss_title_visible', true ) ?: 'yes';

        // Videos
        $vids_raw = get_post_meta( $id, '_ss_videos', true );
        $vids = $vids_raw ? json_decode( $vids_raw, true ) : array();
        if ( ! is_array( $vids ) ) $vids = array();
        $legacy_v = get_post_meta( $id, '_ss_inline_video', true );
        if ( $legacy_v && empty( $vids ) ) {
            $vids = array( array( 'url' => $legacy_v, 'label' => '', 'width' => 800 ) );
        }

        $title   = get_the_title();
        $body    = wp_kses_post( apply_filters( 'the_content', get_the_content() ) );
        $bg_img  = ( $bg_type === 'image' && has_post_thumbnail( $id ) ) ? get_the_post_thumbnail_url( $id, 'full' ) : '';
        $rgba    = ss_hex_rgba( $overlay_color, $overlay );

        $nav[] = $title;

        // Data attrs for JS
        $data = sprintf(
            'data-anim="%s" data-dur="%s" data-del="%s" data-stag="%s" data-ease="%s" data-parallax="%s" data-idx="%d" data-vidauto="%s"',
            esc_attr( $animation ), esc_attr( $anim_dur ), esc_attr( $anim_del ),
            esc_attr( $anim_stag ), esc_attr( $anim_ease ), esc_attr( $parallax ),
            $i, esc_attr( $vid_auto )
        );

        $html .= '<section class="ss-sec ss-pos-' . esc_attr( $content_pos ) . '" ' . $data . ' style="min-height:100vh;height:' . $sec_height . 'vh;width:100%">';

        // BG
        if ( $bg_type === 'video' && $bg_video ) {
            $html .= '<div class="ss-bg ss-bg-vid"><video autoplay muted loop playsinline><source src="' . esc_url( $bg_video ) . '" type="video/mp4"></video></div>';
        } elseif ( $bg_type === 'image' && $bg_img ) {
            $html .= '<div class="ss-bg ss-bg-img" style="background-image:url(' . esc_url( $bg_img ) . ')"></div>';
        } else {
            $html .= '<div class="ss-bg" style="background:' . esc_attr( $overlay_color ) . '"></div>';
        }

        // Overlay
        $html .= '<div class="ss-over" style="background:' . esc_attr( $rgba ) . '"></div>';

        // Content
        $html .= '<div class="ss-cnt" style="text-align:' . esc_attr( $text_align ) . ';max-width:' . $content_w . 'px">';

        if ( $show_title === 'yes' && $title ) {
            $html .= '<h2 class="ss-ttl ss-child">' . esc_html( $title ) . '</h2>';
        }
        if ( trim( $body ) ) {
            $html .= '<div class="ss-body ss-child">' . $body . '</div>';
        }

        // Videos
        if ( ! empty( $vids ) ) {
            $html .= '<div class="ss-vgrid ss-vl-' . esc_attr( $vid_layout ) . ' ss-child">';
            foreach ( $vids as $v ) {
                $vurl = $v['url'] ?? '';
                if ( ! $vurl ) continue;
                $vw    = intval( $v['width'] ?? 800 );
                $vlab  = $v['label'] ?? '';

                $html .= '<div class="ss-vitem">';

                if ( preg_match( '/\.(mp4|webm)(\?.*)?$/i', $vurl ) ) {
                    $auto = ( $vid_auto === 'autoplay' ) ? ' autoplay muted' : '';
                    $html .= '<video class="ss-vid"' . $auto . ' loop playsinline controls><source src="' . esc_url( $vurl ) . '" type="video/mp4"></video>';
                } else {
                    $embed = ss_embed_url( $vurl );
                    if ( $vid_auto === 'autoplay' || $vid_auto === 'on_scroll' ) {
                        $sep = ( strpos( $embed, '?' ) !== false ) ? '&' : '?';
                        if ( strpos( $embed, 'youtube' ) !== false ) {
                            $embed .= $sep . 'autoplay=1&mute=1&loop=1&playsinline=1&rel=0';
                        } elseif ( strpos( $embed, 'vimeo' ) !== false ) {
                            $embed .= $sep . 'autoplay=1&muted=1&loop=1&playsinline=1';
                        }
                    }
                    $html .= '<div class="ss-vembed"><iframe src="' . esc_url( $embed ) . '" frameborder="0" allow="accelerometer;autoplay;clipboard-write;encrypted-media;gyroscope;picture-in-picture;web-share" allowfullscreen loading="lazy"></iframe></div>';
                }

                if ( $vlab ) {
                    $html .= '<p class="ss-vlabel">' . esc_html( $vlab ) . '</p>';
                }
                $html .= '</div>';
            }
            $html .= '</div>';
        }

        $html .= '</div>'; // .ss-cnt
        $html .= '</section>';
        $i++;
    }
    wp_reset_postdata();

    // Build full output with wrapper, nav, progress
    $out  = '<div class="ss-wrap" id="ss-wrap">';
    $out .= $html;

    // Nav dots
    $out .= '<nav class="ss-nav" id="ss-nav"><ul>';
    foreach ( $nav as $idx => $t ) {
        $ac = $idx === 0 ? ' ss-dot-on' : '';
        $out .= '<li><button class="ss-dot' . $ac . '" data-idx="' . $idx . '" aria-label="' . esc_attr( $t ) . '"><span class="ss-tip">' . esc_html( $t ) . '</span></button></li>';
    }
    $out .= '</ul></nav>';

    // Progress
    $out .= '<div class="ss-prog" id="ss-prog"></div>';
    $out .= '</div>';

    return $out;
}

add_shortcode( 'scroll_sections', function () {
    return ss_render_sections();
} );

/* =========================================================================
   7. Helpers
   ========================================================================= */

function ss_embed_url( $url ) {
    if ( preg_match( '/(?:youtube\.com\/(?:watch\?.*v=|shorts\/|embed\/)|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $url, $m ) ) {
        return 'https://www.youtube.com/embed/' . $m[1] . '?enablejsapi=1';
    }
    if ( preg_match( '/vimeo\.com\/(?:video\/)?(\d+)/', $url, $m ) ) {
        return 'https://player.vimeo.com/video/' . $m[1] . '?api=1';
    }
    return $url;
}

function ss_hex_rgba( $hex, $alpha ) {
    $hex = ltrim( $hex, '#' );
    if ( strlen( $hex ) === 3 ) {
        $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
    }
    return sprintf( 'rgba(%d,%d,%d,%s)',
        hexdec( substr( $hex, 0, 2 ) ),
        hexdec( substr( $hex, 2, 2 ) ),
        hexdec( substr( $hex, 4, 2 ) ),
        $alpha
    );
}

/* =========================================================================
   8. Page Template
   ========================================================================= */

add_filter( 'theme_page_templates', function ( $templates ) {
    $templates['scroll-sections-fullscreen'] = 'Scroll Sections (Full Screen)';
    return $templates;
} );

add_filter( 'template_include', function ( $template ) {
    if ( is_page() && get_page_template_slug() === 'scroll-sections-fullscreen' ) {
        $file = SS_DIR . 'templates/scroll-sections-template.php';
        if ( file_exists( $file ) ) return $file;
    }
    return $template;
} );

/* =========================================================================
   9. Admin Columns
   ========================================================================= */

add_filter( 'manage_scroll_section_posts_columns', function ( $cols ) {
    $new = array();
    foreach ( $cols as $k => $v ) {
        $new[ $k ] = $v;
        if ( $k === 'title' ) {
            $new['ss_order'] = 'Order';
            $new['ss_anim']  = 'Animation';
            $new['ss_bg']    = 'Background';
            $new['ss_vids']  = 'Videos';
        }
    }
    return $new;
} );

add_action( 'manage_scroll_section_posts_custom_column', function ( $col, $pid ) {
    switch ( $col ) {
        case 'ss_order': echo esc_html( get_post_meta( $pid, '_ss_order', true ) ?: '0' ); break;
        case 'ss_anim':  echo esc_html( get_post_meta( $pid, '_ss_animation', true ) ?: 'fade-up' ); break;
        case 'ss_bg':    echo esc_html( ucfirst( get_post_meta( $pid, '_ss_bg_type', true ) ?: 'image' ) ); break;
        case 'ss_vids':
            $v = json_decode( get_post_meta( $pid, '_ss_videos', true ) ?: '[]', true );
            $c = is_array( $v ) ? count( $v ) : 0;
            if ( ! $c && get_post_meta( $pid, '_ss_inline_video', true ) ) $c = 1;
            echo $c ? '<span style="color:green">' . $c . '</span>' : '—';
            break;
    }
}, 10, 2 );

add_filter( 'manage_edit-scroll_section_sortable_columns', function ( $cols ) {
    $cols['ss_order'] = 'ss_order';
    return $cols;
} );

add_action( 'pre_get_posts', function ( $q ) {
    if ( is_admin() && $q->is_main_query() && $q->get( 'orderby' ) === 'ss_order' ) {
        $q->set( 'meta_key', '_ss_order' );
        $q->set( 'orderby', 'meta_value_num' );
    }
} );
