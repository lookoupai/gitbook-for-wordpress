jQuery(document).ready(function($) {
    // 等待 marked.js 加载完成
    function initMarkdown() {
        if (typeof marked !== 'undefined') {
            // 配置 marked
            marked.setOptions({
                breaks: true,        // 支持 GitHub 风格的换行
                gfm: true,          // 启用 GitHub 风格的 Markdown
                sanitize: false,     // 允许 HTML 标签
                smartLists: true,    // 优化列表输出
                smartypants: true,   // 优化标点符号
                highlight: function(code, lang) {
                    // 如果需要代码高亮，可以在这里集成 Prism 或 highlight.js
                    return code;
                }
            });

            // 渲染所有 markdown 内容
            $('.markdown-content').each(function() {
                var $element = $(this);
                var markdown = $element.html();
                
                // 处理 WordPress 自动添加的 p 标签
                markdown = markdown.replace(/<p>/g, '').replace(/<\/p>/g, '\n\n');
                
                // 渲染 Markdown
                try {
                    var html = marked.parse(markdown);  // 使用 marked.parse 而不是直接调用 marked
                    $element.html(html);
                } catch (e) {
                    console.error('Markdown parsing error:', e);
                }
            });
        } else {
            // 如果 marked 还没加载完成，等待 100ms 后重试
            setTimeout(initMarkdown, 100);
        }
    }

    // 开始初始化
    initMarkdown();
}); 