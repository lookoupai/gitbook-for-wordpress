jQuery(document).ready(function($) {
    const container = $('#articles-container');
    const content = container.find('.articles-content');
    const loading = container.find('.articles-loading');
    const pagination = container.find('.articles-pagination');
    let currentTab = 'latest';
    
    // 创建公告元素并插入到标签按钮上方
    const announcement = $('<div id="article-tabs-announcement" class="article-tabs-announcement"></div>');
    $('.tab-nav').before(announcement);
    announcement.hide(); // 默认隐藏
    
    // 加载内容函数
    function loadTabContent(tab, page = 1) {
        // 显示加载动画
        loading.show();
        content.css('opacity', '0.5');
        
        // 更新URL参数，但在首页时保持干净的URL
        const isHomepage = document.body.classList.contains('home');
        if (!isHomepage) {
            const url = new URL(window.location.href);
            url.searchParams.set('tab', tab);
            if (page > 1) {
                url.searchParams.set('page', page);
            } else {
                url.searchParams.delete('page');
            }
            window.history.pushState({}, '', url);
        }
        
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
                    // 处理公告显示
                    if (response.data.announcement) {
                        announcement.html(response.data.announcement).show();
                    } else {
                        announcement.hide();
                    }
                    
                    // 更新内容和分页
                    content.html(response.data.content);
                    pagination.html(response.data.pagination);
                    
                    // 移除自动滚动
                    // $('html, body').animate({
                    //     scrollTop: container.offset().top - 20
                    // }, 300);
                } else {
                    content.html('<div class="error">加载失败，请重试</div>');
                }
            },
            error: function() {
                content.html('<div class="error">加载失败，请重试</div>');
            },
            complete: function() {
                loading.hide();
                content.css('opacity', '1');
            }
        });
    }
    
    // 标签切换事件
    $('.tab-btn').on('click', function(e) {
        e.preventDefault();
        const $this = $(this);
        const tab = $this.data('tab');
        
        if (tab === currentTab) return;
        
        $('.tab-btn').removeClass('active');
        $this.addClass('active');
        currentTab = tab;
        
        loadTabContent(tab);
    });
    
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
    
    // 初始加载
    const urlParams = new URLSearchParams(window.location.search);
    const initialTab = urlParams.get('tab') || 'latest';
    const initialPage = parseInt(urlParams.get('page')) || 1;
    
    if (initialTab !== 'latest') {
        $('.tab-btn[data-tab="' + initialTab + '"]').addClass('active')
            .siblings().removeClass('active');
    }
    
    loadTabContent(initialTab, initialPage);
}); 