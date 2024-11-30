<?php
/**
 * Template Name: 文章标签页
 */

get_header(); ?>

<div class="site-left-right-container">
    <div class='left-sidebar-container'>
        <?php require 'left-sidebar-pc.php'; ?>
        <?php require 'left-sidebar-mobile.php'; ?>
    </div>

    <main id="main" class="site-main right-content">
        <!-- 标签导航 -->
        <div class="article-tabs">
            <div class="tab-nav">
                <?php
                // 获取设置
                $settings = get_option('article_tabs_settings', array(
                    'latest_enabled' => true,    // 默认启用最新文章
                    'updated_enabled' => false,  // 默认禁用最近修改
                    'popular_enabled' => false   // 默认禁用热门文章
                ));

                // 显示启用的标签
                if (!isset($settings['latest_enabled']) || $settings['latest_enabled']) {
                    echo '<button class="tab-btn active" data-tab="latest">最新文章</button>';
                }
                if (!empty($settings['updated_enabled'])) {
                    echo '<button class="tab-btn" data-tab="updated">最近修改</button>';
                }
                if (!empty($settings['popular_enabled'])) {
                    echo '<button class="tab-btn" data-tab="popular">热门文章</button>';
                }

                // 获取自定义标签
                $custom_tabs = get_option('article_custom_tabs', array());
                foreach ($custom_tabs as $index => $tab) {
                    $tab_id = isset($tab['id']) ? $tab['id'] : 'custom_' . $index;
                    printf(
                        '<button class="tab-btn" data-tab="%s">%s</button>',
                        esc_attr($tab_id),
                        esc_html($tab['title'])
                    );
                }
                ?>
            </div>
            
            <!-- 文章列表容器 -->
            <div id="articles-container" class="articles-container">
                <div class="articles-loading" style="display: none;">
                    <div class="loading-spinner"></div>
                </div>
                <div class="articles-content"></div>
                <div class="articles-pagination"></div>
            </div>
        </div>

        <?php require 'footer-container.php' ?>
    </main>
</div>

<?php get_footer(); ?> 