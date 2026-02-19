<?php
get_header();
?>

<header class="archive-header">
    <h1><?php esc_html_e('วิดีโอล่าสุด', 'publish-videos-api'); ?></h1>
    <p><?php esc_html_e('สำรวจวิดีโอที่อัปโหลดล่าสุด', 'publish-videos-api'); ?></p>
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
    <p><?php esc_html_e('ไม่พบวิดีโอ', 'publish-videos-api'); ?></p>
<?php endif; ?>

<?php
get_footer();
