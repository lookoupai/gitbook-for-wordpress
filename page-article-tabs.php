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
                if ($settings['latest_enabled']) : ?>
                    <button class="tab-btn" data-tab="latest">最新文章</button>
                <?php endif; ?>
                
                <?php if ($settings['updated_enabled']) : ?>
                    <button class="tab-btn" data-tab="updated">最近修改</button>
                <?php endif; ?>
                
                <?php if ($settings['popular_enabled']) : ?>
                    <button class="tab-btn" data-tab="popular">热门文章</button>
                <?php endif; ?>
                
                <?php
                // 获取自定义标签
                $custom_tabs = get_option('article_custom_tabs', array());
                foreach ($custom_tabs as $index => $tab) :
                    // 检查是否需要登录可见
                    if (!empty($tab['login_required']) && !is_user_logged_in()) {
                        continue; // 跳过不显示需要登录的标签
                    }
                ?>
                    <button class="tab-btn" data-tab="custom_<?php echo $index; ?>">
                        <?php echo esc_html($tab['title']); ?>
                    </button>
                <?php endforeach; ?>
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

<script>
jQuery(document).ready(function($) {
    // 获取当前标签
    var currentTab = '<?php echo get_query_var('tab', 'latest'); ?>';
    window.currentTab = currentTab; // 使其全局可用
    
    // 标签切换事件
    $('.tab-btn').on('click', function(e) {
        e.preventDefault();
        const $this = $(this);
        const tab = $this.data('tab');
        
        if (tab === currentTab) return;
        
        // 更新URL，但不刷新页面
        if (history.pushState) {
            const newurl = tab === 'latest' ? 
                '/' : // 最新文章标签使用根目录
                '/tabs/' + tab + '/';
            window.history.pushState({path: newurl}, '', newurl);
        }
        
        $('.tab-btn').removeClass('active');
        $this.addClass('active');
        currentTab = tab;
        window.currentTab = tab; // 更新全局变量
        
        loadTabContent(tab);
    });
    
    // 初始加载
    $('.tab-btn[data-tab="' + currentTab + '"]').addClass('active')
        .siblings().removeClass('active');
    loadTabContent(currentTab);
});
</script>

<?php get_footer(); ?> 