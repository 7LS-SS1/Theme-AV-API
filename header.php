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

            <!-- Logo / Brand -->
            <div class="brand">
                <?php if (has_custom_logo()) : ?>
                    <div class="logo"><?php echo wp_kses_post(get_custom_logo()); ?></div>
                <?php else : ?>
                    <a class="logo" href="<?php echo esc_url(home_url('/')); ?>"><?php bloginfo('name'); ?></a>
                <?php endif; ?>
            </div>

            <!-- Primary navigation -->
            <nav class="main-nav" id="site-navigation" aria-label="<?php esc_attr_e('เมนูหลัก', 'publish-videos-api'); ?>">
                <?php
                wp_nav_menu([
                    'theme_location' => 'primary',
                    'menu_class'     => 'menu',
                    'container'      => false,
                    'fallback_cb'    => false,
                ]);
                ?>
            </nav>

            <!-- Right-side actions -->
            <div class="header-actions">
                <!-- Search icon toggle -->
                <button class="search-toggle header-icon-btn" type="button" aria-controls="site-navigation" aria-expanded="false" aria-label="<?php esc_attr_e('ค้นหา', 'publish-videos-api'); ?>">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                        <circle cx="9" cy="9" r="6"/><line x1="14" y1="14" x2="19" y2="19"/>
                    </svg>
                </button>

                <!-- User / Sign-in -->
                <a class="header-user-btn" href="<?php echo esc_url(wp_login_url(get_permalink())); ?>" aria-label="<?php esc_attr_e('เข้าสู่ระบบ', 'publish-videos-api'); ?>">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" focusable="false">
                        <circle cx="10" cy="6" r="4"/><path d="M2 18c0-4 3.6-7 8-7s8 3 8 7"/>
                    </svg>
                </a>

                <!-- Mobile menu toggle -->
                <button class="menu-toggle" type="button" aria-controls="site-navigation" aria-expanded="false"
                    data-open-label="<?php esc_attr_e('เมนู', 'publish-videos-api'); ?>"
                    data-close-label="<?php esc_attr_e('ปิด', 'publish-videos-api'); ?>">
                    <?php esc_html_e('เมนู', 'publish-videos-api'); ?>
                </button>
            </div>

            <!-- Inline search (shown when search toggle is active) -->
            <div class="header-search-bar" id="header-search-bar" hidden>
                <form role="search" method="get" class="search-form" action="<?php echo esc_url(home_url('/')); ?>">
                    <label>
                        <span class="screen-reader-text"><?php esc_html_e('ค้นหา:', 'publish-videos-api'); ?></span>
                        <input type="search" name="s" value="<?php echo esc_attr(get_search_query()); ?>"
                               placeholder="<?php esc_attr_e('ค้นหาหนัง ซีรีส์ นักแสดง...', 'publish-videos-api'); ?>"
                               autocomplete="off">
                    </label>
                    <input type="hidden" name="post_type" value="video">
                    <button type="submit"><?php esc_html_e('ค้นหา', 'publish-videos-api'); ?></button>
                </form>
            </div>

        </div>
    </header>

    <div class="site-content">
        <main id="main-content" class="site-main container" tabindex="-1">
