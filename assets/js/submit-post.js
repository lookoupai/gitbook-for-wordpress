jQuery(document).ready(function($) {
    const vditor = new Vditor('vditor', {
        height: 500,
        mode: 'ir',
        value: '',
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
            '|',
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
            '|',
            'preview',
            'fullscreen'
        ],
        preview: {
            delay: 500,
            show: true,
            parse: (element) => {
                // 可以在这里处理预览内容
            }
        },
        after: () => {
            vditor.setValue('');
        }
    });

    // 表单提交时获取内容
    $('#post-submission-form').on('submit', function(e) {
        e.preventDefault(); // 防止表单直接提交
        const content = vditor.getValue();
        $('#post_content').val(content);
        this.submit(); // 手动提交表单
    });
}); 