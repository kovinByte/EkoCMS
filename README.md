# EkoCMS

EkoCMS 是一套基于 [PBootCMS](https://github.com/pbootcmspro/PbootCMS/) 进行**翻版开发**的 PHP 企业网站开发建设管理系统。

系统保留原版本简单直观的模板标签体系，并在此基础上扩展新的业务模块，以满足新的建站需求。标签语法逻辑与原版本完全一致，已有 PBootCMS 模板可以批处理迁移使用。

**永久开源免费，可免费商用。**

当前版本：`v0.0.1`

---

## 为什么需要这个项目

项目作者有一批长期运行的企业站点都基于 PBootCMS：简单、易用，批量起站非常方便。但随着站点数据与业务持续扩展，原版本逐渐无法满足新的需求。

与其在原版本上反复二次开发，不如直接基于原版本进行翻版开发，于是有了 EkoCMS。

需要说明的是：EkoCMS 本身就是基于 PBootCMS 的翻版项目，并非从零重写；它在保留原有成熟机制的同时，补充了新的功能模块，标签语法与 PBootCMS 完全相同。

## 主要特性

- **模板标签驱动**：懂 HTML 即可开发企业网站，标签语法与 PBootCMS 一致。
- **双数据库支持**：默认 SQLite 轻量部署，也可切换 MySQL。
- **高效内核**：模板编译、页面缓存与数据查询分层处理，兼顾速度与稳定。
- **响应式后台**：桌面、平板与手机均可维护内容。
- **默认企业主题**：内置响应式官网主题，可直接使用或二次开发。
- **完整开发手册**：模板目录、基础语法、全局标签、内容标签、交互标签与附录。
- **开源可控**：程序、模板与数据结构开放，可自主部署与二次开发。

## 环境要求

| 项目 | 要求 |
| --- | --- |
| PHP | 7.4 及以上 |
| 必需扩展 | `mbstring`、`curl`、`gd` |
| 数据库扩展 | `sqlite3` / `pdo_sqlite`，或 `mysqli` |
| 数据库 | SQLite（默认）/ MySQL |
| 可写目录 | `data/`、`runtime/` |

## 快速开始

1. **上传程序**：将完整程序包解压并上传到站点根目录，保持目录结构不变，支持子目录部署。
2. **设置目录权限**：确保 Web 运行用户对 `data/`、`runtime/` 具有读写权限。
3. **配置数据库**：默认使用 SQLite，通常无需修改；如需 MySQL，编辑 [config/database.php](config/database.php)，将 `type` 改为 `mysqli` 并填写连接信息。
4. **配置伪静态**：Nginx 规则参考 `rewrite/nginx.txt`，IIS 使用 `rewrite/web.config`，Apache 请按 `rewrite/readme.txt` 的说明配置 `.htaccess`。
5. **访问站点**：前台访问站点域名，后台入口为 `/admin.php`，使用管理员账号登录。

> 切换 MySQL 时，可导入 `static/backup/sql/ekocms.sql` 初始化数据结构与默认内容。

## 从 PBootCMS 迁移

1. 备份原站点数据库，将表前缀 `ay_` 批量替换为 `ek_`，再导入 EkoCMS 使用的数据库。
2. 批量替换模板标签：将 `{poot:}` 替换为 `{eko:}`。
3. 核对栏目绑定模板、伪静态规则与静态资源路径，清理模板编译与页面缓存后预览。

> 建议先在测试环境完整演练，并保留原数据库与模板备份，确认无误后再切换正式站点。

## 目录结构

```text
EkoCMS/
├── admin.php              # 后台入口
├── index.php              # 前台入口
├── api.php                # API 入口
├── apps/                  # 应用目录（前台 / 后台 / 公共）
├── config/                # 站点与数据库配置
├── core/                  # 框架内核
├── data/                  # SQLite 数据库与上传数据（可写）
├── runtime/               # 缓存、编译文件、会话（可写）
├── rewrite/               # 伪静态规则
├── static/                # 静态资源、上传图片、安装 SQL
├── template/              # 前台主题模板
│   └── default/
│       ├── html/          # 页面模板
│       └── assets/        # 模板静态资源
```

## 模板开发

模板位于 `template/default/html`，静态资源位于 `template/default/assets`。

```html
<!-- 新闻列表 -->
{eko:list scode=2 num=4 order=date}
<a href="[list:link]">
  <h3>[list:title lencn=26]</h3>
  <time>[list:date style=Y-m-d]</time>
</a>
{/eko:list}

<!-- 站点信息 -->
{eko:sitetitle} · {eko:companyphone}
```

静态资源引用请使用 `{eko:sitetplpath}/assets/...`，以适配子目录部署。

## 文档

- 官网：[https://ekocms.d50.cn/index.html](https://ekocms.d50.cn/index.html)
- 使用方法：[https://ekocms.d50.cn/guide.html](https://ekocms.d50.cn/guide.html)
- 更新日志：[https://ekocms.d50.cn/changelog.html](https://ekocms.d50.cn/changelog.html)
- 模板开发手册：[https://ekocms.d50.cn/manual/index.html](https://ekocms.d50.cn/manual/index.html)

## 免费与支持

本项目永久免费，请勿向本项目支付任何费用（包括但不限于捐赠、赞助、广告等）。

如需打赏支持原项目，请前往 [PBootCMS](https://github.com/pbootcmspro/PbootCMS/)。

欢迎通过 GitHub 提交 Issue 或 Pull Request 参与共建。

- 源码仓库：<https://github.com/kovinByte/EkoCMS>
- 官方网站：<https://ekocms.d50.cn>
- 联系方式：0531-1234-5678 · admin@ekocms.com
- 公司地址：山东济南高新区新泺大街888号

## 版权

Copyright © 2018-2026 EkoCMS All Rights Reserved.
