<?php
get_header();

$archive_url = get_post_type_archive_link('video') ?: home_url('/');

/* ── Featured videos for hero slider ─────────────────────── */
$hero_query = new WP_Query([
    'post_type'           => 'video',
    'post_status'         => 'publish',
    'posts_per_page'      => 8,
    'meta_key'            => '_sevenls_vp_featured',
    'meta_value'          => '1',
    'ignore_sticky_posts' => true,
    'no_found_rows'       => true,
]);

if (!$hero_query->have_posts()) {
    $hero_query = new WP_Query([
        'post_type'           => 'video',
        'post_status'         => 'publish',
        'posts_per_page'      => 8,
        'orderby'             => 'date',
        'order'               => 'DESC',
        'ignore_sticky_posts' => true,
        'no_found_rows'       => true,
    ]);
}

$hero_slides = [];

if ($hero_query->have_posts()) {
    while ($hero_query->have_posts()) {
        $hero_query->the_post();
        $post_id = get_the_ID();

        /* Thumbnail */
        $thumb_url = '';
        if (has_post_thumbnail($post_id)) {
            $thumb_url = (string) get_the_post_thumbnail_url($post_id, 'video-hero');
        }
        if (!$thumb_url) {
            $thumb_url = (string) get_post_meta($post_id, '_sevenls_vp_thumbnail_url', true);
        }
        if (!$thumb_url) {
            $thumb_url = publish_videos_api_get_placeholder_url();
        }

        /* Excerpt */
        $excerpt = get_the_excerpt($post_id);
        if (!$excerpt) {
            $excerpt = (string) get_post_field('post_content', $post_id);
        }
        $excerpt = wp_trim_words(wp_strip_all_tags($excerpt), 28, '...');

        /* Taxonomies */
        $categories = get_the_terms($post_id, 'video_category');
        $actors     = get_the_terms($post_id, 'video_actor');
        $tags_terms = get_the_terms($post_id, 'video_tag');

        /* IMDB / Rating */
        $rating = '';
        foreach (['_sevenls_vp_imdb_rating', '_sevenls_vp_rating', 'imdb_rating', 'rating'] as $rkey) {
            $r = get_post_meta($post_id, $rkey, true);
            if ($r !== '' && is_numeric($r)) {
                $rating = number_format((float) $r, 1);
                break;
            }
        }

        /* Duration formatted as "2hr : 22mins" */
        $duration_raw = publish_videos_api_get_video_duration($post_id);
        $duration_fmt = '';
        if ($duration_raw !== '') {
            $parts = explode(':', $duration_raw);
            if (count($parts) === 3) {
                $h = (int) $parts[0];
                $m = (int) $parts[1];
                $duration_fmt = $h . 'hr : ' . $m . 'mins';
            } elseif (count($parts) === 2) {
                $m = (int) $parts[0];
                $s = (int) $parts[1];
                $duration_fmt = $m > 0 ? $m . 'mins' : $s . 'sec';
            } else {
                $duration_fmt = $duration_raw;
            }
        }

        /* Age rating (GP, PG, R, etc.) */
        $age_rating = '';
        foreach (['_sevenls_vp_age_rating', '_sevenls_vp_rating_code', 'age_rating', 'content_rating'] as $akey) {
            $ar = get_post_meta($post_id, $akey, true);
            if ($ar) {
                $age_rating = strtoupper(sanitize_text_field((string) $ar));
                break;
            }
        }

        $hero_slides[] = [
            'id'         => $post_id,
            'title'      => get_the_title($post_id),
            'url'        => get_permalink($post_id),
            'thumb'      => $thumb_url,
            'excerpt'    => $excerpt,
            'rating'     => $rating,
            'duration'   => $duration_fmt,
            'age_rating' => $age_rating,
            'categories' => (!empty($categories) && !is_wp_error($categories)) ? $categories : [],
            'actors'     => (!empty($actors)     && !is_wp_error($actors))     ? $actors     : [],
            'tags'       => (!empty($tags_terms) && !is_wp_error($tags_terms)) ? $tags_terms : [],
        ];
    }
}
wp_reset_postdata();

/* ── Latest movies row ────────────────────────────────────── */
$latest_query = new WP_Query([
    'post_type'           => 'video',
    'post_status'         => 'publish',
    'posts_per_page'      => 14,
    'orderby'             => 'date',
    'order'               => 'DESC',
    'ignore_sticky_posts' => true,
    'no_found_rows'       => true,
]);

/* ── Weekly popular row (20 videos) ───────────────────────── */
$weekly_popular_query = new WP_Query([
    'post_type'           => 'video',
    'post_status'         => 'publish',
    'posts_per_page'      => 20,
    'meta_key'            => '_sevenls_vp_views',
    'orderby'             => 'meta_value_num',
    'order'               => 'DESC',
    'date_query'          => [
        [
            'after' => '7 days ago',
        ],
    ],
    'ignore_sticky_posts' => true,
    'no_found_rows'       => true,
]);

if (!$weekly_popular_query->have_posts()) {
    $weekly_popular_query = new WP_Query([
        'post_type'           => 'video',
        'post_status'         => 'publish',
        'posts_per_page'      => 20,
        'meta_key'            => '_sevenls_vp_views',
        'orderby'             => 'meta_value_num',
        'order'               => 'DESC',
        'ignore_sticky_posts' => true,
        'no_found_rows'       => true,
    ]);
}

/* ── All categories row (40 videos) ──────────────────────── */
$av_movies_query = new WP_Query([
    'post_type'           => 'video',
    'post_status'         => 'publish',
    'posts_per_page'      => 40,
    'orderby'             => 'date',
    'order'               => 'DESC',
    'ignore_sticky_posts' => true,
    'no_found_rows'       => true,
]);

$popular_view_all_url = add_query_arg('sort', 'popular', $archive_url);
$av_view_all_url = $archive_url;
?>

<div class="streamit-page">

    <?php if (!empty($hero_slides)) : ?>
    <!-- ═══════════════ HERO SLIDER ═══════════════ -->
    <section class="streamit-hero" data-streamit-slider aria-label="<?php esc_attr_e('Featured', 'publish-videos-api'); ?>">

        <?php foreach ($hero_slides as $idx => $slide) : ?>
        <div class="streamit-hero__slide <?php echo $idx === 0 ? 'is-active' : ''; ?>"
             style="--slide-bg: url('<?php echo esc_url($slide['thumb']); ?>')"
             data-slide-index="<?php echo esc_attr((string) $idx); ?>"
             aria-hidden="<?php echo $idx === 0 ? 'false' : 'true'; ?>">

            <div class="streamit-hero__overlay" aria-hidden="true"></div>

            <div class="streamit-hero__body">

                <!-- ── Left content ── -->
                <div class="streamit-hero__left">

                    <!-- Brand badge -->
                    <div class="streamit-hero__brand" aria-hidden="true">
                        <span class="streamit-hero__brand-dot"></span>
                        <span class="streamit-hero__brand-name"><?php bloginfo('name'); ?></span>
                    </div>

                    <!-- Movie title -->
                    <h1 class="streamit-hero__title"><?php echo esc_html($slide['title']); ?></h1>

                    <!-- Ratings row -->
                    <?php if ($slide['rating'] || $slide['age_rating'] || $slide['duration']) : ?>
                    <div class="streamit-hero__ratings">
                        <?php if ($slide['rating']) : ?>
                            <div class="streamit-hero__stars" aria-label="<?php echo esc_attr($slide['rating']); ?> out of 10">
                                <?php
                                $star_val = (float) $slide['rating'] / 2;
                                for ($i = 1; $i <= 5; $i++) :
                                    if ($star_val >= $i) {
                                        echo '<span class="sh-star sh-star--full" aria-hidden="true">&#9733;</span>';
                                    } elseif ($star_val >= $i - 0.5) {
                                        echo '<span class="sh-star sh-star--half" aria-hidden="true">&#9733;</span>';
                                    } else {
                                        echo '<span class="sh-star sh-star--empty" aria-hidden="true">&#9734;</span>';
                                    }
                                endfor;
                                ?>
                            </div>
                            <span class="streamit-hero__imdb-score"><?php echo esc_html($slide['rating']); ?>(Imdb)</span>
                        <?php endif; ?>

                        <?php if ($slide['age_rating']) : ?>
                            <span class="streamit-hero__age-badge"><?php echo esc_html($slide['age_rating']); ?></span>
                        <?php endif; ?>

                        <?php if ($slide['duration']) : ?>
                            <span class="streamit-hero__duration"><?php echo esc_html($slide['duration']); ?></span>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <!-- Description -->
                    <?php if ($slide['excerpt']) : ?>
                    <p class="streamit-hero__excerpt"><?php echo esc_html($slide['excerpt']); ?></p>
                    <?php endif; ?>

                    <!-- Meta rows: Starring / Genres / Tags -->
                    <?php
                    $has_actors = !empty($slide['actors']);
                    $has_cats   = !empty($slide['categories']);
                    $has_tags   = !empty($slide['tags']);
                    if ($has_actors || $has_cats || $has_tags) :
                    ?>
                    <div class="streamit-hero__meta-rows">
                        <?php if ($has_actors) : ?>
                        <div class="streamit-hero__meta-row">
                            <span class="streamit-hero__meta-label"><?php esc_html_e('Starring:', 'publish-videos-api'); ?></span>
                            <span class="streamit-hero__meta-value">
                                <?php echo esc_html(implode(', ', array_map(static fn($t) => $t->name, array_slice($slide['actors'], 0, 3)))); ?>
                            </span>
                        </div>
                        <?php endif; ?>

                        <?php if ($has_cats) : ?>
                        <div class="streamit-hero__meta-row">
                            <span class="streamit-hero__meta-label"><?php esc_html_e('Genres:', 'publish-videos-api'); ?></span>
                            <span class="streamit-hero__meta-value">
                                <?php echo esc_html(implode(', ', array_map(static fn($t) => $t->name, array_slice($slide['categories'], 0, 3)))); ?>
                            </span>
                        </div>
                        <?php endif; ?>

                        <?php if ($has_tags) : ?>
                        <div class="streamit-hero__meta-row">
                            <span class="streamit-hero__meta-label"><?php esc_html_e('Tag:', 'publish-videos-api'); ?></span>
                            <span class="streamit-hero__meta-value">
                                <?php echo esc_html(implode(', ', array_map(static fn($t) => $t->name, array_slice($slide['tags'], 0, 4)))); ?>
                            </span>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <!-- Play Now button -->
                    <div class="streamit-hero__actions">
                        <a class="streamit-hero__play-btn" href="<?php echo esc_url($slide['url']); ?>">
                            <svg width="14" height="14" viewBox="0 0 14 14" fill="currentColor" aria-hidden="true" focusable="false"><path d="M2 1.5l10 5.5-10 5.5V1.5z"/></svg>
                            <?php esc_html_e('Play Now', 'publish-videos-api'); ?>
                        </a>
                    </div>
                </div>

                <!-- ── Right: Watch Trailer ── -->
                <div class="streamit-hero__right">
                    <a class="streamit-hero__trailer-btn" href="<?php echo esc_url($slide['url']); ?>">
                        <span class="streamit-hero__trailer-circle" aria-hidden="true">
                            <svg width="30" height="30" viewBox="0 0 30 30" fill="currentColor" focusable="false"><path d="M5 3l22 12L5 27V3z"/></svg>
                        </span>
                        <?php esc_html_e('Watch Trailer', 'publish-videos-api'); ?>
                    </a>
                </div>

            </div><!-- /.streamit-hero__body -->
        </div><!-- /.streamit-hero__slide -->
        <?php endforeach; ?>

        <?php if (count($hero_slides) > 1) : ?>
        <!-- Slider arrows (outside slides) -->
        <button class="streamit-hero__arrow streamit-hero__arrow--prev" type="button" data-streamit-prev aria-label="<?php esc_attr_e('Previous', 'publish-videos-api'); ?>">&#8249;</button>
        <button class="streamit-hero__arrow streamit-hero__arrow--next" type="button" data-streamit-next aria-label="<?php esc_attr_e('Next', 'publish-videos-api'); ?>">&#8250;</button>
        <?php endif; ?>

    </section>
    <?php endif; ?>

    <!-- ═══════════════ LATEST MOVIES ROW ═══════════════ -->
    <?php if ($latest_query->have_posts()) : ?>
    <section class="streamit-row" id="latest">
        <div class="streamit-row__head">
            <h2 class="streamit-row__title"><?php esc_html_e('Latest Movies', 'publish-videos-api'); ?></h2>
            <a class="streamit-row__view-all" href="<?php echo esc_url($archive_url); ?>"><?php esc_html_e('View All', 'publish-videos-api'); ?></a>
        </div>
        <div class="streamit-row__track" role="list">
            <?php while ($latest_query->have_posts()) : ?>
                <?php $latest_query->the_post(); ?>
                <div class="streamit-row__item" role="listitem">
                    <?php get_template_part('template-parts/content', 'video-card'); ?>
                </div>
            <?php endwhile; ?>
            <?php wp_reset_postdata(); ?>
        </div>
    </section>
    <?php endif; ?>

    <?php if ($weekly_popular_query->have_posts()) : ?>
    <section class="streamit-row" id="weekly-popular">
        <div class="streamit-row__head">
            <h2 class="streamit-row__title"><?php esc_html_e('ยอดนิยมในสัปดาห์นี้', 'publish-videos-api'); ?></h2>
            <a class="streamit-row__view-all" href="<?php echo esc_url($popular_view_all_url); ?>"><?php esc_html_e('View All', 'publish-videos-api'); ?></a>
        </div>
        <div class="streamit-row__track" role="list">
            <?php while ($weekly_popular_query->have_posts()) : ?>
                <?php $weekly_popular_query->the_post(); ?>
                <div class="streamit-row__item" role="listitem">
                    <?php get_template_part('template-parts/content', 'video-card'); ?>
                </div>
            <?php endwhile; ?>
            <?php wp_reset_postdata(); ?>
        </div>
    </section>
    <?php endif; ?>

    <?php if ($av_movies_query->have_posts()) : ?>
    <section class="streamit-row" id="av-movies">
        <div class="streamit-row__head">
            <h2 class="streamit-row__title"><?php esc_html_e('หมวดหมู่ทั้งหมด', 'publish-videos-api'); ?></h2>
            <a class="streamit-row__view-all" href="<?php echo esc_url($av_view_all_url); ?>"><?php esc_html_e('View All', 'publish-videos-api'); ?></a>
        </div>
        <div class="streamit-row__track" role="list">
            <?php while ($av_movies_query->have_posts()) : ?>
                <?php $av_movies_query->the_post(); ?>
                <div class="streamit-row__item" role="listitem">
                    <?php get_template_part('template-parts/content', 'video-card'); ?>
                </div>
            <?php endwhile; ?>
            <?php wp_reset_postdata(); ?>
        </div>
    </section>
    <?php endif; ?>

</div><!-- /.streamit-page -->

<?php get_footer(); ?>
