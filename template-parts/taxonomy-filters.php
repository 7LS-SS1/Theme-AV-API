<?php
$current = get_queried_object();
$current_id = $current && !is_wp_error($current) && isset($current->term_id) ? (int) $current->term_id : 0;

$categories = publish_videos_api_get_terms('video_category');
$tags = publish_videos_api_get_terms('video_tag');
$actor_parent = get_term_by('name', 'นักแสดง', 'video_actor');
$actor_parent_id = $actor_parent && !is_wp_error($actor_parent) ? (int) $actor_parent->term_id : 0;
$actors = publish_videos_api_get_terms('video_actor', $actor_parent_id);
?>

<div class="filter-group">
    <?php if (!empty($categories)) : ?>
        <div class="filter-row">
            <span class="filter-label"><?php esc_html_e('หมวดหมู่', 'publish-videos-api'); ?></span>
            <?php foreach ($categories as $term) : ?>
                <?php $active = $current_id === (int) $term->term_id ? 'is-active' : ''; ?>
                <a class="filter-pill <?php echo esc_attr($active); ?>" href="<?php echo esc_url(get_term_link($term)); ?>">
                    <?php echo esc_html($term->name); ?>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($tags)) : ?>
        <div class="filter-row">
            <span class="filter-label"><?php esc_html_e('แท็ก', 'publish-videos-api'); ?></span>
            <?php foreach ($tags as $term) : ?>
                <?php $active = $current_id === (int) $term->term_id ? 'is-active' : ''; ?>
                <a class="filter-pill <?php echo esc_attr($active); ?>" href="<?php echo esc_url(get_term_link($term)); ?>">
                    <?php echo esc_html($term->name); ?>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($actors)) : ?>
        <div class="filter-row">
            <span class="filter-label"><?php esc_html_e('นักแสดง', 'publish-videos-api'); ?></span>
            <?php foreach ($actors as $term) : ?>
                <?php $active = $current_id === (int) $term->term_id ? 'is-active' : ''; ?>
                <a class="filter-pill <?php echo esc_attr($active); ?>" href="<?php echo esc_url(get_term_link($term)); ?>">
                    <?php echo esc_html($term->name); ?>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
