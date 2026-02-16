<?php
get_header();
?>

<?php if (have_posts()) : ?>
    <?php while (have_posts()) : the_post(); ?>
        <?php get_template_part('template-parts/breadcrumbs'); ?>

        <?php
        $post_id = get_the_ID();
        $duration = publish_videos_api_get_video_duration($post_id);
        $views = publish_videos_api_get_video_views($post_id);
        $external_id = get_post_meta($post_id, '_sevenls_vp_external_id', true);
        $preroll_allowed = publish_videos_api_is_allowed_preroll($post_id) ? 'true' : 'false';
        $author_id = (int) get_the_author_meta('ID');
        $author_name = get_the_author();
        $author_url = $author_id ? get_author_posts_url($author_id) : '';
        $author_video_count = $author_id ? count_user_posts($author_id, 'video') : 0;
        $likes = publish_videos_api_get_video_likes($post_id);
        ?>

        <article <?php post_class('single-video single-video--stacked'); ?>>
            <div class="single-video__player-wrap">
                <div class="video-player" data-preroll-enabled="<?php echo esc_attr($preroll_allowed); ?>">
                    <div class="preroll" hidden>
                        <div class="preroll__media"></div>
                        <div class="preroll__actions">
                            <a class="preroll__cta" href="#" target="_blank" rel="noopener" hidden>
                                <?php esc_html_e('สมัคร', 'publish-videos-api'); ?>
                            </a>
                            <button class="preroll__skip" type="button" disabled>
                                <?php esc_html_e('ข้ามโฆษณา', 'publish-videos-api'); ?>
                            </button>
                        </div>
                    </div>
                    <?php echo do_shortcode('[sevenls_video_post id="' . $post_id . '"]'); ?>
                </div>
            </div>

            <div class="single-video__info">
                <h1 class="video-summary__title"><?php the_title(); ?></h1>

                <div class="video-summary__row">
                    <div class="video-summary__meta">
                        <?php if ($views) : ?>
                            <span><?php echo esc_html(publish_videos_api_format_views($views)); ?> <?php esc_html_e('วิว', 'publish-videos-api'); ?></span>
                        <?php endif; ?>
                        <span><?php echo esc_html(get_the_date()); ?></span>
                        <?php if ($duration) : ?>
                            <span><?php echo esc_html($duration); ?></span>
                        <?php endif; ?>
                        <?php if ($external_id) : ?>
                            <span><?php echo esc_html($external_id); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="video-summary__actions">
                        <button class="btn btn-ghost" type="button" data-copy-link><?php esc_html_e('แชร์', 'publish-videos-api'); ?></button>
                        <button class="btn btn-outline" type="button" data-save-video><?php esc_html_e('บันทึก', 'publish-videos-api'); ?></button>
                    </div>
                </div>

                <div class="video-summary__channel">
                    <?php if ($author_id) : ?>
                        <a class="channel-avatar" href="<?php echo esc_url($author_url); ?>">
                            <?php echo get_avatar($author_id, 44); ?>
                        </a>
                    <?php endif; ?>
                    <div class="channel-meta">
                        <?php if ($author_url) : ?>
                            <a class="channel-name" href="<?php echo esc_url($author_url); ?>">
                                <?php echo esc_html($author_name); ?>
                            </a>
                        <?php else : ?>
                            <span class="channel-name"><?php echo esc_html($author_name); ?></span>
                        <?php endif; ?>
                        <?php if ($author_video_count) : ?>
                            <span class="channel-sub">
                                <?php echo esc_html(number_format_i18n($author_video_count)); ?> <?php esc_html_e('วิดีโอ', 'publish-videos-api'); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    <div class="video-summary__likes">
                        <button class="btn btn-primary" type="button" data-like-video>
                            <span><?php esc_html_e('ถูกใจ', 'publish-videos-api'); ?></span>
                            <?php if ($likes !== null) : ?>
                                <span class="like-count"><?php echo esc_html(publish_videos_api_format_views($likes)); ?></span>
                            <?php endif; ?>
                        </button>
                        <button class="btn btn-ghost" type="button" data-dislike-video><?php esc_html_e('ไม่ถูกใจ', 'publish-videos-api'); ?></button>
                    </div>
                </div>

                <?php
                $categories = get_the_terms($post_id, 'video_category');
                $tags = get_the_terms($post_id, 'video_tag');
                $actors = get_the_terms($post_id, 'video_actor');
                ?>

                <?php if (!empty($tags) && !is_wp_error($tags)) : ?>
                    <div class="video-tags">
                        <?php foreach ($tags as $term) : ?>
                            <a class="term-chip" href="<?php echo esc_url(get_term_link($term)); ?>">
                                <?php echo esc_html($term->name); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div class="video-summary__description">
                    <div class="video-summary__content">
                        <?php the_content(); ?>
                    </div>

                    <?php if (!empty($categories) && !is_wp_error($categories)) : ?>
                        <div class="term-block">
                            <strong><?php esc_html_e('หมวดหมู่', 'publish-videos-api'); ?></strong>
                            <div class="term-list">
                                <?php foreach ($categories as $term) : ?>
                                    <a class="term-chip" href="<?php echo esc_url(get_term_link($term)); ?>">
                                        <?php echo esc_html($term->name); ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($actors) && !is_wp_error($actors)) : ?>
                        <div class="term-block">
                            <strong><?php esc_html_e('นักแสดง', 'publish-videos-api'); ?></strong>
                            <div class="term-list">
                                <?php foreach ($actors as $term) : ?>
                                    <a class="term-chip" href="<?php echo esc_url(get_term_link($term)); ?>">
                                        <?php echo esc_html($term->name); ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php comments_template(); ?>

            <?php
            $related_terms = get_the_terms($post_id, 'video_category');
            $related_ids = [];
            if (!empty($related_terms) && !is_wp_error($related_terms)) {
                $related_ids = wp_list_pluck($related_terms, 'term_id');
            }

            $related_query = new WP_Query([
                'post_type' => 'video',
                'posts_per_page' => 12,
                'post__not_in' => [$post_id],
                'tax_query' => !empty($related_ids) ? [
                    [
                        'taxonomy' => 'video_category',
                        'field' => 'term_id',
                        'terms' => $related_ids,
                    ],
                ] : [],
            ]);
            ?>

            <?php if ($related_query->have_posts()) : ?>
                <section class="related-videos">
                    <h3><?php esc_html_e('คลิปแนะนำ', 'publish-videos-api'); ?></h3>
                    <div class="video-grid">
                        <?php
                        while ($related_query->have_posts()) :
                            $related_query->the_post();
                            get_template_part('template-parts/content', 'video-card');
                        endwhile;
                        wp_reset_postdata();
                        ?>
                    </div>
                </section>
            <?php endif; ?>
        </article>
    <?php endwhile; ?>
<?php endif; ?>

<?php
get_footer();
