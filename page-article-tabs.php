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
                // 获取当前标签
                $current_tab = get_query_var('tab', 'latest');
                
                // 获取标签顺序
                $tabs_order = get_option('article_tabs_order', array());
                $settings = get_option('article_tabs_settings', array(
                    'latest_enabled' => true,    // 默认启用最新文章
                    'updated_enabled' => false,  // 默认禁用最近修改
                    'popular_enabled' => false   // 默认禁用热门文章
                ));
                
                if(empty($tabs_order)) {
                    // 如果没有保存的顺序,使用默认顺序
                    $tabs_order = array('latest', 'updated', 'popular');
                }
                
                // 获取所有启用的标签
                $enabled_tabs = array();
                if(!empty($settings['latest_enabled'])) $enabled_tabs['latest'] = array(
                    'name' => '最新文章',
                    'url' => home_url('/tabs/latest/')
                );
                if(!empty($settings['updated_enabled'])) $enabled_tabs['updated'] = array(
                    'name' => '最近修改',
                    'url' => home_url('/tabs/updated/')
                );
                if(!empty($settings['popular_enabled'])) $enabled_tabs['popular'] = array(
                    'name' => '热门文章',
                    'url' => home_url('/tabs/popular/')
                );

                // 添加自定义标签
                $custom_tabs = get_option('article_custom_tabs', array());
                if(is_array($custom_tabs)) {
                    foreach ($custom_tabs as $index => $tab) {
                        // 检查标签是否有标题
                        if(empty($tab['title'])) continue;
                        
                        // 检查是否需要登录可见
                        if (!empty($tab['login_required']) && !is_user_logged_in()) {
                            continue;
                        }
                        $enabled_tabs['custom_'.$index] = array(
                            'name' => esc_html($tab['title']),
                            'url' => home_url('/tabs/custom_' . $index . '/')
                        );
                    }
                }

                // 按保存的顺序显示标签
                foreach($tabs_order as $tab_id) {
                    if(isset($enabled_tabs[$tab_id])) {
                        printf(
                            '<button class="tab-btn%s" data-tab="%s">%s</button>',
                            $current_tab === $tab_id ? ' active' : '',
                            esc_attr($tab_id),
                            esc_html($enabled_tabs[$tab_id]['name'])
                        );
                        unset($enabled_tabs[$tab_id]);
                    }
                }

                // 显示未排序的标签
                foreach($enabled_tabs as $tab_id => $tab_info) {
                    printf(
                        '<button class="tab-btn%s" data-tab="%s">%s</button>',
                        $current_tab === $tab_id ? ' active' : '',
                        esc_attr($tab_id),
                        esc_html($tab_info['name'])
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