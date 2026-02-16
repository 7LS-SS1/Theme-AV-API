<?php
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class('theme-dark'); ?>>
<?php wp_body_open(); ?>
<a class="skip-link screen-reader-text" href="#main-content"><?php esc_html_e('ข้ามไปยังเนื้อหาหลัก', 'publish-videos-api'); ?></a>
<div id="page" class="site">
    <header class="site-header">
        <div class="container header-inner">
            <div class="brand">
                <?php if (has_custom_logo()) : ?>
                    <div class="logo">
                        <?php echo wp_kses_post(get_custom_logo()); ?>
                    </div>
                <?php else : ?>
                    <a class="logo" href="<?php echo esc_url(home_url('/')); ?>">
                        <?php bloginfo('name'); ?>
                    </a>
                <?php endif; ?>
                <span class="tagline"><?php bloginfo('description'); ?></span>
            </div>

            <nav class="main-nav" id="site-navigation" aria-label="<?php esc_attr_e('เมนูหลัก', 'publish-videos-api'); ?>">
                <?php
                wp_nav_menu([
                    'theme_location' => 'primary',
                    'menu_class' => 'menu',
                    'container' => false,
                    'fallback_cb' => false,
                ]);
                ?>
                <div class="main-nav__actions">
                    <form role="search" method="get" class="search-form" action="<?php echo esc_url(home_url('/')); ?>">
                        <label>
                            <span class="screen-reader-text"><?php esc_html_e('ค้นหา:', 'publish-videos-api'); ?></span>
                            <input type="search" name="s" value="<?php echo esc_attr(get_search_query()); ?>" placeholder="ค้นหาคลิปหรือแท็ก">
                        </label>
                        <input type="hidden" name="post_type" value="video">
                        <button type="submit">ค้นหา</button>
                    </form>
                    <a class="btn-cta" href="<?php echo esc_url(get_post_type_archive_link('video')); ?>">ดูวิดีโอทั้งหมด</a>
                </div>
            </nav>

            <div class="header-actions">
                <button class="search-toggle" type="button" aria-controls="site-navigation" aria-expanded="false">
                    <?php esc_html_e('ค้นหา', 'publish-videos-api'); ?>
                </button>
                <?php if (get_theme_mod('pva_enable_dark_toggle', true)) : ?>
                    <button class="theme-toggle" type="button" aria-label="<?php esc_attr_e('สลับธีม', 'publish-videos-api'); ?>">
                        <span class="theme-toggle__icon" aria-hidden="true">◐</span>
                    </button>
                <?php endif; ?>
                <button class="menu-toggle" type="button" aria-controls="site-navigation" aria-expanded="false" data-open-label="<?php esc_attr_e('เมนู', 'publish-videos-api'); ?>" data-close-label="<?php esc_attr_e('ปิด', 'publish-videos-api'); ?>">
                    <?php esc_html_e('เมนู', 'publish-videos-api'); ?>
                </button>
            </div>
        </div>
    </header>

    <div class="site-content">
        <main id="main-content" class="site-main container" tabindex="-1">
