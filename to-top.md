已经加入到主题中，不需要手动添加

# 返回顶部按钮代码

这是一个专门为GitBook WordPress主题设计的返回顶部按钮代码。它具有美观的设计和平滑的动画效果。

## 功能特点

- 响应式设计，适配PC和移动端
- 平滑滚动效果
- 渐入渐出动画
- 悬停效果
- 主题配色匹配

## 使用方法

1. 安装并激活 WPCode 插件
2. 在 WordPress 后台进入 WPCode -> 代码片段
3. 点击"添加代码片段"
4. 选择 HTML 代码片段
5. 将以下代码复制到代码编辑器中
6. 设置加载位置为"页面底部"（Footer）
7. 保存并启用代码片段

## 代码

```html
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
```

## 自定义样式

如果需要修改按钮样式，可以调整以下CSS属性：

1. 位置调整：
   ```css
   #back-to-top {
     right: 30px;    /* 调整右侧距离 */
     bottom: 30px;   /* 调整底部距离 */
   }
   ```

2. 大小调整：
   ```css
   #back-to-top {
     width: 40px;    /* 调整按钮宽度 */
     height: 40px;   /* 调整按钮高度 */
   }
   ```

3. 颜色调整：
   ```css
   #back-to-top {
     background: linear-gradient(to bottom, #827265, #716053);  /* 调整背景颜色 */
   }
   ```

4. 圆角调整：
   ```css
   #back-to-top {
     border-radius: 4px;  /* 调整圆角大小 */
   }
   ```

## 注意事项

1. 确保WPCode插件已经正确安装并激活
2. 代码片段加载位置必须设置为"页面底部"
3. 如果页面没有足够的滚动高度（小于300像素），按钮不会显示
4. 如果遇到显示问题，请检查：
   - 浏览器缓存是否已清除
   - 是否有其他插件的返回顶部按钮造成冲突
   - 主题是否有CSS样式覆盖

## 兼容性

- 支持所有现代浏览器
- 兼容移动端设备
- 适配响应式布局
- 支持触摸屏操作