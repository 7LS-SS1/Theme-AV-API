<?php
get_header();
?>

<?php get_template_part('template-parts/breadcrumbs'); ?>

<header class="archive-header">
    <h1><?php post_type_archive_title(); ?></h1>
    <p><?php echo esc_html(get_bloginfo('description')); ?></p>
</header>

<?php get_template_part('template-parts/filter-bar'); ?>
<?php get_template_part('template-parts/taxonomy-filters'); ?>

<?php if (have_posts()) : ?>
    <?php $sort = isset($_GET['sort']) ? sanitize_text_field((string) wp_unslash($_GET['sort'])) : 'latest'; ?>
    <div class="video-grid" data-grid data-page="1" data-sort="<?php echo esc_attr($sort); ?>" data-max-pages="<?php echo esc_attr($wp_query->max_num_pages); ?>">
        <?php
        while (have_posts()) :
            the_post();
            get_template_part('template-parts/content', 'video-card');
        endwhile;
        ?>
    </div>

    <?php if ($wp_query->max_num_pages > 1) : ?>
        <?php get_template_part('template-parts/load-more'); ?>
    <?php endif; ?>
<?php else : ?>
    <p><?php esc_html_e('ไม่พบวิดีโอ', 'publish-videos-api'); ?></p>
<?php endif; ?>

<?php
get_footer();
