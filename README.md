# 《求和!李姐万岁! GitBook For WordPress》

GitBook For WordPress

求和! 李姐万岁! 这是一个**GitBook布局，锤子便签风格**的WordPress主题~

原主题来源：[@zhaoolee/gitbook-for-wordpress](https://github.com/zhaoolee/gitbook-for-wordpress)

## 主要功能

### 原有功能

1. 无需像GitBook一样定制目录, 侧边栏按时间倒序显示已发布文章
2. 侧边栏自动将当前文章滚动到侧边栏顶部
3. 支持按照专题过滤文章列表
4. 支持宽窄屏自适应
5. 支持锤子便签风格的评论
6. 支持搜索
7. 底部预留备案号位置，支持自定义，不填就不显示
8. 支持显示后台菜单
9. 支持小部件

### 新增功能

1. 前台用户系统
   - 用户登录：支持用户名/邮箱登录，记住登录状态
   - 用户注册：支持新用户注册，自动验证邮箱
   - 密码找回：支持通过邮箱重置密码
   - 用户中心：集中管理用户相关功能

2. Markdown 支持
   - 评论支持 Markdown：支持粗体、斜体、代码、链接等格式
   - 实时预览：编写评论时可实时预览 Markdown 效果
   - 工具栏：提供常用 Markdown 语法快捷插入按钮

3. 用户中心功能
   - 个人资料：修改显示名称、邮箱、个人简介等
   - 文章管理：查看、编辑自己发布的文章
   - 评论管理：查看、编辑自己发表的评论
   - 收藏功能：收藏感兴趣的文章
   - 消息通知：系统通知、评论回复等提醒

4. 文章标签页
   - 多标签文章列表：最新文章、最近修改、热门文章等
   - 自定义标签：支持添加自定义标签和RSS内容
   - 缓存机制：优化加载性能，减轻服务器负担
   - 响应式设计：完美适配移动端显示
   - 后台设置：可配置显示数量、缓存时间等

## 使用说明

### 主题安装

1. 下载主题文件
2. 上传到 WordPress 主题目录：`wp-content/themes/`
3. 在后台启用主题
4. 主题会自动创建必要的页面（登录、注册、用户中心等）

### 用户系统配置

1. 确保 WordPress 允许用户注册：
   - 进入 设置 > 常规
   - 勾选"任何人都可以注册"
   - 选择新用户的默认角色（推荐设置为"订阅者"）

2. 配置邮件发送：
   - 建议安装 SMTP 插件以确保邮件正常发送
   - 配置发件邮箱信息
   - 测试邮件发送功能

### 文章标签页配置

1. 创建标签页：
   - 新建页面并选择"文章标签页"模板
   - 可设置为首页或普通页面

2. 后台设置：
   - 进入 外观 > 文章标签页
   - 配置每页显示数量和摘要长度
   - 设置不同类型内容的缓存时间
   - 添加/管理自定义标签和RSS内容

3. RSS内容展示：
   - 安装RSS聚合插件
   - 在自定义标签中填写RSS短代码
   - 支持多个RSS源

### 用户权限设置

1. 文章权限：
   - 普通用户只能查看和编辑自己的文章
   - 管理员可以查看和编辑所有文章

2. 评论权限：
   - 用户可以编辑自己的评论
   - 评论修改后需要重新审核
   - 管理员可以直接编辑和删除任何评论

### Markdown 使用

1. 评论支持的 Markdown 语法：
   - **粗体**：`**文本**`
   - *斜体*：`*文本*`
   - `代码`：`` `代码` ``
   - 代码块：` ```代码块``` `
   - [链接](url)：`[文本](url)`
   - > 引用：`> 引用文本`

2. 工具栏按钮：
   - B：插入粗体
   - I：插入斜体
   - Code：插入行内代码
   - CodeBlock：插入代码块
   - Link：插入链接
   - Quote：插入引用

## 示例网站

方圆小站 https://fangyuanxiaozhan.com

## 开发说明

如果你像zhaoolee一样不想写富文本，只想用Markdown，可以使用zhaoolee的另一个开源项目 用Hexo的方式管理WordPress(使用Github Actions自动更新文章到WordPress)：https://github.com/zhaoolee/WordPressXMLRPCTools

## WordPress免费插件推荐列表

| 插件名称 | 功能简介 | 下载页面 |
| --- | --- | --- |
| POST VIEWS COUNTER | 查看文章阅读量 | http://www.dfactory.eu/plugins/post-views-counter/ |
| WP Super Cache | 对WordPress页面进行静态页缓存 | https://wordpress.org/plugins/wp-super-cache/ |
| instant.page | 用户鼠标滑到超链接时, 预加载网页 | https://wordpress.org/plugins/instant-page/ |

## 开发小技巧

将开发的主题软连接到WordPress Theme目录：
```bash
ln -s /path/to/your/theme /path/to/wordpress/wp-content/themes/your-theme
```

## 常见问题

1. 注册邮件未收到
   - 检查邮件发送配置
   - 检查垃圾邮件文件夹
   - 尝试使用其他邮箱注册

2. 评论 Markdown 不生效
   - 确保主题 JS 文件正确加载
   - 清除浏览器缓存
   - 检查是否有 JS 错误

3. 用户中心访问提示需要登录
   - 检查是否已登录
   - 清除浏览器 Cookie
   - 重新登录

4. 文章标签页加载问题
   - 检查缓存设置是否合理
   - 确认RSS短代码格式正确
   - 清除浏览器缓存和主题缓存

## 致谢

感谢 [@zhaoolee](https://github.com/zhaoolee) 开发的原始主题，本主题在其基础上添加了用户系统等功能。

## 许可证

遵循原项目的开源协议。