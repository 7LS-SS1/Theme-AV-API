<?php
$taxonomy = $args['taxonomy'] ?? 'video_category';
$title = $args['title'] ?? '';
$limit = $args['limit'] ?? 12;
$terms = get_terms([
    'taxonomy' => $taxonomy,
    'hide_empty' => true,
    'number' => $limit,
]);
?>

<?php if (!empty($terms) && !is_wp_error($terms)) : ?>
    <section class="term-strip">
        <div class="term-strip__header">
            <?php if ($title) : ?>
                <h3><?php echo esc_html($title); ?></h3>
            <?php endif; ?>
            <a href="<?php echo esc_url(get_post_type_archive_link('video')); ?>"><?php esc_html_e('ดูทั้งหมด', 'publish-videos-api'); ?></a>
        </div>
        <div class="term-strip__list">
            <?php foreach ($terms as $term) : ?>
                <a class="term-pill" href="<?php echo esc_url(get_term_link($term)); ?>">
                    <?php echo esc_html($term->name); ?>
                </a>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>
