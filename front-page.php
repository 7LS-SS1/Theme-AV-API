<?php
get_header();

$archive_url = get_post_type_archive_link('video');
if (!$archive_url) {
    $archive_url = home_url('/');
}

$hero_tiles = [];
$hero_tiles_query = new WP_Query([
    'post_type' => 'video',
    'post_status' => 'publish',
    'posts_per_page' => 24,
    'orderby' => 'date',
    'order' => 'DESC',
    'ignore_sticky_posts' => true,
    'no_found_rows' => true,
]);

if ($hero_tiles_query->have_posts()) {
    while ($hero_tiles_query->have_posts()) {
        $hero_tiles_query->the_post();
        $post_id = get_the_ID();
        $thumb_url = publish_videos_api_get_video_thumbnail_url($post_id);
        if (!$thumb_url) {
            $thumb_url = publish_videos_api_get_placeholder_url();
        }

        $hero_tiles[] = [
            'title' => get_the_title($post_id),
            'url' => get_permalink($post_id),
            'thumb' => $thumb_url,
        ];
    }
}
wp_reset_postdata();

$hero_featured_query = new WP_Query([
    'post_type' => 'video',
    'post_status' => 'publish',
    'posts_per_page' => 1,
    'meta_key' => '_sevenls_vp_featured',
    'meta_value' => '1',
    'ignore_sticky_posts' => true,
    'no_found_rows' => true,
]);

if (!$hero_featured_query->have_posts()) {
    $hero_featured_query = new WP_Query([
        'post_type' => 'video',
        'post_status' => 'publish',
        'posts_per_page' => 1,
        'orderby' => 'date',
        'order' => 'DESC',
        'ignore_sticky_posts' => true,
        'no_found_rows' => true,
    ]);
}

$hero_video = null;
if ($hero_featured_query->have_posts()) {
    $hero_featured_query->the_post();
    $post_id = get_the_ID();
    $categories = get_the_terms($post_id, 'video_category');
    $primary_category = (!empty($categories) && !is_wp_error($categories)) ? $categories[0]->name : '';
    $excerpt_source = get_the_excerpt($post_id);
    if (!$excerpt_source) {
        $excerpt_source = (string) get_post_field('post_content', $post_id);
    }

    $hero_video = [
        'title' => get_the_title($post_id),
        'url' => get_permalink($post_id),
        'summary' => wp_trim_words(wp_strip_all_tags($excerpt_source), 34, '...'),
        'year' => get_the_date('Y', $post_id),
        'duration' => publish_videos_api_get_video_duration($post_id),
        'views' => publish_videos_api_get_video_views($post_id),
        'category' => $primary_category,
    ];
}
wp_reset_postdata();

$hero_terms = get_terms([
    'taxonomy' => 'video_category',
    'hide_empty' => true,
    'number' => 5,
]);

if (is_wp_error($hero_terms)) {
    $hero_terms = [];
}

$live_query = new WP_Query([
    'post_type' => 'video',
    'post_status' => 'publish',
    'posts_per_page' => 10,
    'orderby' => 'date',
    'order' => 'DESC',
    'ignore_sticky_posts' => true,
    'no_found_rows' => true,
]);

$popular_query = new WP_Query([
    'post_type' => 'video',
    'post_status' => 'publish',
    'posts_per_page' => 12,
    'meta_key' => '_sevenls_vp_views',
    'orderby' => 'meta_value_num',
    'order' => 'DESC',
    'ignore_sticky_posts' => true,
    'no_found_rows' => true,
]);

$for_you_query = new WP_Query([
    'post_type' => 'video',
    'post_status' => 'publish',
    'posts_per_page' => 12,
    'orderby' => 'date',
    'order' => 'DESC',
    'offset' => 6,
    'ignore_sticky_posts' => true,
    'no_found_rows' => true,
]);

$render_row = static function (string $section_id, string $title, string $subtitle, WP_Query $query, string $archive_url): void {
    if (!$query->have_posts()) {
        return;
    }
    ?>
    <section id="<?php echo esc_attr($section_id); ?>" class="home-stream__section">
        <header class="home-stream__section-head">
            <div>
                <h2><?php echo esc_html($title); ?></h2>
                <p><?php echo esc_html($subtitle); ?></p>
            </div>
            <a class="home-stream__see-all" href="<?php echo esc_url($archive_url); ?>">
                <?php esc_html_e('See all', 'publish-videos-api'); ?>
                <span aria-hidden="true">→</span>
            </a>
        </header>

        <div class="home-stream__track" role="list">
            <?php while ($query->have_posts()) : ?>
                <?php $query->the_post(); ?>
                <div class="home-stream__item" role="listitem">
                    <?php get_template_part('template-parts/content', 'video-card'); ?>
                </div>
            <?php endwhile; ?>
        </div>
    </section>
    <?php
    wp_reset_postdata();
};

$hero_title = $hero_video['title'] ?? __('Download Unlimited Movies, Drama and More Content.', 'publish-videos-api');
$hero_summary = $hero_video['summary'] ?? __('Enjoy premium clips, trending scenes and fresh updates from your library.', 'publish-videos-api');
$hero_link = $hero_video['url'] ?? $archive_url;
$hero_category = $hero_video['category'] ?? '';
$hero_year = $hero_video['year'] ?? '';
$hero_duration = $hero_video['duration'] ?? '';
$hero_views = isset($hero_video['views']) ? (int) $hero_video['views'] : 0;
$has_rows = $live_query->have_posts() || $popular_query->have_posts() || $for_you_query->have_posts();
?>

<div class="home-stream">
    <section class="home-stream__hero" aria-label="<?php esc_attr_e('Hero', 'publish-videos-api'); ?>">
        <div class="home-stream__hero-wall" aria-hidden="true">
            <?php if (!empty($hero_tiles)) : ?>
                <?php foreach ($hero_tiles as $tile) : ?>
                    <span class="home-stream__hero-tile">
                        <img src="<?php echo esc_url($tile['thumb']); ?>" alt="" loading="lazy" decoding="async">
                    </span>
                <?php endforeach; ?>
            <?php else : ?>
                <?php for ($i = 0; $i < 20; $i++) : ?>
                    <span class="home-stream__hero-tile home-stream__hero-tile--fallback"></span>
                <?php endfor; ?>
            <?php endif; ?>
        </div>
        <div class="home-stream__hero-shade"></div>

        <div class="home-stream__hero-content">
            <div class="home-stream__hero-top">
                <span class="home-stream__hero-brand"><?php bloginfo('name'); ?></span>
                <nav class="home-stream__hero-menu" aria-label="<?php esc_attr_e('หมวดหมู่หน้าแรก', 'publish-videos-api'); ?>">
                    <a class="is-active" href="<?php echo esc_url(home_url('/')); ?>">
                        <?php esc_html_e('Home', 'publish-videos-api'); ?>
                    </a>
                    <?php foreach ($hero_terms as $term) : ?>
                        <?php
                        $term_link = get_term_link($term);
                        if (is_wp_error($term_link)) {
                            continue;
                        }
                        ?>
                        <a href="<?php echo esc_url($term_link); ?>"><?php echo esc_html($term->name); ?></a>
                    <?php endforeach; ?>
                </nav>
                <a class="home-stream__signin" href="<?php echo esc_url(wp_login_url()); ?>">
                    <?php esc_html_e('Sign In', 'publish-videos-api'); ?>
                </a>
            </div>

            <div class="home-stream__copy">
                <p class="home-stream__eyebrow"><?php esc_html_e('Welcome to Theme AV API', 'publish-videos-api'); ?></p>
                <h1><?php echo esc_html($hero_title); ?></h1>
                <p class="home-stream__summary"><?php echo esc_html($hero_summary); ?></p>
                <div class="home-stream__meta">
                    <?php if ($hero_category !== '') : ?>
                        <span><?php echo esc_html($hero_category); ?></span>
                    <?php endif; ?>
                    <?php if ($hero_year !== '') : ?>
                        <span><?php echo esc_html($hero_year); ?></span>
                    <?php endif; ?>
                    <?php if ($hero_duration !== '') : ?>
                        <span><?php echo esc_html($hero_duration); ?></span>
                    <?php endif; ?>
                    <?php if ($hero_views > 0) : ?>
                        <span><?php echo esc_html(publish_videos_api_format_views($hero_views)); ?> <?php esc_html_e('views', 'publish-videos-api'); ?></span>
                    <?php endif; ?>
                </div>

                <form role="search" method="get" class="home-stream__subscribe" action="<?php echo esc_url(home_url('/')); ?>">
                    <label class="screen-reader-text" for="home-video-search"><?php esc_html_e('ค้นหาวิดีโอ', 'publish-videos-api'); ?></label>
                    <span class="home-stream__prefix" aria-hidden="true">&#128269;</span>
                    <input id="home-video-search" type="search" name="s" value="<?php echo esc_attr(get_search_query()); ?>" placeholder="<?php esc_attr_e('ค้นหาหนัง ซีรีส์ หรือนักแสดง...', 'publish-videos-api'); ?>">
                    <input type="hidden" name="post_type" value="video">
                    <button type="submit"><?php esc_html_e('ค้นหา', 'publish-videos-api'); ?></button>
                </form>

                <div class="home-stream__actions">
                    <a class="btn btn-primary" href="<?php echo esc_url($hero_link); ?>">
                        <?php esc_html_e('Watch now', 'publish-videos-api'); ?>
                    </a>
                    <a class="btn btn-outline" href="<?php echo esc_url($archive_url); ?>">
                        <?php esc_html_e('Browse Library', 'publish-videos-api'); ?>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <?php if ($has_rows) : ?>
        <?php
        $render_row(
            'live-show',
            __('Live Show', 'publish-videos-api'),
            __('Fresh videos from the latest releases.', 'publish-videos-api'),
            $live_query,
            $archive_url
        );

        $render_row(
            'most-popular',
            __('Most Popular', 'publish-videos-api'),
            __('Top viewed picks right now.', 'publish-videos-api'),
            $popular_query,
            $archive_url
        );

        $render_row(
            'movies-for-you',
            __('Movies for you', 'publish-videos-api'),
            __('Curated recommendations to keep watching.', 'publish-videos-api'),
            $for_you_query,
            $archive_url
        );
        ?>
    <?php else : ?>
        <p class="home-stream__empty"><?php esc_html_e('ยังไม่มีวิดีโอสำหรับแสดงผลในตอนนี้', 'publish-videos-api'); ?></p>
    <?php endif; ?>
</div>

<?php
get_footer();
