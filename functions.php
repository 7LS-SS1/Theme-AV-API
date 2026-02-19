<?php

if (!defined('ABSPATH')) {
    exit;
}

if (!defined('PVA_ARCHIVE_POSTS_PER_PAGE')) {
    define('PVA_ARCHIVE_POSTS_PER_PAGE', 40);
}

function publish_videos_api_setup(): void
{
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('custom-logo');
    add_theme_support('html5', ['search-form', 'gallery', 'caption', 'style', 'script']);

    register_nav_menus([
        'primary' => __('เมนูหลัก', 'publish-videos-api'),
        'footer' => __('เมนูส่วนท้าย', 'publish-videos-api'),
    ]);

    add_image_size('video-thumb', 640, 360, true);
    add_image_size('video-hero', 1280, 720, true);
}
add_action('after_setup_theme', 'publish_videos_api_setup');

function publish_videos_api_get_theme_version(): string
{
    $theme = wp_get_theme();
    $version = $theme->get('Version');
    return is_string($version) ? $version : '';
}

function publish_videos_api_assets(): void
{
    $theme_version = publish_videos_api_get_theme_version();

    wp_enqueue_style('publish-videos-api-style', get_stylesheet_uri(), [], $theme_version);
    wp_enqueue_script('publish-videos-api-theme', get_template_directory_uri() . '/assets/js/theme.js', [], $theme_version, true);
    wp_add_inline_style('publish-videos-api-style', publish_videos_api_get_css_variables());

    $preroll_type = 'overlay';
    $preroll_image_id = (int) get_theme_mod('pva_preroll_image_id', 0);
    $preroll_image_url = '';
    if ($preroll_image_id) {
        $image_url = wp_get_attachment_image_url($preroll_image_id, 'full');
        if (is_string($image_url)) {
            $preroll_image_url = $image_url;
        }
    }
    if ($preroll_image_url === '') {
        $preroll_image_url = esc_url_raw(get_theme_mod('pva_preroll_image_url', ''));
    }
    $preroll_media_url = esc_url_raw(get_theme_mod('pva_preroll_media_url', ''));
    $preroll_signup_url = esc_url_raw(get_theme_mod('pva_preroll_signup_url', ''));
    $preroll_ads = publish_videos_api_get_preroll_ad_sets(5);

    $settings = [
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('pva_nonce'),
        'darkModeDefault' => get_theme_mod('pva_dark_mode_default', 'auto'),
        'enableDarkToggle' => (bool) get_theme_mod('pva_enable_dark_toggle', true),
        'loadMoreLabel' => __('โหลดเพิ่ม', 'publish-videos-api'),
        'loadingLabel' => __('กำลังโหลด...', 'publish-videos-api'),
        'noMoreLabel' => __('ไม่มีวิดีโอเพิ่มเติม', 'publish-videos-api'),
        'themeVersion' => $theme_version,
        'preroll' => [
            'enabled' => (bool) get_theme_mod('pva_preroll_enabled', false),
            'type' => $preroll_type,
            'mediaUrl' => $preroll_media_url,
            'imageUrl' => $preroll_image_url,
            'targetUrl' => esc_url_raw(get_theme_mod('pva_preroll_target_url', '')),
            'signupUrl' => $preroll_signup_url,
            'overlayEnabled' => (bool) get_theme_mod('pva_preroll_overlay_enabled', true),
            'videoEnabled' => (bool) get_theme_mod('pva_preroll_video_enabled', false),
            'videoAds' => $preroll_ads,
            'frequencyMinutes' => (int) get_theme_mod('pva_preroll_frequency_minutes', 30),
            'oncePerSession' => (bool) get_theme_mod('pva_preroll_once_per_session', true),
            'closeableOverlay' => (bool) get_theme_mod('pva_preroll_overlay_closeable', true),
        ],
    ];
    wp_localize_script('publish-videos-api-theme', 'pvaSettings', $settings);
}
add_action('wp_enqueue_scripts', 'publish_videos_api_assets');

function publish_videos_api_enable_search(): void
{
    if (!class_exists('c2c_DisableSearch')) {
        return;
    }

    remove_action('parse_query', ['c2c_DisableSearch', 'parse_query'], 5);
    remove_filter('get_search_form', '__return_empty_string', 999);
}
add_action('init', 'publish_videos_api_enable_search', 0);

function publish_videos_api_get_video_thumbnail_url(int $post_id): string
{
    if (has_post_thumbnail($post_id)) {
        $url = get_the_post_thumbnail_url($post_id, 'video-thumb');
        return $url ? $url : '';
    }

    $meta_url = get_post_meta($post_id, '_sevenls_vp_thumbnail_url', true);
    return is_string($meta_url) ? esc_url_raw($meta_url) : '';
}

function publish_videos_api_get_placeholder_url(): string
{
    return get_template_directory_uri() . '/assets/images/placeholder-video.svg';
}

function publish_videos_api_get_video_views(int $post_id): int
{
    $meta_keys = [
        '_sevenls_vp_views',
        '_sevenls_vp_view_count',
        'views',
        'view_count',
        'post_views',
    ];

    foreach ($meta_keys as $key) {
        $value = get_post_meta($post_id, $key, true);
        if (is_numeric($value)) {
            return (int) $value;
        }
    }

    return 0;
}

function publish_videos_api_extract_like_count($data): ?int
{
    if (is_array($data)) {
        $keys = [
            'like_count',
            'likes',
            'likes_count',
            'favorite_count',
            'favorites',
            'upvotes',
            'upvote_count',
        ];
        foreach ($keys as $key) {
            if (isset($data[$key]) && is_numeric($data[$key])) {
                return (int) $data[$key];
            }
        }
    }

    return null;
}

function publish_videos_api_get_video_likes(int $post_id): ?int
{
    $meta_keys = apply_filters('publish_videos_api_like_meta_keys', [
        '_sevenls_vp_likes',
        '_sevenls_vp_like_count',
        'likes',
        'like_count',
        'likes_count',
        'favorite_count',
        'favorites',
    ]);

    foreach ($meta_keys as $key) {
        $value = get_post_meta($post_id, $key, true);
        if (is_numeric($value)) {
            return (int) $value;
        }
        if (is_array($value)) {
            $extracted = publish_videos_api_extract_like_count($value);
            if ($extracted !== null) {
                return $extracted;
            }
        }
    }

    $raw_payload = get_post_meta($post_id, '_sevenls_vp_raw_payload', true);
    if (is_string($raw_payload) && $raw_payload !== '') {
        $decoded = json_decode($raw_payload, true);
        if (is_array($decoded)) {
            $extracted = publish_videos_api_extract_like_count($decoded);
            if ($extracted !== null) {
                return $extracted;
            }
            if (isset($decoded['data']) && is_array($decoded['data'])) {
                $extracted = publish_videos_api_extract_like_count($decoded['data']);
                if ($extracted !== null) {
                    return $extracted;
                }
            }
        }
    }

    return null;
}

function publish_videos_api_format_views(int $views): string
{
    if ($views >= 1000000) {
        return number_format_i18n($views / 1000000, 1) . 'M';
    }
    if ($views >= 1000) {
        return number_format_i18n($views / 1000, 1) . 'K';
    }
    return number_format_i18n($views);
}

function publish_videos_api_sanitize_hero_bg_size(string $value): string
{
    $choices = ['cover', 'contain', 'auto'];
    if (in_array($value, $choices, true)) {
        return $value;
    }
    return 'cover';
}

function publish_videos_api_parse_list(string $raw): array
{
    $items = array_filter(array_map('trim', explode(',', $raw)));
    return array_values($items);
}

function publish_videos_api_parse_preroll_ads(string $raw): array
{
    $lines = preg_split("/\r\n|\r|\n/", $raw);
    if (!is_array($lines)) {
        return [];
    }

    $ads = [];
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '') {
            continue;
        }

        $media_url = '';
        $target_url = '';

        if (strpos($line, '{') === 0) {
            $decoded = json_decode($line, true);
            if (is_array($decoded)) {
                $media_url = (string) ($decoded['mediaUrl'] ?? $decoded['media_url'] ?? $decoded['video_url'] ?? $decoded['videoUrl'] ?? $decoded['url'] ?? '');
                $target_url = (string) ($decoded['targetUrl'] ?? $decoded['target_url'] ?? $decoded['signupUrl'] ?? $decoded['signup_url'] ?? $decoded['link'] ?? '');
            }
        } else {
            $parts = array_map('trim', explode('|', $line));
            $media_url = (string) ($parts[0] ?? '');
            $target_url = (string) ($parts[1] ?? '');
        }

        $media_url = esc_url_raw($media_url);
        $target_url = esc_url_raw($target_url);

        if ($media_url === '') {
            continue;
        }

        $ads[] = [
            'mediaUrl' => $media_url,
            'targetUrl' => $target_url,
        ];
    }

    return $ads;
}

function publish_videos_api_get_preroll_ad_sets(int $max_sets = 5): array
{
    $ads = [];
    for ($index = 1; $index <= $max_sets; $index++) {
        $media_id = (int) get_theme_mod("pva_preroll_video_ad_{$index}_media_id", 0);
        if (!$media_id) {
            $media_id = (int) get_theme_mod("pva_preroll_ad_{$index}_media_id", 0);
        }
        $media_url = '';
        if ($media_id) {
            $url = wp_get_attachment_url($media_id);
            if (is_string($url)) {
                $media_url = $url;
            }
        }
        $target_url = esc_url_raw(get_theme_mod("pva_preroll_video_ad_{$index}_target_url", ''));
        if ($target_url === '') {
            $target_url = esc_url_raw(get_theme_mod("pva_preroll_ad_{$index}_signup_url", ''));
        }
        $skip_after = (int) get_theme_mod("pva_preroll_video_ad_{$index}_skip_after", 3);

        if ($media_url === '') {
            continue;
        }

        $ads[] = [
            'mediaUrl' => esc_url_raw($media_url),
            'targetUrl' => $target_url,
            'skipAfter' => $skip_after,
        ];
    }

    return $ads;
}

function publish_videos_api_is_allowed_preroll(int $post_id): bool
{
    if (!get_theme_mod('pva_preroll_enabled', false)) {
        return false;
    }

    $include_posts = publish_videos_api_parse_list((string) get_theme_mod('pva_preroll_include_posts', ''));
    $exclude_posts = publish_videos_api_parse_list((string) get_theme_mod('pva_preroll_exclude_posts', ''));

    if (!empty($exclude_posts) && in_array((string) $post_id, $exclude_posts, true)) {
        return false;
    }
    if (!empty($include_posts) && !in_array((string) $post_id, $include_posts, true)) {
        return false;
    }

    $include_terms_raw = (string) get_theme_mod('pva_preroll_include_terms', '');
    $exclude_terms_raw = (string) get_theme_mod('pva_preroll_exclude_terms', '');
    $include_terms = publish_videos_api_parse_list($include_terms_raw);
    $exclude_terms = publish_videos_api_parse_list($exclude_terms_raw);

    if (!empty($exclude_terms)) {
        foreach (['video_category', 'video_tag', 'video_actor'] as $taxonomy) {
            $terms = get_the_terms($post_id, $taxonomy);
            if (!empty($terms) && !is_wp_error($terms)) {
                foreach ($terms as $term) {
                    if (in_array($term->slug, $exclude_terms, true) || in_array((string) $term->term_id, $exclude_terms, true)) {
                        return false;
                    }
                }
            }
        }
    }

    if (!empty($include_terms)) {
        $matched = false;
        foreach (['video_category', 'video_tag', 'video_actor'] as $taxonomy) {
            $terms = get_the_terms($post_id, $taxonomy);
            if (!empty($terms) && !is_wp_error($terms)) {
                foreach ($terms as $term) {
                    if (in_array($term->slug, $include_terms, true) || in_array((string) $term->term_id, $include_terms, true)) {
                        $matched = true;
                        break 2;
                    }
                }
            }
        }
        if (!$matched) {
            return false;
        }
    }

    return true;
}

function publish_videos_api_format_duration_value($value): string
{
    if (is_array($value)) {
        if (isset($value['duration'])) {
            return publish_videos_api_format_duration_value($value['duration']);
        }
        if (isset($value['length'])) {
            return publish_videos_api_format_duration_value($value['length']);
        }
        return '';
    }

    if (is_numeric($value)) {
        $seconds = (int) $value;
        if ($seconds <= 0) {
            return '';
        }
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $secs = $seconds % 60;
        if ($hours > 0) {
            return sprintf('%d:%02d:%02d', $hours, $minutes, $secs);
        }
        return sprintf('%d:%02d', $minutes, $secs);
    }

    if (is_string($value)) {
        $trimmed = trim($value);
        if ($trimmed === '') {
            return '';
        }
        if (preg_match('/^\d{1,2}:\d{2}(:\d{2})?$/', $trimmed)) {
            return $trimmed;
        }
        if (ctype_digit($trimmed)) {
            return publish_videos_api_format_duration_value((int) $trimmed);
        }
    }

    return '';
}

function publish_videos_api_parse_duration_seconds($value): ?int
{
    if (is_array($value)) {
        if (isset($value['duration'])) {
            return publish_videos_api_parse_duration_seconds($value['duration']);
        }
        if (isset($value['length'])) {
            return publish_videos_api_parse_duration_seconds($value['length']);
        }
        return null;
    }

    if (is_numeric($value)) {
        $seconds = (int) $value;
        return $seconds >= 0 ? $seconds : null;
    }

    if (is_string($value)) {
        $trimmed = trim($value);
        if ($trimmed === '') {
            return null;
        }
        if (preg_match('/^(\d{1,2}):(\d{2})(?::(\d{2}))?$/', $trimmed, $matches)) {
            $hours = isset($matches[3]) ? (int) $matches[1] : 0;
            $minutes = isset($matches[3]) ? (int) $matches[2] : (int) $matches[1];
            $seconds = isset($matches[3]) ? (int) $matches[3] : (int) $matches[2];
            return ($hours * 3600) + ($minutes * 60) + $seconds;
        }
        if (ctype_digit($trimmed)) {
            return (int) $trimmed;
        }
    }

    return null;
}

function publish_videos_api_get_duration_meta_keys(): array
{
    return apply_filters('publish_videos_api_duration_meta_keys', [
        '_sevenls_vp_duration',
        'sevenls_vp_duration',
        '_sevenls_vp_video_duration',
        '_sevenls_vp_length',
        '_vp_duration',
        '_video_duration',
        'video_duration',
        'duration',
    ]);
}

function publish_videos_api_get_video_duration(int $post_id): string
{
    foreach (publish_videos_api_get_duration_meta_keys() as $key) {
        $duration = get_post_meta($post_id, $key, true);
        $formatted = publish_videos_api_format_duration_value($duration);
        if ($formatted !== '') {
            return $formatted;
        }
    }

    return '';
}

function publish_videos_api_is_preview_media_url(string $url): bool
{
    if ($url === '') {
        return false;
    }
    $filetype = wp_check_filetype($url);
    $ext = strtolower((string) $filetype['ext']);
    $allowed = apply_filters('publish_videos_api_preview_allowed_exts', ['mp4', 'webm', 'ogg']);
    return in_array($ext, $allowed, true);
}

function publish_videos_api_get_video_preview_url(int $post_id): string
{
    $meta_keys = apply_filters('publish_videos_api_preview_meta_keys', [
        '_sevenls_vp_preview_url',
        '_sevenls_vp_preview',
        '_sevenls_vp_trailer_url',
        '_sevenls_vp_trailer',
        '_sevenls_vp_sample_url',
        'preview_url',
        'video_preview',
        'trailer_url',
        'sample_url',
    ]);

    foreach ($meta_keys as $key) {
        $url = get_post_meta($post_id, $key, true);
        if (is_string($url) && trim($url) !== '') {
            return esc_url_raw($url);
        }
    }

    $fallback = get_post_meta($post_id, '_sevenls_vp_video_url', true);
    return is_string($fallback) ? esc_url_raw($fallback) : '';
}

function publish_videos_api_is_yoast_active(): bool
{
    return defined('WPSEO_VERSION');
}

function publish_videos_api_get_setting(string $key, $default = '') {
    return get_theme_mod($key, $default);
}

function publish_videos_api_get_css_variables(): string
{
    $vars = [
        '--color-bg' => publish_videos_api_get_setting('pva_color_bg', '#0a0a0c'),
        '--color-surface' => publish_videos_api_get_setting('pva_color_surface', '#141418'),
        '--color-text' => publish_videos_api_get_setting('pva_color_text', '#f2f2f2'),
        '--color-primary' => publish_videos_api_get_setting('pva_color_primary', '#e50914'),
        '--color-primary-hover' => publish_videos_api_get_setting('pva_color_primary_hover', '#ff1a1a'),
        '--color-accent' => publish_videos_api_get_setting('pva_color_accent', '#b81d24'),
        '--color-accent-hover' => publish_videos_api_get_setting('pva_color_accent_hover', '#d42029'),
        '--color-link' => publish_videos_api_get_setting('pva_color_link', '#ff6b6b'),
        '--color-link-hover' => publish_videos_api_get_setting('pva_color_link_hover', '#ffffff'),
        '--radius' => publish_videos_api_get_setting('pva_card_radius', '12px'),
        '--shadow' => publish_videos_api_get_setting('pva_card_shadow', '0 8px 24px rgba(0,0,0,0.4)'),
        '--card-hover' => publish_videos_api_get_setting('pva_card_hover', 'translateY(-4px) scale(1.01)'),
        '--grid-cols-desktop' => (int) publish_videos_api_get_setting('pva_grid_cols_desktop', 4),
        '--grid-cols-tablet' => (int) publish_videos_api_get_setting('pva_grid_cols_tablet', 3),
        '--grid-cols-mobile' => (int) publish_videos_api_get_setting('pva_grid_cols_mobile', 2),
        '--footer-orb-primary' => publish_videos_api_get_setting('pva_footer_orb_primary', '#e50914'),
        '--footer-orb-secondary' => publish_videos_api_get_setting('pva_footer_orb_secondary', '#b81d24'),
        '--footer-orb-neutral' => publish_videos_api_get_setting('pva_footer_orb_neutral', '#ffffff'),
    ];

    $light_vars = [
        '--color-bg' => publish_videos_api_get_setting('pva_color_bg_light', '#f7f7fb'),
        '--color-surface' => publish_videos_api_get_setting('pva_color_surface_light', '#ffffff'),
        '--color-text' => publish_videos_api_get_setting('pva_color_text_light', '#171717'),
        '--color-link' => publish_videos_api_get_setting('pva_color_link_light', '#1b4cff'),
    ];

    $style = ":root{\n";
    foreach ($vars as $name => $value) {
        $style .= $name . ':' . esc_html($value) . ";\n";
    }
    $style .= "}\n";

    $style .= "[data-theme=\"light\"]{\n";
    foreach ($light_vars as $name => $value) {
        $style .= $name . ':' . esc_html($value) . ";\n";
    }
    $style .= "}\n";

    return $style;
}

function publish_videos_api_theme_mode_head(): void
{
    $default_mode = get_theme_mod('pva_dark_mode_default', 'auto');
    $default_mode = in_array($default_mode, ['auto', 'dark', 'light'], true) ? $default_mode : 'auto';

    echo '<script>(function(){var stored=localStorage.getItem("pva-theme");var mode="' . esc_js($default_mode) . '";if(stored){mode=stored;}if(mode==="auto"){mode=window.matchMedia&&window.matchMedia("(prefers-color-scheme: dark)").matches?"dark":"light";}document.documentElement.setAttribute("data-theme",mode);})();</script>' . "\n";
}
add_action('wp_head', 'publish_videos_api_theme_mode_head', 2);

function publish_videos_api_output_version_meta(): void
{
    $theme_version = publish_videos_api_get_theme_version();
    if ($theme_version === '') {
        return;
    }

    echo '<meta name="theme-version" content="' . esc_attr($theme_version) . '">' . "\n";
}
add_action('wp_head', 'publish_videos_api_output_version_meta', 6);

function publish_videos_api_preconnect_video_host(): void
{
    if (!is_singular('video')) {
        return;
    }

    $post_id = get_queried_object_id();
    $video_url = (string) get_post_meta($post_id, '_sevenls_vp_video_url', true);
    if (!$video_url) {
        return;
    }

    $parts = wp_parse_url($video_url);
    if (empty($parts['scheme']) || empty($parts['host'])) {
        return;
    }

    $origin = esc_url($parts['scheme'] . '://' . $parts['host']);
    echo '<link rel="preconnect" href="' . $origin . '">' . "\n";
    echo '<link rel="dns-prefetch" href="' . $origin . '">' . "\n";
}
add_action('wp_head', 'publish_videos_api_preconnect_video_host', 4);

function publish_videos_api_get_meta_description(int $post_id = 0): string
{
    if ($post_id > 0) {
        $description = (string) get_post_field('post_excerpt', $post_id);
        if (!$description) {
            $description = (string) get_post_field('post_content', $post_id);
        }
        return wp_trim_words(wp_strip_all_tags($description), 30, '');
    }

    if (is_tax() || is_post_type_archive('video')) {
        $term = get_queried_object();
        if ($term && !is_wp_error($term) && !empty($term->description)) {
            return wp_strip_all_tags($term->description);
        }
        return get_bloginfo('description');
    }

    return get_bloginfo('description');
}

function publish_videos_api_get_video_duration_iso(int $post_id): string
{
    $seconds = null;
    foreach (publish_videos_api_get_duration_meta_keys() as $key) {
        $raw = get_post_meta($post_id, $key, true);
        $seconds = publish_videos_api_parse_duration_seconds($raw);
        if ($seconds !== null) {
            break;
        }
    }

    if (!$seconds || $seconds <= 0) {
        return '';
    }

    return 'PT' . $seconds . 'S';
}

function publish_videos_api_search_filter(WP_Query $query): void
{
    if (is_admin() || !$query->is_main_query()) {
        return;
    }

    if ($query->is_search()) {
        $query->set('post_type', ['video']);
    }

    if ($query->is_main_query() && (is_post_type_archive('video') || is_tax(['video_category', 'video_tag', 'video_actor']) || $query->is_home() || $query->is_search())) {
        $query->set('posts_per_page', PVA_ARCHIVE_POSTS_PER_PAGE);
    }

    if ($query->is_main_query() && (is_post_type_archive('video') || is_tax(['video_category', 'video_tag', 'video_actor']))) {
        $sort = isset($_GET['sort']) ? sanitize_text_field((string) wp_unslash($_GET['sort'])) : 'latest';
        if ($sort === 'popular' || $sort === 'trending') {
            $query->set('meta_key', '_sevenls_vp_views');
            $query->set('orderby', 'meta_value_num');
            $query->set('order', 'DESC');
            if ($sort === 'trending') {
                $query->set('date_query', [['after' => '14 days ago']]);
            }
        } else {
            $query->set('orderby', 'date');
            $query->set('order', 'DESC');
        }
    }
}
add_action('pre_get_posts', 'publish_videos_api_search_filter');

function publish_videos_api_meta_tags(): void
{
    if (is_feed()) {
        return;
    }

    if (publish_videos_api_is_yoast_active()) {
        return;
    }

    $description = '';

    if (is_singular()) {
        $post_id = get_queried_object_id();
        $description = publish_videos_api_get_meta_description($post_id);
    } elseif (is_tax() || is_post_type_archive('video')) {
        $description = publish_videos_api_get_meta_description();
    } else {
        $description = publish_videos_api_get_meta_description();
    }

    if ($description) {
        echo '<meta name="description" content="' . esc_attr($description) . "\">\n";
    }

    if (is_singular('video')) {
        $post_id = get_queried_object_id();
        $thumb = publish_videos_api_get_video_thumbnail_url($post_id);
        $video_url = get_post_meta($post_id, '_sevenls_vp_video_url', true);
        $duration_iso = publish_videos_api_get_video_duration_iso($post_id);

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'VideoObject',
            'name' => get_the_title(),
            'description' => $description,
            'thumbnailUrl' => $thumb ? [$thumb] : [],
            'uploadDate' => get_the_date('c', $post_id),
        ];

        if (!empty($video_url)) {
            $schema['contentUrl'] = $video_url;
        }
        if (!empty($duration_iso)) {
            $schema['duration'] = $duration_iso;
        }

        echo '<script type="application/ld+json">' . wp_json_encode($schema) . '</script>' . "\n";
    }
}
add_action('wp_head', 'publish_videos_api_meta_tags', 5);

function publish_videos_api_breadcrumb_items(): array
{
    $items = [
        [
            'label' => __('หน้าแรก', 'publish-videos-api'),
            'url' => home_url('/'),
        ],
    ];

    if (is_singular('video')) {
        $items[] = [
            'label' => __('วิดีโอ', 'publish-videos-api'),
            'url' => get_post_type_archive_link('video'),
        ];

        $primary_term = null;
        $terms = get_the_terms(get_queried_object_id(), 'video_category');
        if (!empty($terms) && !is_wp_error($terms)) {
            $primary_term = $terms[0];
        }
        if ($primary_term) {
            $items[] = [
                'label' => $primary_term->name,
                'url' => get_term_link($primary_term),
            ];
        }

        $items[] = [
            'label' => get_the_title(),
            'url' => get_permalink(),
        ];
    } elseif (is_post_type_archive('video')) {
        $items[] = [
            'label' => __('วิดีโอ', 'publish-videos-api'),
            'url' => get_post_type_archive_link('video'),
        ];
    } elseif (is_tax()) {
        $items[] = [
            'label' => __('วิดีโอ', 'publish-videos-api'),
            'url' => get_post_type_archive_link('video'),
        ];
        $items[] = [
            'label' => single_term_title('', false),
            'url' => get_term_link(get_queried_object()),
        ];
    }

    return $items;
}

function publish_videos_api_render_breadcrumbs(): void
{
    $items = publish_videos_api_breadcrumb_items();
    if (count($items) < 2) {
        return;
    }
    echo '<nav class="breadcrumb" aria-label="' . esc_attr__('Breadcrumb', 'publish-videos-api') . '"><ol>';
    foreach ($items as $index => $item) {
        $is_last = $index === array_key_last($items);
        echo '<li class="breadcrumb__item">';
        if (!$is_last) {
            echo '<a href="' . esc_url($item['url']) . '">' . esc_html($item['label']) . '</a>';
        } else {
            echo '<span>' . esc_html($item['label']) . '</span>';
        }
        echo '</li>';
    }
    echo '</ol></nav>';
}

function publish_videos_api_breadcrumb_schema(): void
{
    if (publish_videos_api_is_yoast_active()) {
        return;
    }

    $items = publish_videos_api_breadcrumb_items();
    if (count($items) < 2) {
        return;
    }

    $list = [];
    foreach ($items as $index => $item) {
        $list[] = [
            '@type' => 'ListItem',
            'position' => $index + 1,
            'name' => $item['label'],
            'item' => $item['url'],
        ];
    }

    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => $list,
    ];

    echo '<script type="application/ld+json">' . wp_json_encode($schema) . '</script>' . "\n";
}
add_action('wp_head', 'publish_videos_api_breadcrumb_schema', 6);

function publish_videos_api_yoast_schema_graph(array $graph, $context): array
{
    if (!publish_videos_api_is_yoast_active() || !is_singular('video')) {
        return $graph;
    }

    foreach ($graph as $piece) {
        if (isset($piece['@type']) && $piece['@type'] === 'VideoObject') {
            return $graph;
        }
    }

    $post_id = get_queried_object_id();
    $description = publish_videos_api_get_meta_description($post_id);
    $thumb = publish_videos_api_get_video_thumbnail_url($post_id);
    $video_url = get_post_meta($post_id, '_sevenls_vp_video_url', true);
    $duration_iso = publish_videos_api_get_video_duration_iso($post_id);

    $video_object = [
        '@type' => 'VideoObject',
        '@id' => get_permalink($post_id) . '#videoobject',
        'name' => get_the_title(),
        'description' => $description,
        'thumbnailUrl' => $thumb ? [$thumb] : [],
        'uploadDate' => get_post_time('c', true, $post_id),
        'inLanguage' => get_bloginfo('language'),
        'mainEntityOfPage' => get_permalink($post_id),
    ];

    if (!empty($video_url)) {
        $video_object['contentUrl'] = $video_url;
    }

    if (!empty($duration_iso)) {
        $video_object['duration'] = $duration_iso;
    }

    $graph[] = $video_object;

    return $graph;
}
add_filter('wpseo_schema_graph', 'publish_videos_api_yoast_schema_graph', 10, 2);

function publish_videos_api_yoast_og_image(string $image): string
{
    if (!publish_videos_api_is_yoast_active() || !is_singular('video')) {
        return $image;
    }

    $thumb = publish_videos_api_get_video_thumbnail_url(get_queried_object_id());
    return $thumb ?: $image;
}
add_filter('wpseo_opengraph_image', 'publish_videos_api_yoast_og_image');
add_filter('wpseo_twitter_image', 'publish_videos_api_yoast_og_image');

function publish_videos_api_ajax_load_more(): void
{
    check_ajax_referer('pva_nonce', 'nonce');

    $paged = max(1, (int) ($_POST['page'] ?? 1));
    $sort = sanitize_text_field((string) ($_POST['sort'] ?? 'latest'));
    $taxonomy = sanitize_text_field((string) ($_POST['taxonomy'] ?? ''));
    $term = sanitize_text_field((string) ($_POST['term'] ?? ''));

    $args = [
        'post_type' => 'video',
        'posts_per_page' => PVA_ARCHIVE_POSTS_PER_PAGE,
        'post_status' => 'publish',
        'paged' => $paged,
    ];

    if ($sort === 'popular') {
        $args['meta_key'] = '_sevenls_vp_views';
        $args['orderby'] = 'meta_value_num';
        $args['order'] = 'DESC';
    } elseif ($sort === 'trending') {
        $args['meta_key'] = '_sevenls_vp_views';
        $args['orderby'] = 'meta_value_num';
        $args['order'] = 'DESC';
        $args['date_query'] = [
            'after' => '14 days ago',
        ];
    } else {
        $args['orderby'] = 'date';
        $args['order'] = 'DESC';
    }

    if ($taxonomy && $term) {
        $args['tax_query'] = [
            [
                'taxonomy' => $taxonomy,
                'field' => is_numeric($term) ? 'term_id' : 'slug',
                'terms' => $term,
            ],
        ];
    }

    $query = new WP_Query($args);
    ob_start();
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            get_template_part('template-parts/content', 'video-card');
        }
    }
    wp_reset_postdata();
    $html = ob_get_clean();

    wp_send_json_success([
        'html' => $html,
        'maxPages' => (int) $query->max_num_pages,
    ]);
}
add_action('wp_ajax_pva_load_more', 'publish_videos_api_ajax_load_more');
add_action('wp_ajax_nopriv_pva_load_more', 'publish_videos_api_ajax_load_more');

function publish_videos_api_customize_register(WP_Customize_Manager $wp_customize): void
{
    $wp_customize->add_section('pva_colors', [
        'title' => __('Theme Colors', 'publish-videos-api'),
        'priority' => 30,
    ]);

    $wp_customize->add_setting('pva_color_bg', ['default' => '#0a0a0c', 'transport' => 'refresh']);
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'pva_color_bg', [
        'label' => __('Background', 'publish-videos-api'),
        'section' => 'pva_colors',
    ]));

    $wp_customize->add_setting('pva_color_surface', ['default' => '#141418']);
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'pva_color_surface', [
        'label' => __('Surface', 'publish-videos-api'),
        'section' => 'pva_colors',
    ]));

    $wp_customize->add_setting('pva_color_text', ['default' => '#f2f2f2']);
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'pva_color_text', [
        'label' => __('Text', 'publish-videos-api'),
        'section' => 'pva_colors',
    ]));

    $wp_customize->add_setting('pva_color_primary', ['default' => '#e50914']);
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'pva_color_primary', [
        'label' => __('Primary', 'publish-videos-api'),
        'section' => 'pva_colors',
    ]));

    $wp_customize->add_setting('pva_color_accent', ['default' => '#b81d24']);
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'pva_color_accent', [
        'label' => __('Accent', 'publish-videos-api'),
        'section' => 'pva_colors',
    ]));

    $wp_customize->add_setting('pva_color_link', ['default' => '#ff6b6b']);
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'pva_color_link', [
        'label' => __('Link', 'publish-videos-api'),
        'section' => 'pva_colors',
    ]));

    $wp_customize->add_setting('pva_color_link_hover', ['default' => '#ffffff']);
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'pva_color_link_hover', [
        'label' => __('Link Hover', 'publish-videos-api'),
        'section' => 'pva_colors',
    ]));

    $wp_customize->add_setting('pva_color_primary_hover', ['default' => '#ff1a1a']);
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'pva_color_primary_hover', [
        'label' => __('Primary Hover', 'publish-videos-api'),
        'section' => 'pva_colors',
    ]));

    $wp_customize->add_setting('pva_color_accent_hover', ['default' => '#d42029']);
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'pva_color_accent_hover', [
        'label' => __('Accent Hover', 'publish-videos-api'),
        'section' => 'pva_colors',
    ]));

    $wp_customize->add_section('pva_light_colors', [
        'title' => __('Light Mode Colors', 'publish-videos-api'),
        'priority' => 31,
    ]);

    $wp_customize->add_setting('pva_color_bg_light', ['default' => '#f7f7fb']);
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'pva_color_bg_light', [
        'label' => __('Background (Light)', 'publish-videos-api'),
        'section' => 'pva_light_colors',
    ]));

    $wp_customize->add_setting('pva_color_surface_light', ['default' => '#ffffff']);
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'pva_color_surface_light', [
        'label' => __('Surface (Light)', 'publish-videos-api'),
        'section' => 'pva_light_colors',
    ]));

    $wp_customize->add_setting('pva_color_text_light', ['default' => '#171717']);
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'pva_color_text_light', [
        'label' => __('Text (Light)', 'publish-videos-api'),
        'section' => 'pva_light_colors',
    ]));

    $wp_customize->add_setting('pva_color_link_light', ['default' => '#1b4cff']);
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'pva_color_link_light', [
        'label' => __('Link (Light)', 'publish-videos-api'),
        'section' => 'pva_light_colors',
    ]));

    $wp_customize->add_section('pva_layout', [
        'title' => __('Layout', 'publish-videos-api'),
        'priority' => 40,
    ]);

    $wp_customize->add_setting('pva_grid_cols_desktop', ['default' => 4]);
    $wp_customize->add_control('pva_grid_cols_desktop', [
        'label' => __('Columns Desktop', 'publish-videos-api'),
        'section' => 'pva_layout',
        'type' => 'select',
        'choices' => [
            3 => '3',
            4 => '4',
            5 => '5',
            6 => '6',
        ],
    ]);

    $wp_customize->add_setting('pva_grid_cols_tablet', ['default' => 3]);
    $wp_customize->add_control('pva_grid_cols_tablet', [
        'label' => __('Columns Tablet', 'publish-videos-api'),
        'section' => 'pva_layout',
        'type' => 'select',
        'choices' => [
            2 => '2',
            3 => '3',
            4 => '4',
        ],
    ]);

    $wp_customize->add_setting('pva_grid_cols_mobile', ['default' => 2]);
    $wp_customize->add_control('pva_grid_cols_mobile', [
        'label' => __('Columns Mobile', 'publish-videos-api'),
        'section' => 'pva_layout',
        'type' => 'select',
        'choices' => [
            1 => '1',
            2 => '2',
        ],
    ]);

    $wp_customize->add_setting('pva_posts_per_page', ['default' => 40]);
    $wp_customize->add_control('pva_posts_per_page', [
        'label' => __('Posts per page', 'publish-videos-api'),
        'section' => 'pva_layout',
        'type' => 'number',
        'input_attrs' => [
            'min' => 1,
            'max' => 40,
            'step' => 1,
        ],
    ]);

    $wp_customize->add_section('pva_card_style', [
        'title' => __('Card Style', 'publish-videos-api'),
        'priority' => 50,
    ]);

    $wp_customize->add_setting('pva_card_radius', ['default' => '12px']);
    $wp_customize->add_control('pva_card_radius', [
        'label' => __('Card Radius', 'publish-videos-api'),
        'section' => 'pva_card_style',
        'type' => 'text',
    ]);

    $wp_customize->add_setting('pva_card_shadow', ['default' => '0 8px 24px rgba(0,0,0,0.4)']);
    $wp_customize->add_control('pva_card_shadow', [
        'label' => __('Card Shadow', 'publish-videos-api'),
        'section' => 'pva_card_style',
        'type' => 'text',
    ]);

    $wp_customize->add_setting('pva_card_hover', ['default' => 'translateY(-4px) scale(1.01)']);
    $wp_customize->add_control('pva_card_hover', [
        'label' => __('Card Hover Transform', 'publish-videos-api'),
        'section' => 'pva_card_style',
        'type' => 'text',
    ]);

    $wp_customize->add_section('pva_dark_mode', [
        'title' => __('Dark Mode', 'publish-videos-api'),
        'priority' => 60,
    ]);

    $wp_customize->add_setting('pva_enable_dark_toggle', ['default' => true]);
    $wp_customize->add_control('pva_enable_dark_toggle', [
        'label' => __('Enable Dark Mode Toggle', 'publish-videos-api'),
        'section' => 'pva_dark_mode',
        'type' => 'checkbox',
    ]);

    $wp_customize->add_setting('pva_dark_mode_default', ['default' => 'auto']);
    $wp_customize->add_control('pva_dark_mode_default', [
        'label' => __('Default Mode', 'publish-videos-api'),
        'section' => 'pva_dark_mode',
        'type' => 'select',
        'choices' => [
            'auto' => __('Auto (System)', 'publish-videos-api'),
            'dark' => __('Dark', 'publish-videos-api'),
            'light' => __('Light', 'publish-videos-api'),
        ],
    ]);

    $wp_customize->add_section('pva_home_sections', [
        'title' => __('Homepage Sections', 'publish-videos-api'),
        'priority' => 70,
    ]);

    $wp_customize->add_setting('pva_show_hero', ['default' => true]);
    $wp_customize->add_control('pva_show_hero', [
        'label' => __('Show Hero', 'publish-videos-api'),
        'section' => 'pva_home_sections',
        'type' => 'checkbox',
    ]);

    $wp_customize->add_setting('pva_hero_bg_image_id', [
        'default' => 0,
        'sanitize_callback' => 'absint',
    ]);
    $wp_customize->add_control(new WP_Customize_Media_Control($wp_customize, 'pva_hero_bg_image_id', [
        'label' => __('Hero Background Image', 'publish-videos-api'),
        'description' => __('Recommended size: 1600×900px or larger.', 'publish-videos-api'),
        'section' => 'pva_home_sections',
        'mime_type' => 'image',
    ]));

    $wp_customize->add_setting('pva_hero_bg_size', [
        'default' => 'cover',
        'sanitize_callback' => 'publish_videos_api_sanitize_hero_bg_size',
    ]);
    $wp_customize->add_control('pva_hero_bg_size', [
        'label' => __('Hero Background Size', 'publish-videos-api'),
        'description' => __('Choose how the hero background image should scale.', 'publish-videos-api'),
        'section' => 'pva_home_sections',
        'type' => 'select',
        'choices' => [
            'cover' => __('Cover', 'publish-videos-api'),
            'contain' => __('Contain', 'publish-videos-api'),
            'auto' => __('Auto', 'publish-videos-api'),
        ],
    ]);

    $wp_customize->add_setting('pva_hero_featured_count', [
        'default' => 8,
        'sanitize_callback' => 'absint',
    ]);
    $wp_customize->add_control('pva_hero_featured_count', [
        'label' => __('Hero Featured Videos', 'publish-videos-api'),
        'section' => 'pva_home_sections',
        'type' => 'number',
        'input_attrs' => [
            'min' => 1,
            'max' => 30,
            'step' => 1,
        ],
    ]);

    $wp_customize->add_setting('pva_hero_trending_count', [
        'default' => 10,
        'sanitize_callback' => 'absint',
    ]);
    $wp_customize->add_control('pva_hero_trending_count', [
        'label' => __('Hero Trending Videos', 'publish-videos-api'),
        'section' => 'pva_home_sections',
        'type' => 'number',
        'input_attrs' => [
            'min' => 1,
            'max' => 30,
            'step' => 1,
        ],
    ]);

    $wp_customize->add_setting('pva_show_filters', ['default' => true]);
    $wp_customize->add_control('pva_show_filters', [
        'label' => __('Show Filters', 'publish-videos-api'),
        'section' => 'pva_home_sections',
        'type' => 'checkbox',
    ]);

    $wp_customize->add_setting('pva_show_categories', ['default' => true]);
    $wp_customize->add_control('pva_show_categories', [
        'label' => __('Show Categories', 'publish-videos-api'),
        'section' => 'pva_home_sections',
        'type' => 'checkbox',
    ]);

    $wp_customize->add_setting('pva_show_tags', ['default' => true]);
    $wp_customize->add_control('pva_show_tags', [
        'label' => __('Show Tags', 'publish-videos-api'),
        'section' => 'pva_home_sections',
        'type' => 'checkbox',
    ]);

    $wp_customize->add_section('pva_footer', [
        'title' => __('Footer', 'publish-videos-api'),
        'priority' => 75,
    ]);

    $wp_customize->add_setting('pva_footer_show_decor', ['default' => true]);
    $wp_customize->add_control('pva_footer_show_decor', [
        'label' => __('Show Footer Decoration', 'publish-videos-api'),
        'section' => 'pva_footer',
        'type' => 'checkbox',
    ]);

    $wp_customize->add_setting('pva_footer_tagline', [
        'default' => get_bloginfo('description'),
        'sanitize_callback' => 'sanitize_text_field',
    ]);
    $wp_customize->add_control('pva_footer_tagline', [
        'label' => __('Footer Tagline', 'publish-videos-api'),
        'section' => 'pva_footer',
        'type' => 'text',
    ]);

    $wp_customize->add_setting('pva_footer_brand_type', [
        'default' => 'logo',
        'sanitize_callback' => 'sanitize_text_field',
    ]);
    $wp_customize->add_control('pva_footer_brand_type', [
        'label' => __('Footer Brand Display', 'publish-videos-api'),
        'section' => 'pva_footer',
        'type' => 'select',
        'choices' => [
            'logo' => __('โลโก้เว็บไซต์', 'publish-videos-api'),
            'domain' => __('โดเมนเว็บไซต์', 'publish-videos-api'),
        ],
    ]);

    $wp_customize->add_setting('pva_footer_pills', [
        'default' => __('อัปเดตทุกวัน, คลิปใหม่มาไว, คุณภาพคมชัด', 'publish-videos-api'),
        'sanitize_callback' => 'sanitize_text_field',
    ]);
    $wp_customize->add_control('pva_footer_pills', [
        'label' => __('Footer Pills (comma separated)', 'publish-videos-api'),
        'section' => 'pva_footer',
        'type' => 'text',
    ]);

    $wp_customize->add_setting('pva_footer_show_menu', ['default' => true]);
    $wp_customize->add_control('pva_footer_show_menu', [
        'label' => __('Show Footer Menu', 'publish-videos-api'),
        'section' => 'pva_footer',
        'type' => 'checkbox',
    ]);

    $wp_customize->add_setting('pva_footer_cta_label', [
        'default' => __('ดูวิดีโอทั้งหมด', 'publish-videos-api'),
        'sanitize_callback' => 'sanitize_text_field',
    ]);
    $wp_customize->add_control('pva_footer_cta_label', [
        'label' => __('Footer CTA Label', 'publish-videos-api'),
        'section' => 'pva_footer',
        'type' => 'text',
    ]);

    $wp_customize->add_setting('pva_footer_cta_url', [
        'default' => get_post_type_archive_link('video'),
        'sanitize_callback' => 'esc_url_raw',
    ]);
    $wp_customize->add_control('pva_footer_cta_url', [
        'label' => __('Footer CTA URL', 'publish-videos-api'),
        'section' => 'pva_footer',
        'type' => 'url',
    ]);

    $wp_customize->add_setting('pva_footer_note', [
        'default' => __('7LS', 'publish-videos-api'),
        'sanitize_callback' => 'sanitize_text_field',
    ]);
    $wp_customize->add_control('pva_footer_note', [
        'label' => __('Footer Note', 'publish-videos-api'),
        'section' => 'pva_footer',
        'type' => 'text',
    ]);

    $wp_customize->add_setting('pva_footer_orb_primary', [
        'default' => '#e50914',
        'sanitize_callback' => 'sanitize_hex_color',
    ]);
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'pva_footer_orb_primary', [
        'label' => __('Footer Accent Glow', 'publish-videos-api'),
        'section' => 'pva_footer',
    ]));

    $wp_customize->add_setting('pva_footer_orb_secondary', [
        'default' => '#b81d24',
        'sanitize_callback' => 'sanitize_hex_color',
    ]);
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'pva_footer_orb_secondary', [
        'label' => __('Footer Secondary Glow', 'publish-videos-api'),
        'section' => 'pva_footer',
    ]));

    $wp_customize->add_setting('pva_footer_orb_neutral', [
        'default' => '#ffffff',
        'sanitize_callback' => 'sanitize_hex_color',
    ]);
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'pva_footer_orb_neutral', [
        'label' => __('Footer Neutral Glow', 'publish-videos-api'),
        'section' => 'pva_footer',
    ]));

    $wp_customize->add_panel('pva_preroll_panel', [
        'title' => __('Pre-roll Ads', 'publish-videos-api'),
        'priority' => 80,
    ]);

    $wp_customize->add_section('pva_preroll_overlay', [
        'title' => __('Picture Overlay', 'publish-videos-api'),
        'priority' => 2,
        'panel' => 'pva_preroll_panel',
    ]);

    $wp_customize->add_section('pva_preroll_video', [
        'title' => __('Video Ads', 'publish-videos-api'),
        'priority' => 3,
        'panel' => 'pva_preroll_panel',
    ]);

    $wp_customize->add_setting('pva_preroll_enabled', ['default' => false]);
    $wp_customize->add_control('pva_preroll_enabled', [
        'label' => __('Enable Pre-roll', 'publish-videos-api'),
        'section' => 'pva_preroll_overlay',
        'type' => 'checkbox',
    ]);

    $wp_customize->add_setting('pva_preroll_overlay_enabled', ['default' => true]);
    $wp_customize->add_control('pva_preroll_overlay_enabled', [
        'label' => __('Enable Picture Overlay', 'publish-videos-api'),
        'section' => 'pva_preroll_overlay',
        'type' => 'checkbox',
    ]);

    $wp_customize->add_setting('pva_preroll_video_enabled', ['default' => false]);
    $wp_customize->add_control('pva_preroll_video_enabled', [
        'label' => __('Enable Video Ads', 'publish-videos-api'),
        'section' => 'pva_preroll_video',
        'type' => 'checkbox',
    ]);

    $wp_customize->add_setting('pva_preroll_image_url', [
        'default' => '',
        'sanitize_callback' => 'esc_url_raw',
    ]);
    $wp_customize->add_control('pva_preroll_image_url', [
        'label' => __('Overlay Image URL', 'publish-videos-api'),
        'section' => 'pva_preroll_overlay',
        'type' => 'url',
    ]);

    $wp_customize->add_setting('pva_preroll_image_id', [
        'default' => 0,
        'sanitize_callback' => 'absint',
    ]);
    $wp_customize->add_control(new WP_Customize_Media_Control($wp_customize, 'pva_preroll_image_id', [
        'label' => __('Overlay Image (Media Library)', 'publish-videos-api'),
        'section' => 'pva_preroll_overlay',
        'mime_type' => 'image',
    ]));

    $wp_customize->add_setting('pva_preroll_target_url', [
        'default' => '',
        'sanitize_callback' => 'esc_url_raw',
    ]);
    $wp_customize->add_control('pva_preroll_target_url', [
        'label' => __('Overlay Target URL', 'publish-videos-api'),
        'section' => 'pva_preroll_overlay',
        'type' => 'url',
    ]);

    $wp_customize->add_setting('pva_preroll_signup_url', [
        'default' => '',
        'sanitize_callback' => 'esc_url_raw',
    ]);
    $wp_customize->add_control('pva_preroll_signup_url', [
        'label' => __('Signup URL', 'publish-videos-api'),
        'section' => 'pva_preroll_overlay',
        'type' => 'url',
    ]);

    $wp_customize->add_setting('pva_preroll_overlay_closeable', ['default' => true]);
    $wp_customize->add_control('pva_preroll_overlay_closeable', [
        'label' => __('Overlay closeable', 'publish-videos-api'),
        'section' => 'pva_preroll_overlay',
        'type' => 'checkbox',
    ]);

    for ($index = 1; $index <= 5; $index++) {
        $section_id = "pva_preroll_video_set_{$index}";
        $wp_customize->add_section($section_id, [
            'title' => sprintf(__('Video Ad Set %d', 'publish-videos-api'), $index),
            'priority' => 10 + $index,
            'panel' => 'pva_preroll_panel',
        ]);

        $wp_customize->add_setting("pva_preroll_video_ad_{$index}_media_id", [
            'default' => 0,
            'sanitize_callback' => 'absint',
        ]);
        $wp_customize->add_control(new WP_Customize_Media_Control($wp_customize, "pva_preroll_video_ad_{$index}_media_id", [
            'label' => sprintf(__('Video Ad %d', 'publish-videos-api'), $index),
            'description' => $index === 1
                ? __('เลือกวิดีโอโฆษณาแต่ละชุด (เว้นว่างได้ถ้าไม่ใช้)', 'publish-videos-api')
                : '',
            'section' => $section_id,
            'mime_type' => 'video',
        ]));

        $wp_customize->add_setting("pva_preroll_video_ad_{$index}_target_url", [
            'default' => '',
            'sanitize_callback' => 'esc_url_raw',
        ]);
        $wp_customize->add_control("pva_preroll_video_ad_{$index}_target_url", [
            'label' => sprintf(__('Target URL %d', 'publish-videos-api'), $index),
            'section' => $section_id,
            'type' => 'url',
        ]);

        $wp_customize->add_setting("pva_preroll_video_ad_{$index}_skip_after", ['default' => 3]);
        $wp_customize->add_control("pva_preroll_video_ad_{$index}_skip_after", [
            'label' => sprintf(__('Skip After (seconds) %d', 'publish-videos-api'), $index),
            'section' => $section_id,
            'type' => 'number',
        ]);
    }

    $wp_customize->add_setting('pva_preroll_frequency_minutes', ['default' => 30]);
    $wp_customize->add_control('pva_preroll_frequency_minutes', [
        'label' => __('Frequency (minutes)', 'publish-videos-api'),
        'section' => 'pva_preroll_overlay',
        'type' => 'number',
    ]);

    $wp_customize->add_setting('pva_preroll_once_per_session', ['default' => true]);
    $wp_customize->add_control('pva_preroll_once_per_session', [
        'label' => __('Once per session', 'publish-videos-api'),
        'section' => 'pva_preroll_overlay',
        'type' => 'checkbox',
    ]);

    $wp_customize->add_setting('pva_preroll_include_terms', ['default' => '']);
    $wp_customize->add_control('pva_preroll_include_terms', [
        'label' => __('Include terms (slug or ID, comma)', 'publish-videos-api'),
        'section' => 'pva_preroll_overlay',
        'type' => 'text',
    ]);

    $wp_customize->add_setting('pva_preroll_exclude_terms', ['default' => '']);
    $wp_customize->add_control('pva_preroll_exclude_terms', [
        'label' => __('Exclude terms (slug or ID, comma)', 'publish-videos-api'),
        'section' => 'pva_preroll_overlay',
        'type' => 'text',
    ]);

    $wp_customize->add_setting('pva_preroll_include_posts', ['default' => '']);
    $wp_customize->add_control('pva_preroll_include_posts', [
        'label' => __('Include posts (IDs, comma)', 'publish-videos-api'),
        'section' => 'pva_preroll_overlay',
        'type' => 'text',
    ]);

    $wp_customize->add_setting('pva_preroll_exclude_posts', ['default' => '']);
    $wp_customize->add_control('pva_preroll_exclude_posts', [
        'label' => __('Exclude posts (IDs, comma)', 'publish-videos-api'),
        'section' => 'pva_preroll_overlay',
        'type' => 'text',
    ]);
}
add_action('customize_register', 'publish_videos_api_customize_register');

function publish_videos_api_get_terms(string $taxonomy, int $parent = 0): array
{
    $args = [
        'taxonomy' => $taxonomy,
        'hide_empty' => true,
    ];

    if ($parent > 0) {
        $args['parent'] = $parent;
    }

    $terms = get_terms($args);
    return is_array($terms) ? $terms : [];
}

/**
 * ประกาศ AV context ให้ plugin 7LS-Video-Publisher รับรู้โดยอัตโนมัติ
 * Plugin จะล็อก content_mode = 'av_movie' ตลอดเวลาที่ theme นี้ active
 */
add_filter('sevenls_vp_forced_mode', function (): string {
    return 'av_movie';
});
