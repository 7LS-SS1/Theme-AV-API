<?php
get_header();
$term = get_queried_object();
$current_id = $term && !is_wp_error($term) ? (int) $term->term_id : 0;
?>

<?php get_template_part('template-parts/breadcrumbs'); ?>

<header class="archive-header">
    <h1><?php single_term_title(); ?></h1>
    <?php if (!empty($term->description)) : ?>
        <p><?php echo esc_html($term->description); ?></p>
    <?php endif; ?>
</header>

<?php get_template_part('template-parts/filter-bar'); ?>
<?php get_template_part('template-parts/taxonomy-filters'); ?>

<?php if (have_posts()) : ?>
    <?php $sort = isset($_GET['sort']) ? sanitize_text_field((string) wp_unslash($_GET['sort'])) : 'latest'; ?>
    <div class="video-grid" data-grid data-page="1" data-sort="<?php echo esc_attr($sort); ?>" data-max-pages="<?php echo esc_attr($wp_query->max_num_pages); ?>" data-taxonomy="video_category" data-term="<?php echo esc_attr($current_id); ?>">
        <?php
        while (have_posts()) :
            the_post();
            get_template_part('template-parts/content', 'video-card');
        endwhile;
        ?>
    </div>
    <?php if ($wp_query->max_num_pages > 1) : ?>
        <?php get_template_part('template-parts/load-more', null, ['taxonomy' => 'video_category', 'term' => $current_id]); ?>
    <?php endif; ?>
<?php else : ?>
    <p><?php esc_html_e('ไม่พบวิดีโอในหมวดหมู่นี้', 'publish-videos-api'); ?></p>
<?php endif; ?>

<?php
get_footer();
