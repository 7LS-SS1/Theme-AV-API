<?php
get_header();
?>

<?php get_template_part('template-parts/breadcrumbs'); ?>

<header class="archive-header">
    <h1>
        <?php
        printf(
            esc_html__('ผลการค้นหา: %s', 'publish-videos-api'),
            '<span>' . esc_html(get_search_query()) . '</span>'
        );
        ?>
    </h1>
</header>

<?php if (have_posts()) : ?>
    <div class="video-grid">
        <?php
        while (have_posts()) :
            the_post();
            get_template_part('template-parts/content', 'video-card');
        endwhile;
        ?>
    </div>
    <?php the_posts_pagination(); ?>
<?php else : ?>
    <p><?php esc_html_e('ไม่พบวิดีโอที่ตรงกับการค้นหา', 'publish-videos-api'); ?></p>
<?php endif; ?>

<?php
get_footer();
