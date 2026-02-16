<?php
$taxonomy = $args['taxonomy'] ?? '';
$term = $args['term'] ?? '';
?>
<button class="load-more" type="button" data-load-more data-taxonomy="<?php echo esc_attr($taxonomy); ?>" data-term="<?php echo esc_attr($term); ?>">
    <?php esc_html_e('โหลดเพิ่ม', 'publish-videos-api'); ?>
</button>
