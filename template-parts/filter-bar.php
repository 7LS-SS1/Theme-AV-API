<?php
$active = isset($_GET['sort']) ? sanitize_text_field((string) wp_unslash($_GET['sort'])) : 'latest';
$items = [
    'latest' => __('ล่าสุด', 'publish-videos-api'),
    'popular' => __('ยอดนิยม', 'publish-videos-api'),
    'trending' => __('กำลังมาแรง', 'publish-videos-api'),
];
?>

<div class="filter-bar" data-sort-bar>
    <div class="filter-tabs">
        <?php foreach ($items as $key => $label) : ?>
            <?php $is_active = $active === $key ? 'is-active' : ''; ?>
            <button class="filter-tab <?php echo esc_attr($is_active); ?>" type="button" data-sort="<?php echo esc_attr($key); ?>">
                <?php echo esc_html($label); ?>
            </button>
        <?php endforeach; ?>
    </div>
</div>
