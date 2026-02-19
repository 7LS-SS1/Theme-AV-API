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
        $categories = get_the_terms($post_id, 'video_category');
        $tags = get_the_terms($post_id, 'video_tag');
        $actors = get_the_terms($post_id, 'video_actor');
        $producer_terms = [];

        $raw_payload_value = get_post_meta($post_id, '_sevenls_vp_raw_payload', true);
        $raw_payload_data = [];
        if (is_string($raw_payload_value) && $raw_payload_value !== '') {
            $decoded_payload = json_decode($raw_payload_value, true);
            if (is_array($decoded_payload)) {
                $raw_payload_data = $decoded_payload;
            }
        }

        $get_first_meta_value = static function (int $target_post_id, array $meta_keys): string {
            foreach ($meta_keys as $meta_key) {
                $value = get_post_meta($target_post_id, $meta_key, true);
                if (!is_scalar($value)) {
                    continue;
                }
                $value = trim((string) $value);
                if ($value !== '') {
                    return $value;
                }
            }
            return '';
        };

        $find_payload_value = static function ($node, array $target_keys) use (&$find_payload_value): string {
            if (!is_array($node)) {
                return '';
            }

            $lookup = array_map('strtolower', $target_keys);
            foreach ($node as $key => $value) {
                if (in_array(strtolower((string) $key), $lookup, true)) {
                    if (is_scalar($value)) {
                        $value = trim((string) $value);
                        if ($value !== '') {
                            return $value;
                        }
                    }
                }
            }

            foreach ($node as $value) {
                if (!is_array($value)) {
                    continue;
                }
                $found = $find_payload_value($value, $target_keys);
                if ($found !== '') {
                    return $found;
                }
            }

            return '';
        };

        $producer_taxonomy_candidates = [
            'video_producer',
            'video_studio',
            'video_maker',
            'video_label',
            'producer',
            'studio',
            'maker',
            'label',
        ];
        $video_taxonomies = get_object_taxonomies('video', 'objects');
        if (is_array($video_taxonomies)) {
            foreach ($video_taxonomies as $taxonomy_slug => $taxonomy_obj) {
                $haystack = strtolower((string) $taxonomy_slug . ' ' . (string) ($taxonomy_obj->labels->name ?? '') . ' ' . (string) ($taxonomy_obj->labels->singular_name ?? ''));
                if (preg_match('/producer|studio|maker|label|publisher|company|ค่าย|ผู้ผลิต/u', $haystack)) {
                    $producer_taxonomy_candidates[] = (string) $taxonomy_slug;
                }
            }
        }
        $producer_taxonomy_candidates = array_values(array_unique($producer_taxonomy_candidates));
        $producer_term_names = [];
        foreach ($producer_taxonomy_candidates as $producer_taxonomy) {
            if (!taxonomy_exists($producer_taxonomy)) {
                continue;
            }
            $terms = get_the_terms($post_id, $producer_taxonomy);
            if (empty($terms) || is_wp_error($terms)) {
                continue;
            }
            foreach ($terms as $term) {
                $term_key = $term->taxonomy . ':' . (string) $term->term_id;
                if (isset($producer_terms[$term_key])) {
                    continue;
                }
                $producer_terms[$term_key] = $term;
                $producer_term_names[] = (string) $term->name;
            }
        }
        $producer_terms = array_values($producer_terms);

        if ($duration === '' && isset($raw_payload_data['duration'])) {
            $duration = publish_videos_api_format_duration_value($raw_payload_data['duration']);
        }

        $video_code = $get_first_meta_value($post_id, [
            '_sevenls_vp_video_code',
            '_sevenls_vp_code',
            '_sevenls_vp_movie_code',
            '_sevenls_vp_dvd_id',
            'video_code',
            'movie_code',
            'code',
            'dvd_id',
            'cid',
        ]);

        if ($video_code === '') {
            $video_code = $find_payload_value($raw_payload_data, ['video_code', 'movie_code', 'code', 'dvd_id', 'cid']);
        }

        if (
            $video_code === ''
            && is_string($external_id)
            && preg_match('/^[A-Za-z]{2,8}-\d{2,6}[A-Za-z0-9-]*$/', $external_id)
        ) {
            $video_code = $external_id;
        }

        if ($video_code === '') {
            $title_value = get_the_title($post_id);
            if (is_string($title_value) && preg_match('/\b([A-Za-z]{2,8}-\d{2,6}[A-Za-z0-9-]*)\b/u', $title_value, $matches)) {
                $video_code = strtoupper($matches[1]);
            }
        }

        $date_added = '';
        $source_created_at = $get_first_meta_value($post_id, [
            '_sevenls_vp_source_created_at',
            '_sevenls_vp_created_at',
            'source_created_at',
            'created_at',
        ]);
        if ($source_created_at !== '') {
            $timestamp = strtotime($source_created_at);
            if ($timestamp !== false) {
                $date_added = wp_date('Y-m-d', $timestamp);
            }
        }
        if ($date_added === '') {
            $date_added = get_the_date('Y-m-d', $post_id);
        }

        $producer = '';
        if (!empty($producer_term_names)) {
            $producer = implode(', ', array_unique($producer_term_names));
        }
        if ($producer === '') {
            $producer = $get_first_meta_value($post_id, [
                '_sevenls_vp_producer',
                '_sevenls_vp_studio',
                '_sevenls_vp_maker',
                '_sevenls_vp_label',
                'producer',
                'studio',
                'maker',
                'label',
                'publisher',
                'company',
            ]);
        }
        if ($producer === '') {
            $producer = $find_payload_value($raw_payload_data, ['producer', 'studio', 'maker', 'label', 'publisher', 'company']);
        }
        if ($producer === '' && !empty($categories) && !is_wp_error($categories)) {
            $producer_from_categories = [];
            foreach ($categories as $term) {
                $term_name = (string) $term->name;
                if (preg_match('/factory|studio|maker|label|production|ค่าย|ผู้ผลิต/i', $term_name)) {
                    $producer_from_categories[] = $term_name;
                }
            }
            if (!empty($producer_from_categories)) {
                $producer = implode(', ', array_unique($producer_from_categories));
            }
        }

        $render_terms_value = static function ($terms): void {
            if (empty($terms) || is_wp_error($terms)) {
                echo '<span class="video-jav-details__empty">-</span>';
                return;
            }

            $index = 0;
            foreach ($terms as $term) {
                $term_link = get_term_link($term);
                if ($index > 0) {
                    echo '<span class="video-jav-details__sep">, </span>';
                }
                if (is_wp_error($term_link)) {
                    echo '<span>' . esc_html($term->name) . '</span>';
                } else {
                    echo '<a href="' . esc_url($term_link) . '">' . esc_html($term->name) . '</a>';
                }
                $index++;
            }
        };

        $video_code = $video_code !== '' ? $video_code : '-';
        $duration = $duration !== '' ? $duration : '-';
        $producer = $producer !== '' ? $producer : '-';
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
                        <span><?php echo esc_html($date_added); ?></span>
                    </div>
                </div>

                <div class="video-summary__description">
                    <div class="video-summary__content">
                        <?php the_content(); ?>
                    </div>

                    <div class="video-jav-details">
                        <div class="video-jav-details__row">
                            <span class="video-jav-details__label">หมวดหมู่ :</span>
                            <span class="video-jav-details__value"><?php $render_terms_value($categories); ?></span>
                        </div>
                        <div class="video-jav-details__row">
                            <span class="video-jav-details__label">รหัส :</span>
                            <span class="video-jav-details__value"><?php echo esc_html($video_code); ?></span>
                        </div>
                        <div class="video-jav-details__row">
                            <span class="video-jav-details__label">วันที่เพิ่ม :</span>
                            <span class="video-jav-details__value"><?php echo esc_html($date_added); ?></span>
                        </div>
                        <div class="video-jav-details__row">
                            <span class="video-jav-details__label">ระยะเวลา :</span>
                            <span class="video-jav-details__value"><?php echo esc_html($duration); ?></span>
                        </div>
                        <div class="video-jav-details__row">
                            <span class="video-jav-details__label">แท็ก :</span>
                            <span class="video-jav-details__value"><?php $render_terms_value($tags); ?></span>
                        </div>
                        <div class="video-jav-details__row">
                            <span class="video-jav-details__label">นักแสดงหญิง :</span>
                            <span class="video-jav-details__value"><?php $render_terms_value($actors); ?></span>
                        </div>
                        <div class="video-jav-details__row">
                            <span class="video-jav-details__label">หมวดหมู่ผู้ผลิต :</span>
                            <span class="video-jav-details__value"><?php $render_terms_value($producer_terms); ?></span>
                        </div>
                        <div class="video-jav-details__row">
                            <span class="video-jav-details__label">ผู้ผลิต :</span>
                            <span class="video-jav-details__value"><?php echo esc_html($producer); ?></span>
                        </div>
                    </div>
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
