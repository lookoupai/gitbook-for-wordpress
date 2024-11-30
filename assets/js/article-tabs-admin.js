jQuery(document).ready(function($) {
    // 清理指定类型的缓存
    function clearCache(type, button, status) {
        button.prop('disabled', true);
        status.html('<span style="color:#666;">正在清理缓存...</span>');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'clear_article_tabs_cache',
                type: type,
                nonce: articleTabsSettings.nonce
            },
            success: function(response) {
                if (response.success) {
                    status.html('<span style="color:green;">✓ ' + response.data + '</span>');
                } else {
                    status.html('<span style="color:red;">✗ ' + response.data + '</span>');
                }
            },
            error: function() {
                status.html('<span style="color:red;">✗ 清理失败，请重试</span>');
            },
            complete: function() {
                button.prop('disabled', false);
                setTimeout(function() {
                    status.html('');
                }, 3000);
            }
        });
    }
    
    // 为每个清理按钮绑定事件
    $('#clear-latest-cache').on('click', function() {
        clearCache('latest', $(this), $('#clear-latest-status'));
    });
    
    $('#clear-updated-cache').on('click', function() {
        clearCache('updated', $(this), $('#clear-updated-status'));
    });
    
    $('#clear-popular-cache').on('click', function() {
        clearCache('popular', $(this), $('#clear-popular-status'));
    });
    
    $('#clear-rss-cache').on('click', function() {
        clearCache('rss', $(this), $('#clear-rss-status'));
    });
    
    $('#clear-all-cache').on('click', function() {
        clearCache('all', $(this), $('#clear-all-status'));
    });
    
    // 分类过滤选项切换
    $('input[name$="[category_filter]"]').on('change', function() {
        var wrapper = $(this).closest('.tab-category-settings').find('.category-select-wrapper');
        if ($(this).val() === 'none') {
            wrapper.slideUp();
        } else {
            wrapper.slideDown();
        }
    });
}); 