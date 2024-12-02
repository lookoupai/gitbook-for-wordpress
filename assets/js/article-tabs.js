jQuery(document).ready(function($) {
    const container = $('#articles-container');
    const content = container.find('.articles-content');
    const loading = container.find('.articles-loading');
    const pagination = container.find('.articles-pagination');
    
    // 创建公告元素并插入到标签按钮上方
    const announcement = $('<div id="article-tabs-announcement" class="article-tabs-announcement"></div>');
    $('.tab-nav').before(announcement);
    announcement.hide(); // 默认隐藏
    
    // 加载标签内容
    window.loadTabContent = function(tab, page = 1) {
        // 显示加载动画
        content.hide();
        pagination.hide();
        loading.show();
        
        // 发送AJAX请求
        $.ajax({
            url: articleTabsData.ajaxurl,
            type: 'POST',
            data: {
                action: 'get_tab_content',
                tab: tab,
                page: page
            },
            success: function(response) {
                if (response.success) {
                    content.html(response.data.content);
                    pagination.html(response.data.pagination);
                    
                    // 处理公告显示
                    if (response.data.announcement) {
                        announcement.html(response.data.announcement).show();
                    } else {
                        announcement.hide();
                    }
                } else {
                    content.html('<div class="error">加载失败</div>');
                }
            },
            error: function() {
                content.html('<div class="error">加载失败，请重试</div>');
            },
            complete: function() {
                // 隐藏加载动画，显示内容
                loading.hide();
                content.show();
                pagination.show();
            }
        });
    };
    
    // 分页点击事件
    $(document).on('click', '.page-number', function(e) {
        e.preventDefault();
        const page = $(this).data('page');
        if (page) {
            loadTabContent(currentTab, page);
            // 只在点击分页时滚动到顶部
            $('html, body').animate({
                scrollTop: container.offset().top - 20
            }, 300);
        }
    });
}); 