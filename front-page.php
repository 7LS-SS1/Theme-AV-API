<?php
get_header();
?>

<?php if (get_theme_mod('pva_show_hero', true)) : ?>
    <?php get_template_part('template-parts/hero'); ?>
<?php endif; ?>

<?php if (get_theme_mod('pva_show_filters', true)) : ?>
    <?php get_template_part('template-parts/filter-bar'); ?>
<?php endif; ?>

<?php if (get_theme_mod('pva_show_categories', true)) : ?>
    <?php
    get_template_part('template-parts/term-strip', null, [
        'taxonomy' => 'video_category',
        'title' => __('หมวดหมู่ยอดนิยม', 'publish-videos-api'),
        'limit' => 10,
    ]);
    ?>
<?php endif; ?>

<?php if (get_theme_mod('pva_show_tags', true)) : ?>
    <?php
    get_template_part('template-parts/term-strip', null, [
        'taxonomy' => 'video_tag',
        'title' => __('แท็กมาแรง', 'publish-videos-api'),
        'limit' => 12,
    ]);
    ?>
<?php endif; ?>

<?php
$trending_query = new WP_Query([
    'post_type' => 'video',
    'posts_per_page' => 8,
    'post_status' => 'publish',
    'meta_key' => '_sevenls_vp_views',
    'orderby' => 'meta_value_num',
    'order' => 'DESC',
]);
?>

<?php if ($trending_query->have_posts()) : ?>
    <section class="section">
        <div class="section-header">
            <div>
                <h2><?php esc_html_e('เทรนด์มาแรง', 'publish-videos-api'); ?></h2>
                <p><?php esc_html_e('คลิปยอดนิยมที่คนดูมากที่สุด', 'publish-videos-api'); ?></p>
            </div>
            <a class="section-link" href="<?php echo esc_url(get_post_type_archive_link('video')); ?>">
                <?php esc_html_e('ดูทั้งหมด', 'publish-videos-api'); ?>
            </a>
        </div>
        <div class="video-grid">
            <?php while ($trending_query->have_posts()) : ?>
                <?php $trending_query->the_post(); ?>
                <?php get_template_part('template-parts/content', 'video-card'); ?>
            <?php endwhile; ?>
        </div>
    </section>
    <?php wp_reset_postdata(); ?>
<?php endif; ?>

<section id="latest" class="section">
    <div class="section-header">
        <div>
            <h2><?php esc_html_e('วิดีโอล่าสุด', 'publish-videos-api'); ?></h2>
            <p><?php esc_html_e('อัปเดตใหม่ทุกวัน ดูได้ไม่รู้จบ', 'publish-videos-api'); ?></p>
        </div>
        <a class="section-link" href="<?php echo esc_url(get_post_type_archive_link('video')); ?>">
            <?php esc_html_e('ดูทั้งหมด', 'publish-videos-api'); ?>
        </a>
    </div>

    <?php
    $per_page = (int) get_theme_mod('pva_posts_per_page', 24);
    $latest_videos = new WP_Query([
        'post_type' => 'video',
        'posts_per_page' => $per_page,
        'post_status' => 'publish',
        'paged' => 1,
    ]);
    ?>

    <?php if ($latest_videos->have_posts()) : ?>
        <div class="video-grid" data-grid data-page="1" data-sort="latest" data-max-pages="<?php echo esc_attr($latest_videos->max_num_pages); ?>">
            <?php
            while ($latest_videos->have_posts()) :
                $latest_videos->the_post();
                get_template_part('template-parts/content', 'video-card');
            endwhile;
            wp_reset_postdata();
            ?>
        </div>
        <?php if ($latest_videos->max_num_pages > 1) : ?>
            <?php get_template_part('template-parts/load-more'); ?>
        <?php endif; ?>
    <?php else : ?>
        <p><?php esc_html_e('ยังไม่มีวิดีโอ ลองกลับมาใหม่ภายหลัง', 'publish-videos-api'); ?></p>
    <?php endif; ?>
</section>

<?php
get_footer();
