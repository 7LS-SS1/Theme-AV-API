        </main>
    </div>

    <?php
    $footer_show_decor = (bool) get_theme_mod('pva_footer_show_decor', true);
    $footer_tagline = (string) get_theme_mod('pva_footer_tagline', get_bloginfo('description'));
    $footer_brand_type = (string) get_theme_mod('pva_footer_brand_type', 'logo');
    $footer_brand_type = in_array($footer_brand_type, ['logo', 'domain'], true) ? $footer_brand_type : 'logo';
    $footer_domain = wp_parse_url(home_url('/'), PHP_URL_HOST);
    $footer_pills_raw = (string) get_theme_mod('pva_footer_pills', __('อัปเดตทุกวัน, คลิปใหม่มาไว, คุณภาพคมชัด', 'publish-videos-api'));
    $footer_pills = array_filter(array_map('trim', explode(',', $footer_pills_raw)));
    $footer_show_menu = (bool) get_theme_mod('pva_footer_show_menu', true);
    $footer_cta_label = (string) get_theme_mod('pva_footer_cta_label', __('ดูวิดีโอทั้งหมด', 'publish-videos-api'));
    $footer_cta_url = (string) get_theme_mod('pva_footer_cta_url', get_post_type_archive_link('video'));
    $footer_note = (string) get_theme_mod('pva_footer_note', __('7LS', 'publish-videos-api'));
    ?>
    <footer class="site-footer">
        <?php if ($footer_show_decor) : ?>
            <div class="footer-decor" aria-hidden="true">
                <span class="footer-orb footer-orb--one"></span>
                <span class="footer-orb footer-orb--two"></span>
                <span class="footer-orb footer-orb--three"></span>
            </div>
        <?php endif; ?>
        <div class="container footer-inner">
            <div class="footer-brand">
                <?php if ($footer_brand_type === 'logo' && has_custom_logo()) : ?>
                    <div class="footer-logo footer-logo--image">
                        <?php echo wp_kses_post(get_custom_logo()); ?>
                    </div>
                <?php else : ?>
                    <?php $footer_brand_text = ($footer_brand_type === 'domain' && $footer_domain) ? $footer_domain : get_bloginfo('name'); ?>
                    <a class="footer-logo" href="<?php echo esc_url(home_url('/')); ?>">
                        <?php echo esc_html($footer_brand_text); ?>
                    </a>
                <?php endif; ?>
                <?php if ($footer_tagline !== '') : ?>
                    <p class="footer-tagline"><?php echo esc_html($footer_tagline); ?></p>
                <?php endif; ?>
                <?php if (!empty($footer_pills)) : ?>
                    <div class="footer-pills">
                        <?php foreach ($footer_pills as $pill) : ?>
                            <span class="footer-pill"><?php echo esc_html($pill); ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="footer-links">
                <?php if ($footer_show_menu) : ?>
                    <nav aria-label="<?php esc_attr_e('เมนูส่วนท้าย', 'publish-videos-api'); ?>">
                        <?php
                        wp_nav_menu([
                            'theme_location' => 'footer',
                            'menu_class' => 'menu',
                            'container' => false,
                            'fallback_cb' => false,
                        ]);
                        ?>
                    </nav>
                <?php endif; ?>
                <?php if ($footer_cta_label !== '' && $footer_cta_url !== '') : ?>
                    <div class="footer-cta">
                        <a class="btn btn-outline" href="<?php echo esc_url($footer_cta_url); ?>">
                            <?php echo esc_html($footer_cta_label); ?>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <div class="container footer-bottom">
            <div>
                <strong><?php bloginfo('name'); ?></strong> &middot; <?php echo esc_html(date('Y')); ?>
            </div>
            <?php if ($footer_note !== '') : ?>
                <div class="footer-note">
                    <?php echo esc_html($footer_note); ?>
                </div>
            <?php endif; ?>
        </div>
    </footer>
</div>

<?php wp_footer(); ?>
</body>
</html>
