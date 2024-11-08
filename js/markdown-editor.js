jQuery(document).ready(function($) {
    console.log('Markdown editor script loaded');

    if (!$('#post_content').length) {
        console.log('Editor element not found');
        return;
    }

    // 直接加载 markdown-it
    const script = document.createElement('script');
    script.src = 'https://cdnjs.cloudflare.com/ajax/libs/markdown-it/13.0.1/markdown-it.min.js';
    script.onload = function() {
        console.log('markdown-it loaded successfully');
        initEditor();
    };
    script.onerror = function() {
        console.error('Failed to load markdown-it');
    };
    document.head.appendChild(script);

    function initEditor() {
        console.log('Initializing editor');
        const editor = $('#post_content');
        const preview = $('#markdown-preview');
        
        // 创建 markdown-it 实例，添加表格支持
        const md = new window.markdownit({
            html: true,
            linkify: true,
            typographer: true,
            breaks: true,
            // 启用表格支持
            tables: true
        });

        // 添加工具栏（使用与后台类似的样式）
        const toolbar = $(`
            <div class="markdown-toolbar">
                <button type="button" data-format="bold" title="粗体 (Ctrl+B)">B</button>
                <button type="button" data-format="italic" title="斜体 (Ctrl+I)">I</button>
                <button type="button" data-format="heading" title="标题">H</button>
                <button type="button" data-format="quote" title="引用">""</button>
                <button type="button" data-format="ulist" title="无序列表">•</button>
                <button type="button" data-format="olist" title="有序列表">1.</button>
                <button type="button" data-format="link" title="链接">🔗</button>
                <button type="button" data-format="image" title="图片">📷</button>
                <button type="button" data-format="table" title="表格">☷</button>
                <button type="button" data-format="code" title="代码">{}</button>
                <button type="button" data-format="preview" title="预览">👁</button>
            </div>
        `);
        
        // 移除任何现有的工具栏
        $('.markdown-toolbar').remove();
        // 添加新工具栏
        editor.before(toolbar);

        // 实时预览功能
        function updatePreview() {
            const markdownText = editor.val();
            try {
                // 处理表格前的空行问题
                const processedText = markdownText.replace(/\n\n\|/g, '\n|');
                const htmlContent = md.render(processedText);
                preview.html(htmlContent || '<div class="preview-placeholder">预览区域</div>');
                console.log('Preview updated with HTML:', htmlContent); // 调试输出
            } catch (e) {
                console.error('Markdown parsing error:', e);
                preview.html('<div class="preview-error">预览出错</div>');
            }
        }

        // 工具栏点击事件
        toolbar.on('click', 'button', function(e) {
            e.preventDefault();
            const format = $(this).data('format');
            const textarea = editor[0];
            const start = textarea.selectionStart;
            const end = textarea.selectionEnd;
            const text = textarea.value;
            let insertion = '';
            const selectedText = text.substring(start, end);

            switch(format) {
                case 'bold':
                    insertion = `**${selectedText || '粗体文本'}**`;
                    break;
                case 'italic':
                    insertion = `*${selectedText || '斜体文本'}*`;
                    break;
                case 'heading':
                    insertion = `\n## ${selectedText || '标题'}\n`;
                    break;
                case 'quote':
                    insertion = `\n> ${selectedText || '引用文本'}\n`;
                    break;
                case 'ulist':
                    insertion = `\n- ${selectedText || '列表项'}\n`;
                    break;
                case 'olist':
                    insertion = `\n1. ${selectedText || '列表项'}\n`;
                    break;
                case 'link':
                    insertion = `[${selectedText || '链接文本'}](url)`;
                    break;
                case 'image':
                    insertion = `![${selectedText || '图片描述'}](图片URL)`;
                    break;
                case 'table':
                    insertion = `
| 列1 | 列2 | 列3 |
|-----|-----|-----|
| 内容1 | 内容2 | 内容3 |
| 内容4 | 内容5 | 内容6 |`;
                    break;
                case 'code':
                    insertion = selectedText.includes('\n') 
                        ? `\n\`\`\`\n${selectedText || '代码'}\n\`\`\`\n`
                        : `\`${selectedText || '代码'}\``;
                    break;
                case 'preview':
                    $('.preview-section').toggle();
                    return;
            }

            // 如果是表格，需要去掉开头的换行
            if (format === 'table') {
                insertion = insertion.trim();
            }

            textarea.value = text.substring(0, start) + insertion + text.substring(end);
            updatePreview();
            textarea.focus();
            
            // 设置光标位置
            const newCursorPos = start + insertion.length;
            textarea.setSelectionRange(newCursorPos, newCursorPos);
        });

        // 初始预览
        updatePreview();

        // 监听输入事件
        editor.on('input', updatePreview);

        // 添加键盘快捷键
        editor.on('keydown', function(e) {
            if (e.ctrlKey || e.metaKey) {
                switch(e.key.toLowerCase()) {
                    case 'b':
                        e.preventDefault();
                        toolbar.find('[data-format="bold"]').click();
                        break;
                    case 'i':
                        e.preventDefault();
                        toolbar.find('[data-format="italic"]').click();
                        break;
                }
            }
        });
    }
}); 