<?php
if (!defined('ABSPATH')) exit;

// 添加返回顶部按钮到页面底部
function add_back_to_top() {
    ?>
<!-- 返回顶部按钮代码 -->
<style>
#back-to-top {
    position: fixed;
    right: 30px;
    bottom: 30px;
    width: 40px;
    height: 40px;
    border-radius: 4px;
    background: linear-gradient(to bottom, #827265, #716053);
    border: none;
    cursor: pointer;
    opacity: 0;
    visibility: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    box-shadow: 0 2px 6px rgba(0,0,0,0.2);
    transition: all 0.3s ease;
    z-index: 9999;
}

#back-to-top.visible {
    opacity: 1;
    visibility: visible;
}

#back-to-top:hover {
    background: linear-gradient(to bottom, #716053, #635753);
    transform: translateY(-3px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.3);
}

#back-to-top svg {
    width: 20px;
    height: 20px;
    fill: currentColor;
}

/* 适配移动端 */
@media (max-width: 768px) {
    #back-to-top {
        right: 20px;
        bottom: 20px;
        width: 36px;
        height: 36px;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 创建返回顶部按钮
    const button = document.createElement('button');
    button.id = 'back-to-top';
    button.title = '返回顶部';
    button.innerHTML = `<svg viewBox="0 0 24 24" width="24" height="24">
        <path fill="currentColor" d="M7.41 15.41L12 10.83l4.59 4.58L18 14l-6-6-6 6z"></path>
    </svg>`;
    document.body.appendChild(button);

    // 获取主要内容容器
    const contentContainer = document.querySelector('.right-content');
    
    function toggleButton() {
        const scrollTop = contentContainer ? contentContainer.scrollTop : window.pageYOffset;
        if (scrollTop > 300) {
            button.classList.add('visible');
        } else {
            button.classList.remove('visible');
        }
    }

    function scrollToTop() {
        if (contentContainer) {
            contentContainer.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        } else {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        }
    }

    // 添加滚动事件监听
    if (contentContainer) {
        contentContainer.addEventListener('scroll', toggleButton);
    } else {
        window.addEventListener('scroll', toggleButton);
    }

    // 添加点击事件
    button.addEventListener('click', scrollToTop);

    // 初始检查
    toggleButton();
});
</script>
    <?php
}
add_action('wp_footer', 'add_back_to_top'); 