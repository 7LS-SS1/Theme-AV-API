<?php
$featured_count = max(1, absint(get_theme_mod('pva_hero_featured_count', 8)));
$trending_count = max(1, absint(get_theme_mod('pva_hero_trending_count', 10)));

$featured_query = new WP_Query([
    'post_type' => 'video',
    'posts_per_page' => $featured_count,
    'post_status' => 'publish',
    'meta_key' => '_sevenls_vp_featured',
    'meta_value' => '1',
]);

if (!$featured_query->have_posts()) {
    $featured_query = new WP_Query([
        'post_type' => 'video',
        'posts_per_page' => $featured_count,
        'post_status' => 'publish',
        'orderby' => 'date',
        'order' => 'DESC',
    ]);
}

$trending_query = new WP_Query([
    'post_type' => 'video',
    'posts_per_page' => $trending_count,
    'post_status' => 'publish',
    'meta_key' => '_sevenls_vp_views',
    'orderby' => 'meta_value_num',
    'order' => 'DESC',
]);

$hero_bg_id = absint(get_theme_mod('pva_hero_bg_image_id', 0));
$hero_bg_url = $hero_bg_id ? wp_get_attachment_image_url($hero_bg_id, 'full') : '';
$hero_bg_size = publish_videos_api_sanitize_hero_bg_size((string) get_theme_mod('pva_hero_bg_size', 'cover'));
$hero_styles = [];
if ($hero_bg_url) {
    $hero_styles[] = sprintf('--hero-bg-image: url(%s);', esc_url($hero_bg_url));
}
if ($hero_bg_size !== '') {
    $hero_styles[] = sprintf('--hero-bg-size: %s;', esc_attr($hero_bg_size));
}
$hero_style = $hero_styles ? sprintf(' style="%s"', esc_attr(implode(' ', $hero_styles))) : '';
?>

<section class="hero hero--carousel"<?php echo $hero_style; ?>>
    <div class="hero__header">
        <div>
            <span class="hero__eyebrow"><?php esc_html_e('Video Highlights', 'publish-videos-api'); ?></span>
            <h1><?php esc_html_e('วิดีโอแนะนำและวิดีโอมาแรง', 'publish-videos-api'); ?></h1>
            <p><?php esc_html_e('คัดมาให้ดูง่าย ๆ แบบเลื่อนซ้ายขวา เลือกชมได้ทันที', 'publish-videos-api'); ?></p>
        </div>
        <div class="hero__actions">
            <a class="btn btn-primary" href="<?php echo esc_url(get_post_type_archive_link('video')); ?>">
                <?php esc_html_e('ดูวิดีโอทั้งหมด', 'publish-videos-api'); ?>
            </a>
            <a class="btn btn-outline" href="#latest">
                <?php esc_html_e('ดูวิดีโอล่าสุด', 'publish-videos-api'); ?>
            </a>
        </div>
    </div>

    <div class="hero__carousels">
        <?php if ($featured_query->have_posts()) : ?>
            <div class="hero-carousel" data-carousel data-carousel-autoplay="true" data-carousel-interval="4500">
                <div class="hero-carousel__header">
                    <h2><?php esc_html_e('วิดีโอแนะนำ', 'publish-videos-api'); ?></h2>
                    <div class="hero-carousel__controls">
                        <button class="carousel-btn" type="button" data-carousel-prev aria-label="<?php esc_attr_e('เลื่อนวิดีโอแนะนำย้อนกลับ', 'publish-videos-api'); ?>">‹</button>
                        <button class="carousel-btn" type="button" data-carousel-next aria-label="<?php esc_attr_e('เลื่อนวิดีโอแนะนำถัดไป', 'publish-videos-api'); ?>">›</button>
                    </div>
                </div>
                <div class="hero-carousel__track" data-carousel-track>
                    <?php while ($featured_query->have_posts()) : ?>
                        <?php $featured_query->the_post(); ?>
                        <div class="hero-carousel__item">
                            <?php get_template_part('template-parts/content', 'video-card'); ?>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
            <?php wp_reset_postdata(); ?>
        <?php endif; ?>

        <?php if ($trending_query->have_posts()) : ?>
            <div class="hero-carousel" data-carousel data-carousel-autoplay="true" data-carousel-interval="4500">
                <div class="hero-carousel__header">
                    <h2><?php esc_html_e('วิดีโอมาแรง', 'publish-videos-api'); ?></h2>
                    <div class="hero-carousel__controls">
                        <button class="carousel-btn" type="button" data-carousel-prev aria-label="<?php esc_attr_e('เลื่อนวิดีโอมาแรงย้อนกลับ', 'publish-videos-api'); ?>">‹</button>
                        <button class="carousel-btn" type="button" data-carousel-next aria-label="<?php esc_attr_e('เลื่อนวิดีโอมาแรงถัดไป', 'publish-videos-api'); ?>">›</button>
                    </div>
                </div>
                <div class="hero-carousel__track" data-carousel-track>
                    <?php while ($trending_query->have_posts()) : ?>
                        <?php $trending_query->the_post(); ?>
                        <div class="hero-carousel__item">
                            <?php get_template_part('template-parts/content', 'video-card'); ?>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
            <?php wp_reset_postdata(); ?>
        <?php endif; ?>
    </div>
</section>
