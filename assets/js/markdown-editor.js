jQuery(document).ready(function($) {
    // 如果页面上有 Markdown 编辑器
    if ($('#post_content').length) {
        // 引入 marked.js 用于 Markdown 解析
        $.getScript('https://cdn.jsdelivr.net/npm/marked/marked.min.js', function() {
            // 配置 marked 选项
            marked.setOptions({
                breaks: true,        // 支持 GitHub 风格的换行
                gfm: true,          // 启用 GitHub 风格的 Markdown
                sanitize: true,      // 消毒 HTML 标签
                smartLists: true,    // 优化列表输出
                smartypants: true    // 优化标点符号
            });

            // 实时预览功能
            $('#post_content').on('input', function() {
                var content = $(this).val();
                var html = marked(content);
                $('#markdown-preview').html(html);
            });

            // 初始加载时也执行一次预览
            $('#post_content').trigger('input');

            // 添加工具栏按钮
            var toolbar = $('<div class="markdown-toolbar"></div>').insertBefore('#post_content');
            
            // 添加常用的 Markdown 格式按钮
            var tools = [
                { icon: 'B', title: '粗体', prefix: '**', suffix: '**' },
                { icon: 'I', title: '斜体', prefix: '_', suffix: '_' },
                { icon: 'H', title: '标题', prefix: '## ', suffix: '' },
                { icon: '•', title: '无序列表', prefix: '- ', suffix: '' },
                { icon: '1.', title: '有序列表', prefix: '1. ', suffix: '' },
                { icon: '``', title: '代码', prefix: '`', suffix: '`' },
                { icon: '```', title: '代码块', prefix: '\n```\n', suffix: '\n```\n' },
                { icon: '>', title: '引用', prefix: '> ', suffix: '' },
                { icon: '[]', title: '链接', prefix: '[', suffix: '](url)' },
                { icon: '![]', title: '图片', prefix: '![alt](', suffix: ')' }
            ];

            tools.forEach(function(tool) {
                $('<button>', {
                    text: tool.icon,
                    title: tool.title,
                    class: 'markdown-tool',
                    click: function(e) {
                        e.preventDefault();
                        insertMarkdown(tool.prefix, tool.suffix);
                    }
                }).appendTo(toolbar);
            });

            // 插入 Markdown 格式文本
            function insertMarkdown(prefix, suffix) {
                var textarea = $('#post_content')[0];
                var start = textarea.selectionStart;
                var end = textarea.selectionEnd;
                var text = textarea.value;
                var selectedText = text.substring(start, end);
                
                var replacement = prefix + selectedText + suffix;
                textarea.value = text.substring(0, start) + replacement + text.substring(end);
                
                // 更新光标位置
                var newPosition = start + prefix.length + selectedText.length + suffix.length;
                textarea.setSelectionRange(newPosition, newPosition);
                
                // 触发预览更新
                $(textarea).trigger('input');
                textarea.focus();
            }
        });
    }
}); 