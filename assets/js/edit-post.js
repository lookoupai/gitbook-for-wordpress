jQuery(document).ready(function($) {
    // 初始化 Vditor
    const vditor = new Vditor('vditor', {
        height: 500,
        mode: 'ir',
        value: initValue || '',  // 使用服务端传来的初始值
        cache: {
            enable: false
        },
        toolbar: [
            'emoji',
            'headings',
            'bold',
            'italic',
            'strike',
            '|',
            'line',
            'quote',
            'list',
            'ordered-list',
            'check',
            'code',
            'inline-code',
            'link',
            {
                name: 'insert-image',
                tip: '插入图片',
                icon: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M6.002 5.5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/><path d="M2.002 1a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V3a2 2 0 0 0-2-2h-12zm12 1a1 1 0 0 1 1 1v6.5l-3.777-1.947a.5.5 0 0 0-.577.093l-3.71 3.71-2.66-1.772a.5.5 0 0 0-.63.062L1.002 12V3a1 1 0 0 1 1-1h12z"/></svg>',
                click: () => {
                    const url = prompt('请输入图片链接:');
                    if (url) {
                        const desc = prompt('请输入图片描述(可选):') || '';
                        vditor.insertValue(`![${desc}](${url})`);
                    }
                }
            },
            'table',
            'preview',
            'fullscreen'
        ],
        preview: {
            delay: 500,
            show: true
        },
        after: () => {
            if(typeof initValue !== 'undefined') {
                vditor.setValue(initValue);
            }
        }
    });

    // 表单提交处理
    $('#edit-post-form').on('submit', function(e) {
        e.preventDefault();
        
        // 禁用提交按钮
        const submitButton = $(this).find('button[type="submit"]');
        submitButton.prop('disabled', true);
        
        // 获取编辑器内容
        const content = vditor.getValue();
        $('#post_content').val(content);
        
        // 准备表单数据
        const formData = $(this).serialize();
        
        // 发送 AJAX 请求
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: formData + '&action=edit_post&edit_post_nonce=' + editPostNonce,
            success: function(response) {
                if(response.success) {
                    // 显示成功消息
                    alert('文章更新成功！');
                    // 重定向到投票页面
                    window.location.href = response.data.redirect_url;
                } else {
                    // 显示错误消息
                    alert(response.data.message || '保存失败，请稍后重试');
                }
            },
            error: function(xhr, status, error) {
                // 显示错误消息
                alert('发生错误：' + error);
                console.error('AJAX Error:', status, error);
            },
            complete: function() {
                // 重新启用提交按钮
                submitButton.prop('disabled', false);
            }
        });
    });
}); 