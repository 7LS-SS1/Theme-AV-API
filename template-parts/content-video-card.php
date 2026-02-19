<?php
$thumb_url = publish_videos_api_get_video_thumbnail_url(get_the_ID());
$placeholder_url = publish_videos_api_get_placeholder_url();
$duration = publish_videos_api_get_video_duration(get_the_ID());
$views = publish_videos_api_get_video_views(get_the_ID());
$categories = get_the_terms(get_the_ID(), 'video_category');
$actors = get_the_terms(get_the_ID(), 'video_actor');
$is_new = (get_the_time('U') > strtotime('-7 days'));
$quality = strtolower((string) get_post_meta(get_the_ID(), '_sevenls_vp_quality', true));
$has_hd = $quality && strpos($quality, 'hd') !== false;
$preview_url = publish_videos_api_get_video_preview_url(get_the_ID());
$preview_url = publish_videos_api_is_preview_media_url($preview_url) ? $preview_url : '';
$preview_poster = !empty($thumb_url) ? $thumb_url : $placeholder_url;
?>

<article <?php post_class('video-card'); ?>>
    <a class="video-card__thumb" href="<?php the_permalink(); ?>">
        <?php if (has_post_thumbnail()) : ?>
            <?php the_post_thumbnail('video-thumb', ['loading' => 'lazy', 'decoding' => 'async']); ?>
        <?php else : ?>
            <?php $image_url = !empty($thumb_url) ? $thumb_url : $placeholder_url; ?>
            <img src="<?php echo esc_url($image_url); ?>" alt="<?php the_title_attribute(); ?>" loading="lazy" decoding="async">
        <?php endif; ?>
        <?php if (!empty($preview_url)) : ?>
            <video class="video-card__preview" muted playsinline loop preload="none" data-preview-src="<?php echo esc_url($preview_url); ?>" poster="<?php echo esc_url($preview_poster); ?>"></video>
        <?php endif; ?>
        <div class="video-card__badges">
            <?php if ($is_new) : ?>
                <span class="badge badge--new"><?php esc_html_e('NEW', 'publish-videos-api'); ?></span>
            <?php endif; ?>
            <?php if ($has_hd) : ?>
                <span class="badge badge--hd"><?php esc_html_e('HD', 'publish-videos-api'); ?></span>
            <?php endif; ?>
        </div>
        <?php if (!empty($duration)) : ?>
            <span class="video-card__duration"><?php echo esc_html($duration); ?></span>
        <?php endif; ?>
    </a>

    <div class="video-card__body">
        <h3 class="video-card__title">
            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
        </h3>

        <div class="video-card__meta">
            <?php if (!empty($categories) && !is_wp_error($categories)) : ?>
                <span><?php echo esc_html($categories[0]->name); ?></span>
            <?php endif; ?>
            <?php if (!empty($actors) && !is_wp_error($actors)) : ?>
                <span><?php echo esc_html($actors[0]->name); ?></span>
            <?php endif; ?>
            <?php if ($views) : ?>
                <span><?php echo esc_html(publish_videos_api_format_views($views)); ?> <?php esc_html_e('วิว', 'publish-videos-api'); ?></span>
            <?php endif; ?>
        </div>
    </div>
</article>
