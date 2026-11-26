-- Online Database Management SQL Dump
-- 数据库名: ekocms
-- 生成日期: 2020-07-05 23:55:34
-- PHP 版本: 5.6.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+08:00";
SET NAMES utf8;

-- --------------------------------------------------------

--
-- 表的结构 `ek_area`
--

DROP TABLE IF EXISTS `ek_area`;
CREATE TABLE `ek_area` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '区域编号',
  `acode` varchar(20) NOT NULL COMMENT '区域编码',
  `pcode` varchar(20) NOT NULL COMMENT '区域父编码',
  `name` varchar(50) NOT NULL COMMENT '区域名称',
  `domain` varchar(100) NOT NULL COMMENT '区域绑定域名',
  `is_default` char(1) NOT NULL DEFAULT '0' COMMENT '是否默认',
  `create_user` varchar(30) NOT NULL COMMENT '添加人员',
  `update_user` varchar(30) NOT NULL COMMENT '更新人员',
  `create_time` datetime NOT NULL COMMENT '添加时间',
  `update_time` datetime NOT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `ek_area_acode` (`acode`),
  KEY `ek_area_pcode` (`pcode`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

--
-- 转存表中的数据 `ek_area`
--

INSERT INTO `ek_area` (`id`,`acode`,`pcode`,`name`,`domain`,`is_default`,`create_user`,`update_user`,`create_time`,`update_time`) VALUES
('1','cn','0','中文','','1','admin','admin','2017-11-30 13:55:37','2018-04-13 11:40:49');

-- --------------------------------------------------------

--
-- 表的结构 `ek_company`
--

DROP TABLE IF EXISTS `ek_company`;
CREATE TABLE `ek_company` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '站点编号',
  `acode` varchar(20) NOT NULL COMMENT '区域代码',
  `name` varchar(100) NOT NULL COMMENT '公司名称',
  `address` varchar(200) NOT NULL COMMENT '公司地址',
  `postcode` varchar(6) NOT NULL COMMENT '邮政编码',
  `contact` varchar(10) NOT NULL COMMENT '公司联系人',
  `mobile` varchar(50) NOT NULL COMMENT '手机号码',
  `phone` varchar(50) NOT NULL COMMENT '电话号码',
  `fax` varchar(50) NOT NULL COMMENT '公司传真',
  `email` varchar(30) NOT NULL COMMENT '电子邮箱',
  `qq` varchar(50) NOT NULL COMMENT '公司QQ',
  `weixin` varchar(100) NOT NULL COMMENT '微信图标',
  `blicense` varchar(20) NOT NULL COMMENT '营业执照代码',
  `other` varchar(200) NOT NULL COMMENT '其他信息',
  PRIMARY KEY (`id`),
  KEY `ek_company_acode` (`acode`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

--
-- 转存表中的数据 `ek_company`
--

INSERT INTO `ek_company` (`id`,`acode`,`name`,`address`,`postcode`,`contact`,`mobile`,`phone`,`fax`,`email`,`qq`,`weixin`,`blicense`,`other`) VALUES
('1','cn','济南某某网络科技有限公司','山东济南高新区新泺大街888号','410000','谢先生','13988886666','0531-1234-5678','0731-88886666','admin@ekocms.com','88886666','/static/upload/image/20180715/1531651052464521.png','91430102567888888G','');

-- --------------------------------------------------------

--
-- 表的结构 `ek_config`
--

DROP TABLE IF EXISTS `ek_config`;
CREATE TABLE `ek_config` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '编号',
  `name` varchar(30) NOT NULL COMMENT '名称',
  `value` varchar(200) NOT NULL COMMENT '值',
  `type` char(1) NOT NULL DEFAULT '1' COMMENT '配置类型',
  `sorting` int(10) unsigned NOT NULL DEFAULT '255' COMMENT '排序',
  `description` varchar(30) NOT NULL COMMENT '描述文本',
  PRIMARY KEY (`id`),
  KEY `ek_config_name` (`name`)
) ENGINE=MyISAM AUTO_INCREMENT=45 DEFAULT CHARSET=utf8;

--
-- 转存表中的数据 `ek_config`
--

INSERT INTO `ek_config` (`id`,`name`,`value`,`type`,`sorting`,`description`) VALUES
('1','open_wap','0','1','255','手机版'),
('2','message_check_code','1','1','255','留言验证码'),
('3','smtp_server','smtp.qq.com','2','255','邮件SMTP服务器'),
('4','smtp_port','465','2','255','邮件SMTP端口'),
('5','smtp_ssl','1','1','255','邮件是否安全连接'),
('6','smtp_username','','2','255','邮件发送账号'),
('7','smtp_password','','2','255','邮件发送密码'),
('8','admin_check_code','1','1','255','后台验证码'),
('9','weixin_appid','','2','255','微信APPID'),
('10','weixin_secret','','2','255','微信SECRET'),
('11','message_send_mail','1','1','255','留言发送邮件开关'),
('12','message_send_to','','1','255','留言发送到邮箱'),
('13','api_open','0','2','255','API开关'),
('14','api_auth','1','2','255','API强制认证'),
('15','api_appid','','2','255','API认证用户'),
('16','api_secret','','2','255','API认证密钥'),
('17','baidu_zz_token','','2','255','百度站长密钥'),
('18','baidu_xzh_appid','','2','255','熊掌号appid'),
('19','baidu_xzh_token','','2','255','熊掌号token'),
('20','wap_domain','','2','255','手机绑定域名'),
('21','gzip','0','2','255','GZIP压缩'),
('22','content_tags_replace_num','','2','255','内容关键字替换次数'),
('23','smtp_username_test','','2','255','测试邮箱'),
('24','form_send_mail','0','2','255','表单发送邮件'),
('25','baidu_xzh_type','0','2','255','熊掌号推送类型'),
('26','watermark_open','0','2','255','水印开关'),
('27','watermark_text','EkoCMS','2','255','水印文本'),
('28','watermark_text_font','','2','255','水印文本字体'),
('29','watermark_text_size','20','2','255','水印文本字号'),
('30','watermark_text_color','100,100,100','2','255','水印文本字体颜色'),
('31','watermark_pic','/static/images/logo.png','2','255','水印图片'),
('32','watermark_position','4','2','255','水印位置'),
('33','message_verify','1','2','255','留言审核'),
('34','form_check_code','0','2','255','表单验证码'),
('35','lock_count','5','2','255','登陆锁定阈值'),
('36','lock_time','900','2','255','登录锁定时间'),
('37','url_rule_type','3','2','255','路径类型'),
('38','message_status','1','2','255','留言开关'),
('39','form_status','1','2','255','表单开关'),
('40','tpl_html_dir','html','2','255','模板HTML目录'),
('41','ip_deny','','2','255','IP黑名单'),
('42','ip_allow','','2','255','IP白名单'),
('43','url_index_404','0','2','255','跳转404'),
('44','tpl_html_cache','1','1','255','动态缓存开关'),
('45','tpl_html_cache_time','900000000000','2','255','缓存有效期(秒)');

-- --------------------------------------------------------

--
-- 表的结构 `ek_content`
--

DROP TABLE IF EXISTS `ek_content`;
CREATE TABLE `ek_content` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '编号',
  `acode` varchar(20) NOT NULL COMMENT '区域',
  `scode` varchar(20) NOT NULL COMMENT '内容栏目',
  `subscode` varchar(20) NOT NULL COMMENT '副栏目',
  `title` varchar(100) NOT NULL COMMENT '标题',
  `titlecolor` varchar(7) NOT NULL COMMENT '标题颜色',
  `subtitle` varchar(100) NOT NULL COMMENT '副标题',
  `filename` varchar(50) NOT NULL COMMENT '自定义文件名',
  `author` varchar(30) NOT NULL COMMENT '作者',
  `source` varchar(30) NOT NULL COMMENT '来源',
  `outlink` varchar(100) NOT NULL COMMENT '外链地址',
  `date` datetime NOT NULL COMMENT '发布日期',
  `ico` varchar(100) NOT NULL COMMENT '缩略图',
  `pics` varchar(1000) NOT NULL COMMENT '多图片',
  `picstitle` varchar(1000) NOT NULL COMMENT '多图片标题',
  `contenttpl` varchar(255) NOT NULL DEFAULT '' COMMENT '详情页模板',
  `content` mediumtext NOT NULL COMMENT '内容',
  `tags` varchar(500) NOT NULL COMMENT 'tag关键字',
  `enclosure` varchar(100) NOT NULL COMMENT '附件',
  `keywords` varchar(200) NOT NULL COMMENT '关键字',
  `description` varchar(500) NOT NULL COMMENT '描述',
  `sorting` int(10) unsigned NOT NULL DEFAULT '255' COMMENT '内容排序',
  `status` char(1) NOT NULL DEFAULT '1' COMMENT '状态',
  `istop` char(1) NOT NULL DEFAULT '0' COMMENT '是否置顶',
  `isrecommend` char(1) NOT NULL DEFAULT '0' COMMENT '是否推荐',
  `isheadline` char(1) NOT NULL DEFAULT '0' COMMENT '是否头条',
  `visits` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '访问数',
  `likes` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '点赞数',
  `oppose` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '反对数',
  `create_user` varchar(30) NOT NULL COMMENT '创建人员',
  `update_user` varchar(20) NOT NULL COMMENT '更新人员',
  `create_time` datetime NOT NULL COMMENT '创建时间',
  `update_time` datetime NOT NULL COMMENT '更新时间',
  `gtype` char(1) NOT NULL DEFAULT '4',
  `gid` varchar(20) NOT NULL DEFAULT '',
  `gnote` varchar(100) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `ek_content_scode` (`scode`),
  KEY `ek_content_subscode` (`subscode`),
  KEY `ek_content_acode` (`acode`),
  KEY `ek_content_filename` (`filename`),
  KEY `ek_content_date` (`date`),
  KEY `ek_content_sorting` (`sorting`),
  KEY `ek_content_status` (`status`),
  KEY `ek_content_title_index` (`title`)
) ENGINE=MyISAM AUTO_INCREMENT=18 DEFAULT CHARSET=utf8;

--
-- 转存表中的数据 `ek_content`
--

INSERT INTO `ek_content` (`id`,`acode`,`scode`,`subscode`,`title`,`titlecolor`,`subtitle`,`filename`,`author`,`source`,`outlink`,`date`,`ico`,`pics`,`content`,`tags`,`enclosure`,`keywords`,`description`,`sorting`,`status`,`istop`,`isrecommend`,`isheadline`,`visits`,`likes`,`oppose`,`create_user`,`update_user`,`create_time`,`update_time`,`gtype`,`gid`,`gnote`) VALUES
('1','cn','1','','公司简介','#333333','','','admin','本站','','2018-04-11 17:26:11','','','<p>
    &nbsp;&nbsp;&nbsp;&nbsp;<span style="font-size: 18px;">EkoCMS是基于PBootCMS翻版开发且永久开源免费的PHP企业网站开发建设管理系统，是一套高效、简洁、
 强悍的可免费商用的PHP 
CMS源码，能够满足各类企业网站开发建设的需要。系统采用简单到想哭的模板标签，只要懂HTML就可快速开发企业网站。官方提供了大量网站模板免费下载和使用，将致力于为广大开发者和企业提供最佳的网站开发建设解决方案。</span>
</p>
<p>
    &nbsp;&nbsp;&nbsp;&nbsp;1、系统采用高效、简洁、强悍的模板标签，只要懂HTML就可快速开发企业网站；
</p>
<p>
    &nbsp;&nbsp;&nbsp;&nbsp;2、系统采用PHP语言开发，使用自主研发的高速多层开发框架及缓存技术；
</p>
<p>
    &nbsp;&nbsp;&nbsp;&nbsp;3、系统默认采用sqlite轻型数据库，放入PHP空间即可直接使用，可选mysql等数据库，满足各类存储需求；
</p>
<p>
    &nbsp;&nbsp;&nbsp;&nbsp;4、系统采用响应式管理后台，满足各类设备随时管理的需要；
</p>
<p>
    &nbsp;&nbsp;&nbsp;&nbsp;5、系统支持后台在线升级，满足系统及时升级更新的需要；
</p>
<p>
    &nbsp;&nbsp;&nbsp;&nbsp;6、系统支持内容模型、多语言、自定义表单、筛选、多条件搜索、小程序、APP等功能；
</p>
<p>
    &nbsp;&nbsp;&nbsp;&nbsp;7、系统支持多种URL模式及模型、栏目、内容自定义地址名称，满足各类网站推广优化的需要。<br/>
</p>
<p>
    <br/>
</p>
<p>
    <strong><span style="font-size: 18px;">源码托管地址：</span></strong>
</p>
<p>
    GitHub：<a href="https://github.com/kovinByte/EkoCMS" target="_blank">https://github.com/kovinByte/EkoCMS</a><br/>
</p>

<p>
    <strong><span style="font-size: 18px;"><br/></span></strong>
</p>
<p>
    <strong><span style="font-size: 18px;">简单到想哭的标签：</span></strong>
</p>
<pre class="brush:html;toolbar:false">1、全局标签示意：
{eko:sitetitle} 站点标题 
{eko:sitelogo} 站点logo
2、列表页标签示意：
{eko:list num=10 order=date}    
    &lt;p&gt;&lt;a href=&quot;[list:link]&quot;&gt;[list:title]&lt;/a&gt;&lt;/p&gt;
{/eko:list}
3、内容页标签示意：
{content:title} 标题
{content:subtitle}副标题
{content:author} 作者
{content:source} 来源
更多简单到想哭的标签请参考开发手册...</pre>','','','','EkoCMS是基于PBootCMS翻版开发且永久开源免费的PHP企业网站开发建设管理系统，是一套高效、简洁、 强悍的可免费商用的PHP CMS源码，能够满足各类企业网站开发建设的需要。系统采用简单到想哭的模板标签，只要懂HTML就可快速开发企业网站。官方提供了大量网站模板免费下载和使用，将致力于为广大开发者和企','255','1','0','0','0','39','0','0','admin','admin','2018-04-11 17:26:11','2019-08-05 11:19:51','4','',''),
('2','cn','10','','在线留言','#333333','','','admin','本站','','2018-04-11 17:30:36','','','','','','','','255','1','0','0','0','27','0','0','admin','admin','2018-04-11 17:30:36','2018-04-11 17:30:36','4','',''),
('3','cn','11','','联系我们','#333333','','','admin','本站','','2018-04-11 17:31:29','','','<p>官方网站：<a href="http://ekocms.d50.cn">ekocms.d50.cn</a><br/></p><p>我们一直秉承大道至简分享便可改变世界的理念，坚持做最简约灵活的EkoCMS开源软件！</p><p>您的每一份帮助都将支持EkoCMS做的更好，走的更远！</p><p>我们一直在坚持不懈地努力，并尽可能让EkoCMS完全开源免费，您的帮助将使我们更有动力和信心^_^！</p>','','','','','255','1','0','0','0','18','0','0','admin','admin','2018-04-11 17:31:29','2018-04-13 09:30:19','4','',''),
('4','cn','3','','EkoCMS V0.0.1 正式发布：永久开源免费的企业建站系统','#333333','翻版开发、模板标签、轻量部署，一套系统满足企业官网建设需求','','admin','本站','','2026-11-24 10:00:00','/static/upload/image/20180412/1523499864406172.jpg','','<p style="text-indent:2em;">经过持续打磨，EkoCMS V0.0.1 正式与大家见面。EkoCMS 是一套基于PBootCMS翻版开发且永久开源免费的 PHP 企业网站开发建设管理系统，定位于高效、简洁、可免费商用的建站工具，能够满足各类企业官网的开发与运营需求。</p>
<p style="text-indent:2em;"><strong>本次版本的主要特性：</strong></p>
<p style="text-indent:2em;">1、翻版内核：基于 PBootCMS 原有架构进行翻版开发，保留成熟机制并扩展新的业务模块，二次开发更清晰。</p>
<p style="text-indent:2em;">2、模板标签：延续简单到想哭的标签体系，只要懂 HTML 就能快速开发企业网站，原有模板可批量迁移使用。</p>
<p style="text-indent:2em;">3、轻量部署：默认采用 SQLite 轻型数据库，上传到 PHP 空间即可直接使用，也可切换 MySQL 满足更大数据量需求。</p>
<p style="text-indent:2em;">4、响应式后台：管理端适配各类设备，随时随地维护栏目、内容与站点配置。</p>
<p style="text-indent:2em;">5、SEO 友好：内置标题、关键词、描述、伪静态与静态缓存能力，便于搜索引擎收录。</p>
<p style="text-indent:2em;">EkoCMS 承诺永久开源免费，可免费商用。后续版本将持续完善内容模型、模板生态与安全能力，欢迎广大开发者与建站服务商共同参与。</p>','EkoCMS,开源CMS,企业建站','','EkoCMS,开源CMS,免费CMS,企业建站,PHP CMS','EkoCMS V0.0.1 正式发布，基于 PBootCMS 翻版开发，采用简单直观的模板标签，支持 SQLite 与 MySQL，放入 PHP 空间即可使用。','255','1','1','0','0','4','0','0','admin','admin','2018-04-11 17:43:19','2026-11-24 10:00:00','4','',''),
('5','cn','4','','开源 CMS 成为中小企业建站的主流选择','#333333','成本、自主可控与生态，是开源建站系统受欢迎的三个关键原因','','admin','本站','','2026-11-20 14:00:00','','','<p style="text-indent:2em;">过去，企业建设官网往往依赖闭源建站平台，按年付费、功能受限、数据难以迁移。随着开源生态的成熟，越来越多的中小企业开始转向开源 CMS，把网站真正掌握在自己手里。</p>
<p style="text-indent:2em;"><strong>第一，成本更可控。</strong>开源 CMS 通常可以免费商用，企业只需承担服务器与域名成本。对于预算有限的中小企业，这意味着可以用更低的投入获得完整的企业官网能力。</p>
<p style="text-indent:2em;"><strong>第二，数据更自主。</strong>网站程序与内容数据部署在自己的服务器上，栏目结构、内容数据、访问日志都由企业掌握，不依赖第三方平台的规则变化。</p>
<p style="text-indent:2em;"><strong>第三，生态更开放。</strong>开源系统拥有模板与插件生态，建站服务商可以基于同一套系统批量交付项目，开发成本更低，交付周期更短。</p>
<p style="text-indent:2em;">EkoCMS 正是在这样的背景下诞生。系统坚持永久开源免费，采用简单直观的模板标签与轻量部署方式，既适合企业自建官网，也适合建站服务商批量起站。</p>
<p style="text-indent:2em;">可以预见，随着企业对数字化自主权的重视，开源 CMS 的市场占比还会继续提升。</p>','开源CMS,企业建站,行业观察','','开源CMS,企业建站,中小企业建站,PHP CMS','从成本结构到数据自主可控，开源 CMS 正在成为中小企业建设官网的主流方案，EkoCMS 也是其中的代表之一。','255','1','0','0','0','4','0','0','admin','admin','2018-04-12 10:06:15','2026-11-20 14:00:00','4','',''),
('6','cn','4','','从模板到上线：企业官网建设的效率革命','#333333','模板化开发让官网交付从数周缩短到数天','','admin','本站','','2026-11-18 16:20:00','/static/upload/image/20180412/1523499864406172.jpg','','<p style="text-indent:2em;">传统企业官网项目通常需要经历需求沟通、原型设计、页面开发、后台对接、内容录入与测试上线等多个环节，周期长、沟通成本高。模板化开发的出现，让这条链路明显变短。</p>
<p style="text-indent:2em;">在 EkoCMS 中，开发者可以把页面结构沉淀为模板，把可变内容交给后台维护。同一套模板可以复用于不同企业客户，只需替换 Logo、配色与栏目内容，就能快速交付一个完整官网。</p>
<p style="text-indent:2em;">效率提升主要体现在三个方面：</p>
<p style="text-indent:2em;">1、开发效率：模板标签直接调用后台数据，无需为每个项目重复编写数据接口。</p>
<p style="text-indent:2em;">2、交付效率：内容由客户在后台自行维护，减少反复修改页面的沟通成本。</p>
<p style="text-indent:2em;">3、运维效率：统一的技术栈与目录结构，让后续升级、迁移和故障排查更简单。</p>
<p style="text-indent:2em;">对于建站服务商而言，模板化不仅是效率工具，更是可复用的资产。把经过验证的模板沉淀下来，新项目的起点就从零变成了八成。</p>
<p style="text-indent:2em;">EkoCMS 提供的模板机制与缓存能力，正是为了支撑这种高效交付模式而设计。</p>','企业建站,模板开发,效率','','企业官网建设,模板开发,建站效率,EkoCMS','模板化开发正在改变企业官网的交付方式，借助 EkoCMS 的模板标签体系，建站周期可以大幅缩短。','255','1','0','0','0','5','0','0','admin','admin','2018-04-12 10:08:03','2026-11-18 16:20:00','4','',''),
('7','cn','4','','企业为什么选择自主可控的建站系统','#333333','数据主权、可迁移性与长期成本，决定建站系统的选择','','admin','本站','','2026-11-16 11:10:00','/static/upload/image/20180412/1523499864406172.jpg','','<p style="text-indent:2em;">企业官网是品牌与业务的长期入口，选错建站系统，往往在几年后付出更高的迁移成本。因此，越来越多企业在选型时把自主可控放在首位。</p>
<p style="text-indent:2em;"><strong>数据主权。</strong>网站内容、客户留言、访问数据都沉淀在企业自己的数据库中，不依赖第三方平台导出，也不会因为平台停服而丢失。</p>
<p style="text-indent:2em;"><strong>可迁移性。</strong>自主部署的系统可以随时更换服务器、切换数据库、调整代码。即使更换服务商，网站本身也不会被绑定。</p>
<p style="text-indent:2em;"><strong>长期成本。</strong>闭源平台通常按年收取授权与服务费用，随着年限增加，总成本可能远超自建。开源系统一次投入、长期使用，成本结构更透明。</p>
<p style="text-indent:2em;">EkoCMS 采用开源模式，程序、模板与数据结构都开放可控。企业既可以自行部署维护，也可以委托服务商实施，选择权始终掌握在企业手中。</p>
<p style="text-indent:2em;">官网是企业的数字资产，值得用一套能长期掌控的系统来承载。</p>','建站系统,数据自主,企业数字化','','建站系统,自主可控,企业数字化,开源建站','选择建站系统时，数据主权、可迁移性与长期成本比短期价格更重要。本文从三个角度说明企业为何倾向自主可控的建站方案。','255','1','0','0','0','13','0','0','admin','admin','2018-04-12 10:09:37','2026-11-16 11:10:00','4','',''),
('8','cn','3','','EkoCMS 模板标签详解：懂 HTML 就能快速开发企业网站','#333333','全局标签、列表标签、内容标签，一套语法覆盖常见建站场景','','admin','本站','','2026-11-22 09:30:00','/static/upload/image/20180412/1523499864406172.jpg','','<p style="text-indent:2em;">模板标签是 EkoCMS 最核心的能力之一。系统把常用数据封装为直观的标签语法，开发者只需掌握 HTML，就能完成企业网站的前端开发。</p>
<p style="text-indent:2em;"><strong>1、全局标签</strong></p>
<p style="text-indent:2em;">用于输出站点级信息，例如 {eko:sitetitle} 输出站点标题，{eko:sitelogo} 输出站点 Logo，{eko:companyphone} 输出联系电话。页头、页脚等公共区域使用全局标签即可自适应后台配置。</p>
<p style="text-indent:2em;"><strong>2、列表标签</strong></p>
<p style="text-indent:2em;">用于循环输出栏目内容，例如 {eko:list num=10 order=date} 与 {/eko:list} 成对使用，内部通过 [list:title]、[list:link]、[list:date] 等标签取字段，适用于新闻列表、产品列表与服务案例列表。</p>
<p style="text-indent:2em;"><strong>3、内容标签</strong></p>
<p style="text-indent:2em;">用于内容详情页，例如 {content:title} 输出标题，{content:content} 输出正文，{content:date} 输出发布时间，配合上一篇、下一篇标签即可完成完整详情页。</p>
<p style="text-indent:2em;">除上述基础标签外，系统还提供导航标签、分类标签、标签云、搜索、留言与会员等扩展标签。标签语法与原 PBootCMS 保持兼容，已有模板可以低成本迁移到 EkoCMS。</p>
<p style="text-indent:2em;">完整的标签说明可参考项目内置的模板开发手册，建议开发前先通读一遍，能显著减少重复工作。</p>','EkoCMS,模板标签,建站教程','','EkoCMS模板标签,企业建站教程,PHP CMS模板','EkoCMS 采用简单直观的模板标签体系，掌握全局标签、列表标签与内容标签即可快速完成企业网站开发。','255','1','0','0','0','4','0','0','admin','admin','2018-04-12 10:10:46','2026-11-22 09:30:00','4','',''),
('9','cn','7','','域名注册服务','#333333','','','admin','本站','','2018-04-12 10:11:20','/static/upload/image/20180412/1523499435499884.png','','<p>
    &nbsp;&nbsp;&nbsp;&nbsp;<span style="font-size: 18px;">EkoCMS是基于PBootCMS翻版开发且永久开源免费的PHP企业网站开发建设管理系统，是一套高效、简洁、
 强悍的可免费商用的PHP 
CMS源码，能够满足各类企业网站开发建设的需要。系统采用简单到想哭的模板标签，只要懂HTML就可快速开发企业网站。官方提供了大量网站模板免费下载和使用，将致力于为广大开发者和企业提供最佳的网站开发建设解决方案。</span>
</p>
<p>
    &nbsp;&nbsp;&nbsp;&nbsp;1、系统采用高效、简洁、强悍的模板标签，只要懂HTML就可快速开发企业网站；
</p>
<p>
    &nbsp;&nbsp;&nbsp;&nbsp;2、系统采用PHP语言开发，使用自主研发的高速多层开发框架及缓存技术；
</p>
<p>
    &nbsp;&nbsp;&nbsp;&nbsp;3、系统默认采用sqlite轻型数据库，放入PHP空间即可直接使用，可选mysql等数据库，满足各类存储需求；
</p>
<p>
    &nbsp;&nbsp;&nbsp;&nbsp;4、系统采用响应式管理后台，满足各类设备随时管理的需要；
</p>
<p>
    &nbsp;&nbsp;&nbsp;&nbsp;5、系统支持后台在线升级，满足系统及时升级更新的需要；
</p>
<p>
    &nbsp;&nbsp;&nbsp;&nbsp;6、系统支持内容模型、多语言、自定义表单、筛选、多条件搜索、小程序、APP等功能；
</p>
<p>
    &nbsp;&nbsp;&nbsp;&nbsp;7、系统支持多种URL模式及模型、栏目、内容自定义地址名称，满足各类网站推广优化的需要。<br/>
</p>
<p>
    <br/>
</p>
<p>
    <strong><span style="font-size: 18px;">源码托管地址：</span></strong>
</p>
<p>
    GitHub：<a href="https://github.com/kovinByte/EkoCMS" target="_blank">https://github.com/kovinByte/EkoCMS</a><br/>
</p>

<p>
    <strong><span style="font-size: 18px;"><br/></span></strong>
</p>
<p>
    <strong><span style="font-size: 18px;">简单到想哭的标签：</span></strong>
</p>
<pre class="brush:html;toolbar:false">1、全局标签示意：
{eko:sitetitle} 站点标题 
{eko:sitelogo} 站点logo
2、列表页标签示意：
{eko:list num=10 order=date}    
    &lt;p&gt;&lt;a href=&quot;[list:link]&quot;&gt;[list:title]&lt;/a&gt;&lt;/p&gt;
{/eko:list}
3、内容页标签示意：
{content:title} 标题
{content:subtitle}副标题
{content:author} 作者
{content:source} 来源
更多简单到想哭的标签请参考开发手册...</pre>','','','','EkoCMS是基于PBootCMS翻版开发且永久开源免费的PHP企业网站开发建设管理系统，是一套高效、简洁、 强悍的可免费商用的PHP CMS源码，能够满足各类企业网站开发建设的需要。系统采用简单到想哭的模板标签，只要懂HTML就可快速开发企业网站。官方提供了大量网站模板免费下载和使用，将致力于为广大开发','255','1','0','0','0','6','0','0','admin','admin','2018-04-12 10:20:28','2019-08-05 11:20:53','4','',''),
('10','cn','6','','网站建设基础版','#333333','','','admin','本站','','2018-04-12 10:23:07','/static/upload/image/20180412/1523499813391526.jpg','','<p>
    &nbsp;&nbsp;&nbsp;&nbsp;<span style="font-size: 18px;">EkoCMS是基于PBootCMS翻版开发且永久开源免费的PHP企业网站开发建设管理系统，是一套高效、简洁、
 强悍的可免费商用的PHP 
CMS源码，能够满足各类企业网站开发建设的需要。系统采用简单到想哭的模板标签，只要懂HTML就可快速开发企业网站。官方提供了大量网站模板免费下载和使用，将致力于为广大开发者和企业提供最佳的网站开发建设解决方案。</span>
</p>
<p>
    &nbsp;&nbsp;&nbsp;&nbsp;1、系统采用高效、简洁、强悍的模板标签，只要懂HTML就可快速开发企业网站；
</p>
<p>
    &nbsp;&nbsp;&nbsp;&nbsp;2、系统采用PHP语言开发，使用自主研发的高速多层开发框架及缓存技术；
</p>
<p>
    &nbsp;&nbsp;&nbsp;&nbsp;3、系统默认采用sqlite轻型数据库，放入PHP空间即可直接使用，可选mysql等数据库，满足各类存储需求；
</p>
<p>
    &nbsp;&nbsp;&nbsp;&nbsp;4、系统采用响应式管理后台，满足各类设备随时管理的需要；
</p>
<p>
    &nbsp;&nbsp;&nbsp;&nbsp;5、系统支持后台在线升级，满足系统及时升级更新的需要；
</p>
<p>
    &nbsp;&nbsp;&nbsp;&nbsp;6、系统支持内容模型、多语言、自定义表单、筛选、多条件搜索、小程序、APP等功能；
</p>
<p>
    &nbsp;&nbsp;&nbsp;&nbsp;7、系统支持多种URL模式及模型、栏目、内容自定义地址名称，满足各类网站推广优化的需要。<br/>
</p>
<p>
    <br/>
</p>
<p>
    <strong><span style="font-size: 18px;">源码托管地址：</span></strong>
</p>
<p>
    GitHub：<a href="https://github.com/kovinByte/EkoCMS" target="_blank">https://github.com/kovinByte/EkoCMS</a><br/>
</p>

<p>
    <strong><span style="font-size: 18px;"><br/></span></strong>
</p>
<p>
    <strong><span style="font-size: 18px;">简单到想哭的标签：</span></strong>
</p>
<pre class="brush:html;toolbar:false">1、全局标签示意：
{eko:sitetitle} 站点标题 
{eko:sitelogo} 站点logo
2、列表页标签示意：
{eko:list num=10 order=date}    
    &lt;p&gt;&lt;a href=&quot;[list:link]&quot;&gt;[list:title]&lt;/a&gt;&lt;/p&gt;
{/eko:list}
3、内容页标签示意：
{content:title} 标题
{content:subtitle}副标题
{content:author} 作者
{content:source} 来源
更多简单到想哭的标签请参考开发手册...</pre>','','','','EkoCMS是基于PBootCMS翻版开发且永久开源免费的PHP企业网站开发建设管理系统，是一套高效、简洁、 强悍的可免费商用的PHP CMS源码，能够满足各类企业网站开发建设的需要。系统采用简单到想哭的模板标签，只要懂HTML就可快速开发企业网站。官方提供了大量网站模板免费下载和使用，将致力于为广大开发','255','1','0','0','0','2','0','0','admin','admin','2018-04-12 10:23:34','2019-08-05 11:20:42','4','',''),
('11','cn','6','','网站建设专业版','#333333','','','admin','本站','','2018-04-12 10:23:37','/static/upload/image/20180412/1523501297516241.jpg','','<p>
    &nbsp;&nbsp;&nbsp;&nbsp;<span style="font-size: 18px;">EkoCMS是基于PBootCMS翻版开发且永久开源免费的PHP企业网站开发建设管理系统，是一套高效、简洁、
 强悍的可免费商用的PHP 
CMS源码，能够满足各类企业网站开发建设的需要。系统采用简单到想哭的模板标签，只要懂HTML就可快速开发企业网站。官方提供了大量网站模板免费下载和使用，将致力于为广大开发者和企业提供最佳的网站开发建设解决方案。</span>
</p>
<p>
    &nbsp;&nbsp;&nbsp;&nbsp;1、系统采用高效、简洁、强悍的模板标签，只要懂HTML就可快速开发企业网站；
</p>
<p>
    &nbsp;&nbsp;&nbsp;&nbsp;2、系统采用PHP语言开发，使用自主研发的高速多层开发框架及缓存技术；
</p>
<p>
    &nbsp;&nbsp;&nbsp;&nbsp;3、系统默认采用sqlite轻型数据库，放入PHP空间即可直接使用，可选mysql等数据库，满足各类存储需求；
</p>
<p>
    &nbsp;&nbsp;&nbsp;&nbsp;4、系统采用响应式管理后台，满足各类设备随时管理的需要；
</p>
<p>
    &nbsp;&nbsp;&nbsp;&nbsp;5、系统支持后台在线升级，满足系统及时升级更新的需要；
</p>
<p>
    &nbsp;&nbsp;&nbsp;&nbsp;6、系统支持内容模型、多语言、自定义表单、筛选、多条件搜索、小程序、APP等功能；
</p>
<p>
    &nbsp;&nbsp;&nbsp;&nbsp;7、系统支持多种URL模式及模型、栏目、内容自定义地址名称，满足各类网站推广优化的需要。<br/>
</p>
<p>
    <br/>
</p>
<p>
    <strong><span style="font-size: 18px;">源码托管地址：</span></strong>
</p>
<p>
    GitHub：<a href="https://github.com/kovinByte/EkoCMS" target="_blank">https://github.com/kovinByte/EkoCMS</a><br/>
</p>

<p>
    <strong><span style="font-size: 18px;"><br/></span></strong>
</p>
<p>
    <strong><span style="font-size: 18px;">简单到想哭的标签：</span></strong>
</p>
<pre class="brush:html;toolbar:false">1、全局标签示意：
{eko:sitetitle} 站点标题 
{eko:sitelogo} 站点logo
2、列表页标签示意：
{eko:list num=10 order=date}    
    &lt;p&gt;&lt;a href=&quot;[list:link]&quot;&gt;[list:title]&lt;/a&gt;&lt;/p&gt;
{/eko:list}
3、内容页标签示意：
{content:title} 标题
{content:subtitle}副标题
{content:author} 作者
{content:source} 来源
更多简单到想哭的标签请参考开发手册...</pre>','','','','EkoCMS是基于PBootCMS翻版开发且永久开源免费的PHP企业网站开发建设管理系统，是一套高效、简洁、 强悍的可免费商用的PHP CMS源码，能够满足各类企业网站开发建设的需要。系统采用简单到想哭的模板标签，只要懂HTML就可快速开发企业网站。官方提供了大量网站模板免费下载和使用，将致力于为广大开发','255','1','0','0','0','5','0','0','admin','admin','2018-04-12 10:24:01','2019-08-05 11:20:33','4','',''),
('12','cn','6','','网站建设旗舰版','#333333','','','admin','本站','','2018-04-12 10:24:04','/static/upload/image/20180412/1523499864406172.jpg','','<p>
    &nbsp;&nbsp;&nbsp;&nbsp;<span style="font-size: 18px;">EkoCMS是基于PBootCMS翻版开发且永久开源免费的PHP企业网站开发建设管理系统，是一套高效、简洁、
 强悍的可免费商用的PHP 
CMS源码，能够满足各类企业网站开发建设的需要。系统采用简单到想哭的模板标签，只要懂HTML就可快速开发企业网站。官方提供了大量网站模板免费下载和使用，将致力于为广大开发者和企业提供最佳的网站开发建设解决方案。</span>
</p>
<p>
    &nbsp;&nbsp;&nbsp;&nbsp;1、系统采用高效、简洁、强悍的模板标签，只要懂HTML就可快速开发企业网站；
</p>
<p>
    &nbsp;&nbsp;&nbsp;&nbsp;2、系统采用PHP语言开发，使用自主研发的高速多层开发框架及缓存技术；
</p>
<p>
    &nbsp;&nbsp;&nbsp;&nbsp;3、系统默认采用sqlite轻型数据库，放入PHP空间即可直接使用，可选mysql等数据库，满足各类存储需求；
</p>
<p>
    &nbsp;&nbsp;&nbsp;&nbsp;4、系统采用响应式管理后台，满足各类设备随时管理的需要；
</p>
<p>
    &nbsp;&nbsp;&nbsp;&nbsp;5、系统支持后台在线升级，满足系统及时升级更新的需要；
</p>
<p>
    &nbsp;&nbsp;&nbsp;&nbsp;6、系统支持内容模型、多语言、自定义表单、筛选、多条件搜索、小程序、APP等功能；
</p>
<p>
    &nbsp;&nbsp;&nbsp;&nbsp;7、系统支持多种URL模式及模型、栏目、内容自定义地址名称，满足各类网站推广优化的需要。<br/>
</p>
<p>
    <br/>
</p>
<p>
    <strong><span style="font-size: 18px;">源码托管地址：</span></strong>
</p>
<p>
    GitHub：<a href="https://github.com/kovinByte/EkoCMS" target="_blank">https://github.com/kovinByte/EkoCMS</a><br/>
</p>

<p>
    <strong><span style="font-size: 18px;"><br/></span></strong>
</p>
<p>
    <strong><span style="font-size: 18px;">简单到想哭的标签：</span></strong>
</p>
<pre class="brush:html;toolbar:false">1、全局标签示意：
{eko:sitetitle} 站点标题 
{eko:sitelogo} 站点logo
2、列表页标签示意：
{eko:list num=10 order=date}    
    &lt;p&gt;&lt;a href=&quot;[list:link]&quot;&gt;[list:title]&lt;/a&gt;&lt;/p&gt;
{/eko:list}
3、内容页标签示意：
{content:title} 标题
{content:subtitle}副标题
{content:author} 作者
{content:source} 来源
更多简单到想哭的标签请参考开发手册...</pre>','','','','EkoCMS是基于PBootCMS翻版开发且永久开源免费的PHP企业网站开发建设管理系统，是一套高效、简洁、 强悍的可免费商用的PHP CMS源码，能够满足各类企业网站开发建设的需要。系统采用简单到想哭的模板标签，只要懂HTML就可快速开发企业网站。官方提供了大量网站模板免费下载和使用，将致力于为广大开发','255','1','0','0','0','10','0','0','admin','admin','2018-04-12 10:24:25','2019-08-05 11:20:25','4','',''),
('13','cn','7','','网站空间','#333333','','','admin','本站','','2018-04-12 10:24:52','/static/upload/image/20180412/1523499979727269.jpg','','<p>
    &nbsp;&nbsp;&nbsp;&nbsp;<span style="font-size: 18px;">EkoCMS是基于PBootCMS翻版开发且永久开源免费的PHP企业网站开发建设管理系统，是一套高效、简洁、
 强悍的可免费商用的PHP 
CMS源码，能够满足各类企业网站开发建设的需要。系统采用简单到想哭的模板标签，只要懂HTML就可快速开发企业网站。官方提供了大量网站模板免费下载和使用，将致力于为广大开发者和企业提供最佳的网站开发建设解决方案。</span>
</p>
<p>
    &nbsp;&nbsp;&nbsp;&nbsp;1、系统采用高效、简洁、强悍的模板标签，只要懂HTML就可快速开发企业网站；
</p>
<p>
    &nbsp;&nbsp;&nbsp;&nbsp;2、系统采用PHP语言开发，使用自主研发的高速多层开发框架及缓存技术；
</p>
<p>
    &nbsp;&nbsp;&nbsp;&nbsp;3、系统默认采用sqlite轻型数据库，放入PHP空间即可直接使用，可选mysql等数据库，满足各类存储需求；
</p>
<p>
    &nbsp;&nbsp;&nbsp;&nbsp;4、系统采用响应式管理后台，满足各类设备随时管理的需要；
</p>
<p>
    &nbsp;&nbsp;&nbsp;&nbsp;5、系统支持后台在线升级，满足系统及时升级更新的需要；
</p>
<p>
    &nbsp;&nbsp;&nbsp;&nbsp;6、系统支持内容模型、多语言、自定义表单、筛选、多条件搜索、小程序、APP等功能；
</p>
<p>
    &nbsp;&nbsp;&nbsp;&nbsp;7、系统支持多种URL模式及模型、栏目、内容自定义地址名称，满足各类网站推广优化的需要。<br/>
</p>
<p>
    <br/>
</p>
<p>
    <strong><span style="font-size: 18px;">源码托管地址：</span></strong>
</p>
<p>
    GitHub：<a href="https://github.com/kovinByte/EkoCMS" target="_blank">https://github.com/kovinByte/EkoCMS</a><br/>
</p>

<p>
    <strong><span style="font-size: 18px;"><br/></span></strong>
</p>
<p>
    <strong><span style="font-size: 18px;">简单到想哭的标签：</span></strong>
</p>
<pre class="brush:html;toolbar:false">1、全局标签示意：
{eko:sitetitle} 站点标题 
{eko:sitelogo} 站点logo
2、列表页标签示意：
{eko:list num=10 order=date}    
    &lt;p&gt;&lt;a href=&quot;[list:link]&quot;&gt;[list:title]&lt;/a&gt;&lt;/p&gt;
{/eko:list}
3、内容页标签示意：
{content:title} 标题
{content:subtitle}副标题
{content:author} 作者
{content:source} 来源
更多简单到想哭的标签请参考开发手册...</pre>','','','','EkoCMS是基于PBootCMS翻版开发且永久开源免费的PHP企业网站开发建设管理系统，是一套高效、简洁、 强悍的可免费商用的PHP CMS源码，能够满足各类企业网站开发建设的需要。系统采用简单到想哭的模板标签，只要懂HTML就可快速开发企业网站。官方提供了大量网站模板免费下载和使用，将致力于为广大开发','255','1','0','0','0','2','0','0','admin','admin','2018-04-12 10:26:20','2026-11-26 10:47:35','4','',''),
('14','cn','8','','济南某某网络科技有限公司','#333333','','','admin','本站','','2018-04-12 10:26:28','/static/upload/image/20180412/1523500443228678.png','','<p>
    &nbsp;&nbsp;&nbsp;&nbsp;<span style="font-size: 18px;">EkoCMS是基于PBootCMS翻版开发且永久开源免费的PHP企业网站开发建设管理系统，是一套高效、简洁、
 强悍的可免费商用的PHP 
CMS源码，能够满足各类企业网站开发建设的需要。系统采用简单到想哭的模板标签，只要懂HTML就可快速开发企业网站。官方提供了大量网站模板免费下载和使用，将致力于为广大开发者和企业提供最佳的网站开发建设解决方案。</span>
</p>
<p>
    &nbsp;&nbsp;&nbsp;&nbsp;1、系统采用高效、简洁、强悍的模板标签，只要懂HTML就可快速开发企业网站；
</p>
<p>
    &nbsp;&nbsp;&nbsp;&nbsp;2、系统采用PHP语言开发，使用自主研发的高速多层开发框架及缓存技术；
</p>
<p>
    &nbsp;&nbsp;&nbsp;&nbsp;3、系统默认采用sqlite轻型数据库，放入PHP空间即可直接使用，可选mysql等数据库，满足各类存储需求；
</p>
<p>
    &nbsp;&nbsp;&nbsp;&nbsp;4、系统采用响应式管理后台，满足各类设备随时管理的需要；
</p>
<p>
    &nbsp;&nbsp;&nbsp;&nbsp;5、系统支持后台在线升级，满足系统及时升级更新的需要；
</p>
<p>
    &nbsp;&nbsp;&nbsp;&nbsp;6、系统支持内容模型、多语言、自定义表单、筛选、多条件搜索、小程序、APP等功能；
</p>
<p>
    &nbsp;&nbsp;&nbsp;&nbsp;7、系统支持多种URL模式及模型、栏目、内容自定义地址名称，满足各类网站推广优化的需要。<br/>
</p>
<p>
    <br/>
</p>
<p>
    <strong><span style="font-size: 18px;">源码托管地址：</span></strong>
</p>
<p>
    GitHub：<a href="https://github.com/kovinByte/EkoCMS" target="_blank">https://github.com/kovinByte/EkoCMS</a><br/>
</p>

<p>
    <strong><span style="font-size: 18px;"><br/></span></strong>
</p>
<p>
    <strong><span style="font-size: 18px;">简单到想哭的标签：</span></strong>
</p>
<pre class="brush:html;toolbar:false">1、全局标签示意：
{eko:sitetitle} 站点标题 
{eko:sitelogo} 站点logo
2、列表页标签示意：
{eko:list num=10 order=date}    
    &lt;p&gt;&lt;a href=&quot;[list:link]&quot;&gt;[list:title]&lt;/a&gt;&lt;/p&gt;
{/eko:list}
3、内容页标签示意：
{content:title} 标题
{content:subtitle}副标题
{content:author} 作者
{content:source} 来源
更多简单到想哭的标签请参考开发手册...</pre>','','','','EkoCMS是基于PBootCMS翻版开发且永久开源免费的PHP企业网站开发建设管理系统，是一套高效、简洁、 强悍的可免费商用的PHP CMS源码，能够满足各类企业网站开发建设的需要。系统采用简单到想哭的模板标签，只要懂HTML就可快速开发企业网站。官方提供了大量网站模板免费下载和使用，将致力于为广大开发','255','1','0','0','0','3','0','0','admin','admin','2018-04-12 10:32:52','2019-08-05 11:21:09','4','',''),
('15','cn','9','','信息审核专员','#333333','','','admin','本站','','2018-04-12 10:34:24','','','<p><strong>岗位职责：</strong></p><p>1、根据业务规范对全平台音视图文内容进行审核、筛选及处理；</p><p>2、对平台内容进行监管处理和备案，维持网络秩序；</p><p>3、为用户提供平台业务咨询服务，保障产品活动顺利进行；</p><p>4、受理客户投诉，在授权范围内予以解决；</p><p>5、参与修订审核标准，优化审核流程与规范。</p><p>&nbsp;</p><p><strong>岗位要求：</strong></p><p>1、大专以上学历，专业不限，有视频网站内容审核经验者优先；</p><p>2、熟悉互联网信息安全，有敏感的风险意识，针对突发热点话题具备一定的判断处理能力；</p><p>3、耐心、细致、踏实、严谨，具备高度的责任心和团队合作精神；</p><p>4、有一定沟通协调能力及组织领导力，能够承担一定的压力与挑战。</p><p>说明：上班时间遵从部门内部排班安排，能适应夜班。</p><p>岗位升值空间：组长、主管、平台运营专员、网络推广、音乐编辑…</p><p><br/></p><p><strong>工作地址：</strong>
 &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;</p><h2>北京市朝阳区</h2><p><br/></p>','','','','','255','1','0','0','0','4','0','0','admin','admin','2018-04-12 10:37:25','2018-04-12 10:37:25','4','',''),
('16','cn','9','','平台运营','#333333','','','admin','本站','','2018-04-12 10:37:31','','','<p><strong><span style=";font-family:宋体">岗位职责： </span></strong></p><p>1、 负责平台运营的业务支撑工作，保证平台业务稳定发展；</p><p>2、 参与和优化部门业务操作流程，保证团队协同工作；</p><p>3、 为用户提供平台业务咨询服务；</p><p>4、 受理客户投诉，在授权范围内予以解决；</p><p>5、 网络活动视频录像与剪辑，挖掘优秀作品,后台信息简单编辑处理；</p><p>6、 与公司其他部门配合工作。</p><p><br/></p><p><strong><span style=";font-family:宋体">任职要求： </span></strong></p><p>1、 专科及以上学历，热爱互联网行业；</p><p>2、 较强的工作责任心，踏实勤恳，积极向上，性格开朗；</p><p>3、 形象佳，口齿伶俐，普通话标准；</p><p>4、 熟练使用电脑，经常上网，会使用office等相关办公软件；</p><p>5、 能适应白班、夜班倒班工作制；</p><p>注：根据个人能力和特长，公司给予更多的发展及晋升空间。</p><p><br/></p><p><strong>工作地址：</strong>
 &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;</p><h2>北京市朝阳区北苑路</h2><p><br/></p>','','','','','255','1','0','0','0','3','0','0','admin','admin','2018-04-12 10:37:57','2018-04-12 10:37:57','4','',''),
('17','cn','9','','高级Linux运维工程师','#333333','','','admin','本站','','2018-04-12 10:38:09','','','<p style="line-height: 150%"><strong><span style="font-size:16px;line-height: 150%;font-family:宋体">岗位职责：</span></strong></p><p>1、负责公司服务器基础环境的部署、配置、日常巡检、维护、故障的应急响应和问题处理；</p><p>2、负责公司kvm虚拟化平台的管理工作，基础环境部署，性能容量管理，漏洞扫描、安全加固，保证其稳定、高效运行；</p><p>3、负责维护公司集中监控系统，根据业务需求调整监控策略、告警阀值，处理告警信息和问题跟踪；</p><p>4、编写系统维护文档，完善并更新运维流程文档；</p><p style="line-height:150%"><span style="font-size: 16px;line-height:150%">&nbsp;</span></p><p style="line-height:150%"><strong><span style="font-size:16px;line-height:150%;font-family:宋体">任职要求：</span></strong></p><p>1、计算机等相关专业，本科以上学历，2年以上linux系统管理工作经验，经验丰富可适当放宽学历条件；</p><p>2、熟悉基础网络知识，熟悉TCP/IP协议工作原理，有大流量网站服务器管理经验者优先，熟悉自动化运维工具（三选一puppet/saltstack/ansible）优先；</p><p>3、熟悉linux系统高可用技术和负载均衡技术，熟悉WEB相关技术，包括Apache/Nginx/tomcat/squid 等应用程序的安装、配置和维护；</p><p>4、熟悉服务器硬件，具备排错及故障定位、处理的能力；熟练使用各种工具进行系统状态监控（cacti、Nagios、ganglia等），有虚拟化平台相关经验者优先（vmware/kvm/docker）；</p><p>5、有良好的沟通能力和团队合作精神，有强烈的事业心和责任感，工作细心，热爱学习和分享，具有RHCE、RHCA认证者优先；</p><p>6、熟练撑握shell/python/perl等1至2种语言。</p><p><br/></p><p><strong>工作地址：</strong>
 &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;</p><h2>北京市朝阳区</h2><p><br/></p>','','','','','255','1','0','0','0','5','0','0','admin','admin','2018-04-12 10:39:40','2018-04-12 10:39:40','4','',''),
('22226','cn','8','','山东某机械制造企业官网建设项目','#333333','响应式企业官网，产品展示与在线询价一体化','','admin','本站','','2026-11-25 09:20:00','','','<p style="text-indent:2em;"><strong>客户背景：</strong>客户是山东一家专注于工程机械配件研发与生产的制造企业，原有官网建设时间较早，页面在手机上浏览体验较差，产品资料更新也不方便。</p>
<p style="text-indent:2em;"><strong>项目需求：</strong>重新梳理栏目结构，突出产品分类与技术优势；适配手机、平板与电脑访问；支持业务人员在后台自助发布产品与新闻；增加在线留言与询价入口。</p>
<p style="text-indent:2em;"><strong>解决方案：</strong>基于 EkoCMS 搭建响应式官网，规划首页、产品中心、服务案例、新闻中心、关于我们与联系我们等栏目。产品中心按系列分类展示，参数与图片由后台统一维护；新闻与案例模块用于持续沉淀内容；全站接入在线留言表单，方便客户提交询价信息。</p>
<p style="text-indent:2em;"><strong>项目成果：</strong>官网在约定周期内上线，移动端访问体验明显改善，产品资料维护从依赖技术人员转为业务人员自助完成，上线后询价留言量稳步提升。</p>','企业官网,响应式建站,服务案例','','企业官网建设案例,机械制造官网,响应式网站,EkoCMS','山东某机械制造企业官网建设案例：基于 EkoCMS 搭建响应式官网，实现产品展示、新闻发布与在线询价一体化。','255','1','0','0','0','12','0','0','admin','admin','2026-11-25 09:20:00','2026-11-25 09:20:00','4','',''),
('22227','cn','8','','济南某连锁餐饮品牌官网改版项目','#333333','品牌形象展示与门店导览同步升级','','admin','本站','','2026-11-19 15:40:00','','','<p style="text-indent:2em;"><strong>客户背景：</strong>客户是济南本地一家连锁餐饮品牌，门店数量持续增加，原官网信息更新滞后，品牌形象与线下门店的视觉风格不统一。</p>
<p style="text-indent:2em;"><strong>项目需求：</strong>统一品牌视觉，突出招牌菜品与门店环境；提供门店列表与地址导航；支持总部统一发布活动资讯；页面需适配移动端，方便顾客到店前浏览。</p>
<p style="text-indent:2em;"><strong>解决方案：</strong>使用 EkoCMS 重构整站模板，以图片与留白为主的版式呈现品牌调性。门店信息通过独立栏目维护，支持持续新增门店；活动资讯由运营人员在后台发布；并在页脚与悬浮区域增加电话咨询和在线留言入口。</p>
<p style="text-indent:2em;"><strong>项目成果：</strong>改版后品牌形象更加统一，门店信息维护效率明显提高，活动内容可以快速上线，移动端访问占比与停留时长均有提升。</p>','品牌官网,餐饮官网,服务案例','','餐饮品牌官网案例,连锁门店官网,移动端适配,EkoCMS','济南某连锁餐饮品牌官网改版案例：通过 EkoCMS 统一品牌形象，完善门店导览与活动资讯维护能力。','255','1','0','0','0','8','0','0','admin','admin','2026-11-19 15:40:00','2026-11-19 15:40:00','4','','');

-- --------------------------------------------------------

--
-- 表的结构 `ek_content_ext`
--

DROP TABLE IF EXISTS `ek_content_ext`;
CREATE TABLE `ek_content_ext` (
  `extid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `contentid` int(10) unsigned NOT NULL,
  `ext_price` varchar(100) DEFAULT NULL COMMENT '产品价格',
  `ext_type` varchar(100) DEFAULT NULL COMMENT '类型',
  `ext_color` varchar(100) DEFAULT NULL COMMENT '颜色',
    PRIMARY KEY (`extid`),
    KEY `ek_content_ext_contentid` (`contentid`),
    KEY ek_content_ext_color_index (`ext_color`),
    KEY ek_content_ext_price_index (`ext_price`),
    KEY ek_content_ext_type_index (`ext_type`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=utf8;

--
-- 转存表中的数据 `ek_content_ext`
--

INSERT INTO `ek_content_ext` (`extid`,`contentid`,`ext_price`,`ext_type`,`ext_color`) VALUES
('1','9','80','专业版','红色,黄色'),
('2','10','999','基础版','黄色,绿色'),
('3','11','1999','旗舰版','蓝色,紫色'),
('4','12','2999','专业版','黄色,绿色'),
('5','13','150','基础版','红色,橙色');

-- --------------------------------------------------------

--
-- 表的结构 `ek_content_sort`
--

DROP TABLE IF EXISTS `ek_content_sort`;
CREATE TABLE `ek_content_sort` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '编号',
  `acode` varchar(20) NOT NULL COMMENT '区域编码',
  `mcode` varchar(20) NOT NULL COMMENT '内容模型编码',
  `pcode` varchar(20) NOT NULL COMMENT '父编码',
  `scode` varchar(20) NOT NULL COMMENT '分类编码',
  `name` varchar(100) NOT NULL COMMENT '分类名称',
  `listtpl` varchar(255) NOT NULL COMMENT '列表页模板',
  `contenttpl` varchar(255) NOT NULL COMMENT '内容页模板',
  `status` char(1) NOT NULL DEFAULT '1' COMMENT '状态',
  `outlink` varchar(100) NOT NULL COMMENT '转外链接',
  `subname` varchar(200) NOT NULL COMMENT '附加名称',
  `def1` varchar(1000) NOT NULL COMMENT '栏目描述1',
  `def2` varchar(1000) NOT NULL COMMENT '栏目描述2',
  `def3` varchar(1000) NOT NULL COMMENT '栏目描述3',
  `ico` varchar(100) NOT NULL COMMENT '分类缩略图',
  `pic` varchar(100) NOT NULL COMMENT '分类大图',
  `title` varchar(100) NOT NULL COMMENT 'seo标题',
  `keywords` varchar(200) NOT NULL COMMENT '分类关键字',
  `description` varchar(500) NOT NULL COMMENT '分类描述',
  `filename` varchar(30) NOT NULL COMMENT '自定义文件名',
  `sorting` int(10) unsigned NOT NULL DEFAULT '255' COMMENT '排序',
  `create_user` varchar(30) NOT NULL COMMENT '创建人员',
  `update_user` varchar(30) NOT NULL COMMENT '更新人员',
  `create_time` datetime NOT NULL COMMENT '创建时间',
  `update_time` datetime NOT NULL COMMENT '更新时间',
  `gtype` char(1) NOT NULL DEFAULT '4',
  `gid` varchar(20) NOT NULL DEFAULT '',
  `gnote` varchar(100) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `ek_content_sort_scode` (`scode`),
  KEY `ek_content_sort_pcode` (`pcode`),
  KEY `ek_content_sort_acode` (`acode`),
  KEY `ek_content_sort_mcode` (`mcode`),
  KEY `ek_content_sort_filename` (`filename`),
  KEY `ek_content_sort_sorting` (`sorting`)
) ENGINE=MyISAM AUTO_INCREMENT=12 DEFAULT CHARSET=utf8;

--
-- 转存表中的数据 `ek_content_sort`
--

INSERT INTO `ek_content_sort` (`id`,`acode`,`mcode`,`pcode`,`scode`,`name`,`listtpl`,`contenttpl`,`status`,`outlink`,`subname`,`ico`,`pic`,`title`,`keywords`,`description`,`filename`,`sorting`,`create_user`,`update_user`,`create_time`,`update_time`,`gtype`,`gid`,`gnote`) VALUES
('1','cn','1','0','1','公司简介','','about.html','1','','网站建设「一站式」服务商','','','','','','aboutus','255','admin','admin','2018-04-11 17:26:11','2018-04-11 17:26:11','4','',''),
('2','cn','2','0','2','新闻中心','newslist.html','news.html','1','','了解最新公司动态及行业资讯','','','','','','article','255','admin','admin','2018-04-11 17:26:46','2018-04-11 17:26:46','4','',''),
('3','cn','2','2','3','公司动态','newslist.html','news.html','1','','了解最新公司动态及行业资讯','','','','','','company','255','admin','admin','2018-04-11 17:27:05','2018-04-11 17:27:05','4','',''),
('4','cn','2','2','4','行业动态','newslist.html','news.html','1','','了解最新公司动态及行业资讯','','','','','','industry','255','admin','admin','2018-04-11 17:27:30','2018-04-11 17:27:30','4','',''),
('5','cn','3','0','5','产品中心','productlist.html','product.html','1','','服务创造价值、存在造就未来','','','','','','product','255','admin','admin','2018-04-11 17:27:54','2018-04-11 17:27:54','4','',''),
('6','cn','3','5','6','网站建设','productlist.html','product.html','1','','服务创造价值、存在造就未来','','','','','','website','255','admin','admin','2018-04-11 17:28:19','2018-04-11 17:28:19','4','',''),
('7','cn','3','5','7','域名空间','productlist.html','product.html','1','','服务创造价值、存在造就未来','','','','','','domain','255','admin','admin','2018-04-11 17:28:38','2018-04-11 17:28:38','4','',''),
('8','cn','4','0','8','服务案例','caselist.html','case.html','1','','服务创造价值、存在造就未来','','','','','','case','255','admin','admin','2018-04-11 17:29:16','2018-04-11 17:29:16','4','',''),
('9','cn','5','0','9','招贤纳士','joblist.html','job.html','1','','诚聘优秀人士加入我们的团队','','','','','','job','255','admin','admin','2018-04-11 17:30:02','2018-04-11 17:30:02','4','',''),
('10','cn','1','0','10','在线留言','','message.html','1','','有什么问题欢迎您随时反馈','','','','','','gbook','255','admin','admin','2018-04-11 17:30:36','2018-04-12 10:55:31','4','',''),
('11','cn','1','0','11','联系我们','','about.html','1','','能为您服务是我们的荣幸','','','','','','contact','255','admin','admin','2018-04-11 17:31:29','2018-04-11 17:31:29','4','','');

-- --------------------------------------------------------

--
-- 表的结构 `ek_diy_telephone`
--

DROP TABLE IF EXISTS `ek_diy_telephone`;
CREATE TABLE `ek_diy_telephone` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `create_time` datetime NOT NULL,
  `tel` varchar(20) DEFAULT NULL COMMENT '电话号码',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `ek_extfield`
--

DROP TABLE IF EXISTS `ek_extfield`;
CREATE TABLE `ek_extfield` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '编号',
  `mcode` varchar(20) NOT NULL COMMENT '模型编码',
  `name` varchar(30) NOT NULL COMMENT '字段名称',
  `type` char(2) NOT NULL COMMENT '字段类型',
  `value` varchar(500) NOT NULL COMMENT '单选或多选值',
  `description` varchar(30) NOT NULL COMMENT '描述文本',
  `sorting` int(11) NOT NULL COMMENT '排序',
  PRIMARY KEY (`id`),
  KEY `ek_extfield_mcode` (`mcode`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;

--
-- 转存表中的数据 `ek_extfield`
--

INSERT INTO `ek_extfield` (`id`,`mcode`,`name`,`type`,`value`,`description`,`sorting`) VALUES
('1','3','ext_price','1','','产品价格','255'),
('2','3','ext_type','4','基础版,专业版,旗舰版','类型','255'),
('3','3','ext_color','4','红色,橙色,黄色,绿色,蓝色,紫色','颜色','255');

-- --------------------------------------------------------

--
-- 表的结构 `ek_form`
--

DROP TABLE IF EXISTS `ek_form`;
CREATE TABLE `ek_form` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '编号',
  `fcode` varchar(20) NOT NULL COMMENT '表单编码',
  `form_name` varchar(30) NOT NULL COMMENT '表单名称',
  `table_name` varchar(30) NOT NULL COMMENT '表名称',
  `create_user` varchar(30) NOT NULL COMMENT '添加人员',
  `update_user` varchar(30) NOT NULL COMMENT '更新人员',
  `create_time` datetime NOT NULL COMMENT '添加时间',
  `update_time` datetime NOT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `ek_form_fcode` (`fcode`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;

--
-- 转存表中的数据 `ek_form`
--

INSERT INTO `ek_form` (`id`,`fcode`,`form_name`,`table_name`,`create_user`,`update_user`,`create_time`,`update_time`) VALUES
('1','1','在线留言','ek_message','admin','admin','2018-04-11 17:31:29','2018-04-11 17:31:29'),
('2','2','搜集电话','ek_diy_telephone','admin','admin','2018-11-30 15:17:40','2018-11-30 15:17:40');

-- --------------------------------------------------------

--
-- 表的结构 `ek_form_field`
--

DROP TABLE IF EXISTS `ek_form_field`;
CREATE TABLE `ek_form_field` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '编号',
  `fcode` varchar(20) NOT NULL COMMENT '表单编码',
  `name` varchar(30) NOT NULL COMMENT '字段名称',
  `length` int(10) unsigned NOT NULL COMMENT '字段长度',
  `required` char(1) NOT NULL DEFAULT '0' COMMENT '是否必填',
  `description` varchar(30) NOT NULL COMMENT '描述文本',
  `sorting` int(10) unsigned NOT NULL DEFAULT '255' COMMENT '排序',
  `create_user` varchar(30) NOT NULL COMMENT '添加人员',
  `update_user` varchar(30) NOT NULL COMMENT '更新人员',
  `create_time` datetime NOT NULL COMMENT '添加时间',
  `update_time` datetime NOT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `ek_form_field_fcode` (`fcode`),
  KEY `ek_form_field_sorting` (`sorting`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;

--
-- 转存表中的数据 `ek_form_field`
--

INSERT INTO `ek_form_field` (`id`,`fcode`,`name`,`length`,`required`,`description`,`sorting`,`create_user`,`update_user`,`create_time`,`update_time`) VALUES
('1','1','contacts','10','1','联系人','255','admin','admin','2018-07-14 18:24:02','2018-07-15 17:47:43'),
('2','1','mobile','12','1','手机','255','admin','admin','2018-07-14 18:24:02','2018-07-15 17:47:44'),
('3','1','content','500','1','内容','255','admin','admin','2018-07-14 18:24:02','2018-07-15 17:47:45'),
('4','2','tel','20','1','电话号码','255','admin','admin','2018-11-30 15:18:00','2018-11-30 15:18:00');

-- --------------------------------------------------------

--
-- 表的结构 `ek_label`
--

DROP TABLE IF EXISTS `ek_label`;
CREATE TABLE `ek_label` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '编号',
  `name` varchar(100) NOT NULL COMMENT '名称',
  `value` varchar(500) NOT NULL COMMENT '值',
  `type` char(1) NOT NULL DEFAULT '1' COMMENT '字段类型',
  `description` varchar(30) NOT NULL COMMENT '描述',
  `create_user` varchar(30) NOT NULL COMMENT '创建人员',
  `update_user` varchar(20) NOT NULL COMMENT '更新人员',
  `create_time` datetime NOT NULL COMMENT '创建时间',
  `update_time` datetime NOT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

--
-- 转存表中的数据 `ek_label`
--

INSERT INTO `ek_label` (`id`,`name`,`value`,`type`,`description`,`create_user`,`update_user`,`create_time`,`update_time`) VALUES
('1','downlink','https://github.com/kovinByte/EkoCMS','1','下载地址','admin','admin','2018-04-11 16:52:19','2018-04-30 15:05:00');

-- --------------------------------------------------------

--
-- 表的结构 `ek_link`
--

DROP TABLE IF EXISTS `ek_link`;
CREATE TABLE `ek_link` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '序号',
  `acode` varchar(20) NOT NULL COMMENT '区域编码',
  `gid` int(10) unsigned NOT NULL COMMENT '分组序号',
  `name` varchar(50) NOT NULL COMMENT '链接名称',
  `link` varchar(100) NOT NULL COMMENT '跳转链接',
  `logo` varchar(100) NOT NULL COMMENT '图片地址',
  `sorting` int(11) NOT NULL COMMENT '排序',
  `create_user` varchar(30) NOT NULL COMMENT '创建人员',
  `update_user` varchar(30) NOT NULL COMMENT '更新人员',
  `create_time` datetime NOT NULL COMMENT '创建时间',
  `update_time` datetime NOT NULL COMMENT '修改时间',
  PRIMARY KEY (`id`),
  KEY `ek_link_acode` (`acode`),
  KEY `ek_link_gid` (`gid`),
  KEY `ek_link_sorting` (`sorting`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

--
-- 转存表中的数据 `ek_link`
--

INSERT INTO `ek_link` (`id`,`acode`,`gid`,`name`,`link`,`logo`,`sorting`,`create_user`,`update_user`,`create_time`,`update_time`) VALUES
('1','cn','1','EkoCMS','https://ekocms.d50.cn','','255','admin','admin','2018-04-12 10:53:06','2018-04-12 10:53:26');

-- --------------------------------------------------------

--
-- 表的结构 `ek_member`
--

DROP TABLE IF EXISTS `ek_member`;
CREATE TABLE `ek_member` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ucode` varchar(20) NOT NULL,
  `username` varchar(100) NOT NULL,
  `useremail` varchar(50) NOT NULL DEFAULT '',
  `usermobile` varchar(11) NOT NULL DEFAULT '',
  `nickname` varchar(100) NOT NULL,
  `password` varchar(32) NOT NULL,
  `headpic` varchar(200) NOT NULL,
  `status` char(1) NOT NULL,
  `activation` char(1) NOT NULL DEFAULT '1',
  `gid` varchar(20) NOT NULL,
  `wxid` varchar(50) NOT NULL,
  `qqid` varchar(50) NOT NULL,
  `wbid` varchar(50) NOT NULL,
  `score` int(10) unsigned NOT NULL DEFAULT '0',
  `register_time` datetime NOT NULL,
  `login_count` int(10) unsigned NOT NULL DEFAULT '0',
  `last_login_ip` varchar(11) NOT NULL,
  `last_login_time` varchar(11) NOT NULL,
  `sex` varchar(2) NOT NULL DEFAULT '',
  `birthday` varchar(20) NOT NULL DEFAULT '',
  `qq` varchar(15) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `ek_member_ucode` (`ucode`),
  UNIQUE KEY `ek_member_username` (`username`),
  KEY `ek_member_gid` (`gid`),
  KEY `ek_member_wxid` (`wxid`),
  KEY `ek_member_qqid` (`qqid`),
  KEY `ek_member_wbid` (`wbid`),
  KEY `ek_member_useremail` (`useremail`),
  KEY `ek_member_usermobile` (`usermobile`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `ek_member_comment`
--

DROP TABLE IF EXISTS `ek_member_comment`;
CREATE TABLE `ek_member_comment` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pid` int(10) unsigned NOT NULL DEFAULT '0',
  `contentid` int(10) unsigned NOT NULL,
  `comment` varchar(1000) NOT NULL,
  `uid` int(10) unsigned NOT NULL,
  `puid` int(10) unsigned NOT NULL,
  `likes` int(10) unsigned NOT NULL DEFAULT '0',
  `oppose` int(10) unsigned NOT NULL DEFAULT '0',
  `status` char(1) NOT NULL,
  `user_ip` varchar(11) NOT NULL,
  `user_os` varchar(30) NOT NULL,
  `user_bs` varchar(30) NOT NULL,
  `create_time` datetime NOT NULL,
  `update_user` varchar(30) NOT NULL,
  `update_time` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ek_member_comment_pid` (`pid`),
  KEY `ek_member_comment_contentid` (`contentid`),
  KEY `ek_member_comment_uid` (`uid`),
  KEY `ek_member_comment_puid` (`puid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `ek_member_field`
--

DROP TABLE IF EXISTS `ek_member_field`;
CREATE TABLE `ek_member_field` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(30) NOT NULL,
  `length` int(10) unsigned NOT NULL,
  `required` char(1) NOT NULL,
  `description` varchar(30) NOT NULL,
  `sorting` int(10) unsigned NOT NULL,
  `status` char(1) NOT NULL,
  `create_user` varchar(30) NOT NULL,
  `update_user` varchar(30) NOT NULL,
  `create_time` datetime NOT NULL,
  `update_time` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;

--
-- 转存表中的数据 `ek_member_field`
--

INSERT INTO `ek_member_field` (`id`,`name`,`length`,`required`,`description`,`sorting`,`status`,`create_user`,`update_user`,`create_time`,`update_time`) VALUES
('1','sex','2','0','性别','255','1','admin','admin','2020-06-25 00:00:00','2020-06-25 00:00:00'),
('2','birthday','20','0','生日','255','1','admin','admin','2020-06-25 00:00:00','2020-06-25 00:00:00'),
('3','qq','15','0','QQ','255','1','admin','admin','2020-06-25 00:00:00','2020-06-25 00:00:00');

-- --------------------------------------------------------

--
-- 表的结构 `ek_member_group`
--

DROP TABLE IF EXISTS `ek_member_group`;
CREATE TABLE `ek_member_group` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `gcode` varchar(20) NOT NULL,
  `gname` varchar(100) NOT NULL,
  `description` varchar(200) NOT NULL,
  `status` varchar(1) NOT NULL,
  `lscore` int(10) unsigned NOT NULL DEFAULT '0',
  `uscore` int(10) unsigned NOT NULL DEFAULT '0',
  `create_user` varchar(30) NOT NULL,
  `update_user` varchar(30) NOT NULL,
  `create_time` datetime NOT NULL,
  `update_time` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ek_member_group_gcode` (`gcode`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;

--
-- 转存表中的数据 `ek_member_group`
--

INSERT INTO `ek_member_group` (`id`,`gcode`,`gname`,`description`,`status`,`lscore`,`uscore`,`create_user`,`update_user`,`create_time`,`update_time`) VALUES
('1','1','初级会员','初级会员具备基本的权限','1','0','999','admin','admin','2020-06-25 00:00:00','2020-06-25 00:00:00'),
('2','2','中级会员','中级会员具备部分特殊权限','1','1000','9999','admin','admin','2020-06-25 00:00:00','2020-06-25 00:00:00'),
('3','3','高级会员','高级会员具备全部特殊权限','1','10000','4294967295','admin','admin','2020-06-25 00:00:00','2020-06-25 00:00:00');

-- --------------------------------------------------------

--
-- 表的结构 `ek_menu`
--

DROP TABLE IF EXISTS `ek_menu`;
CREATE TABLE `ek_menu` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '菜单编号',
  `mcode` varchar(20) NOT NULL COMMENT '菜单编码',
  `pcode` varchar(20) NOT NULL COMMENT '上级菜单',
  `name` varchar(50) NOT NULL COMMENT '菜单名称',
  `url` varchar(100) NOT NULL COMMENT '菜单地址',
  `sorting` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '菜单排序',
  `status` char(1) NOT NULL DEFAULT '1' COMMENT '是否启用',
  `shortcut` char(1) NOT NULL DEFAULT '0' COMMENT '桌面图标',
  `ico` varchar(30) NOT NULL COMMENT '菜单图标',
  `create_user` varchar(30) NOT NULL COMMENT '创建人员',
  `update_user` varchar(30) NOT NULL COMMENT '更新人员',
  `create_time` datetime NOT NULL COMMENT '创建时间',
  `update_time` datetime NOT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `ek_menu_mcode` (`mcode`),
  KEY `ek_menu_pcode` (`pcode`),
  KEY `ek_menu_sorting` (`sorting`)
) ENGINE=MyISAM AUTO_INCREMENT=71 DEFAULT CHARSET=utf8;

--
-- 转存表中的数据 `ek_menu`
--

INSERT INTO `ek_menu` (`id`,`mcode`,`pcode`,`name`,`url`,`sorting`,`status`,`shortcut`,`ico`,`create_user`,`update_user`,`create_time`,`update_time`) VALUES
('1','M101','0','系统管理','/admin/M101/index','900','1','0','fa-cog','admin','admin','0000-00-00 00:00:00','2018-04-30 14:52:57'),
('2','M102','M101','数据区域','/admin/Area/index','901','1','1','fa-sitemap','admin','admin','0000-00-00 00:00:00','2018-04-30 14:54:23'),
('3','M103','M101','系统菜单','/admin/Menu/index','902','0','0','fa-bars','admin','admin','0000-00-00 00:00:00','2018-04-30 14:54:35'),
('4','M104','M101','系统角色','/admin/Role/index','903','1','1','fa-hand-stop-o','admin','admin','0000-00-00 00:00:00','2018-04-30 14:54:43'),
('5','M105','M101','系统用户','/admin/User/index','904','1','1','fa-users','admin','admin','0000-00-00 00:00:00','2018-04-30 14:54:51'),
('6','M106','M101','系统日志','/admin/Syslog/index','905','1','1','fa-history','admin','admin','0000-00-00 00:00:00','2018-04-30 14:55:00'),
('7','M107','M101','类型管理','/admin/Type/index','906','0','0','fa-tags','admin','admin','0000-00-00 00:00:00','2018-04-30 14:55:13'),
('8','M108','M101','数据库管理','/admin/Database/index','907','1','1','fa-database','admin','admin','0000-00-00 00:00:00','2018-04-30 14:55:24'),
('9','M109','M101','服务器信息','/admin/Site/server','908','1','1','fa-info-circle','admin','admin','0000-00-00 00:00:00','2018-04-30 14:55:34'),
('10','M1101', 'M101', '图片清理', '/admin/ImageExt/index', '909', '1', '1', 'fa-trash', 'admin', 'admin', '0000-00-00 00:00:00', '2022-09-19 13:44:59'),
('11','M110','0','基础内容','/admin/M110/index','300','1','0','fa-sliders','admin','admin','2017-11-28 11:13:05','2018-04-30 14:48:29'),
('12','M111','M110','站点信息','/admin/Site/index','301','1','1','fa-cog','admin','admin','0000-00-00 00:00:00','2018-04-07 18:45:57'),
('13','M112','M110','公司信息','/admin/Company/index','302','1','1','fa-copyright','admin','admin','0000-00-00 00:00:00','2018-04-07 18:46:09'),
('29','M129','M110','内容栏目','/admin/ContentSort/index','303','1','1','fa-bars','admin','admin','2017-12-26 10:42:40','2018-04-07 18:46:25'),
('30','M130','0','文章内容','/admin/M130/index','400','1','0','fa-file-text-o','admin','admin','2017-12-26 10:45:36','2018-04-30 14:49:47'),
('31','M131','M130','单页内容','/admin/Single/index','401','0','0','fa-file-o','admin','admin','2017-12-26 10:46:35','2018-04-07 18:46:35'),
('32','M132','M130','列表内容','/admin/Content/index','402','0','0','fa-file-text-o','admin','admin','2017-12-26 10:48:17','2018-04-07 21:52:15'),
('36','M136','M156','标签定制','/admin/Label/index','203','1','1','fa-wrench','admin','admin','2018-01-03 11:52:40','2018-04-07 18:44:31'),
('50','M150','M157','留言信息','/admin/Message/index','501','1','1','fa-question-circle-o','admin','admin','2018-02-01 13:20:17','2018-07-07 23:45:09'),
('51','M151','M157','轮播图片','/admin/Slide/index','502','1','1','fa-picture-o','admin','admin','2018-03-01 14:57:41','2018-04-07 18:47:07'),
('52','M152','M157','友情链接','/admin/Link/index','503','1','1','fa-link','admin','admin','2018-03-01 14:58:45','2018-04-07 18:47:16'),
('53','M153','M156','配置参数','/admin/Config/index','201','1','1','fa-sliders','admin','admin','2018-03-21 14:52:05','2018-04-07 18:44:02'),
('61','M1000','M157','文章内链','/admin/Tags/index','505','1','0','fa-random','admin','admin','2019-07-12 08:25:41','2019-07-12 08:26:23'),
('55','M155','M156','模型管理','/admin/Model/index','204','1','1','fa-codepen','admin','admin','2018-03-25 17:16:06','2018-04-07 18:44:40'),
('56','M156','0','全局配置','/admin/M156/index','200','1','0','fa-globe','admin','admin','2018-03-25 17:20:43','2018-04-30 14:43:56'),
('58','M158','M156','模型字段','/admin/ExtField/index','205','1','1','fa-external-link','admin','admin','2018-03-25 21:24:43','2018-04-07 18:44:49'),
('57','M157','0','扩展内容','/admin/M157/index','500','1','0','fa-arrows-alt','admin','admin','2018-03-25 17:27:57','2018-04-30 14:50:34'),
('60','M160','M157','自定义表单','/admin/Form/index','504','1','1','fa-plus-square-o','admin','admin','2018-05-30 18:25:41','2018-05-31 23:55:10'),
('62','M1001','0','会员中心','/admin/M1001/index','600','1','0','fa-user-o','admin','admin','2019-10-04 08:25:41','2019-10-04 08:26:23'),
('63','M1002','M1001','会员等级','/admin/MemberGroup/index','601','1','0','fa-signal','admin','admin','2019-10-04 08:25:41','2019-10-04 08:26:23'),
('64','M1003','M1001','会员字段','/admin/MemberField/index','602','1','0','fa-wpforms','admin','admin','2019-10-04 08:25:41','2019-10-04 08:26:23'),
('65','M1004','M1001','会员管理','/admin/Member/index','603','1','0','fa-users','admin','admin','2019-10-04 08:25:41','2019-10-04 08:26:23'),
('66','M1005','M1001','文章评论','/admin/MemberComment/index','604','1','0','fa-commenting-o','admin','admin','2019-10-04 08:25:41','2019-10-04 08:26:23'),
('68','M159','M156','SEO配置','/admin/Config/seo','202','1','0','fa-search','admin','admin','2026-11-24 00:00:00','2026-11-24 00:00:00'),
('69','M161','M157','蜘蛛统计','/admin/Spider/index','506','1','0','fa-bug','admin','admin','2026-11-24 00:00:00','2026-11-24 00:00:00'),
('70','M162','M157','定时发布','/admin/AutoPublish/index','507','1','0','fa-clock-o','admin','admin','2026-11-24 00:00:00','2026-11-24 00:00:00');

-- --------------------------------------------------------

--
-- 表的结构 `ek_menu_action`
--

DROP TABLE IF EXISTS `ek_menu_action`;
CREATE TABLE `ek_menu_action` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `mcode` varchar(20) NOT NULL COMMENT '菜单编码',
  `action` varchar(20) NOT NULL COMMENT '类型编码',
  PRIMARY KEY (`id`),
  KEY `ek_menu_action_mcode` (`mcode`)
) ENGINE=MyISAM AUTO_INCREMENT=80 DEFAULT CHARSET=utf8;

--
-- 转存表中的数据 `ek_menu_action`
--

INSERT INTO `ek_menu_action` (`id`,`mcode`,`action`) VALUES
('1','M102','mod'),
('2','M102','del'),
('3','M102','add'),
('4','M103','mod'),
('5','M103','del'),
('6','M103','add'),
('7','M104','mod'),
('8','M104','del'),
('9','M104','add'),
('10','M105','mod'),
('11','M105','del'),
('12','M105','add'),
('13','M107','mod'),
('14','M107','del'),
('15','M107','add'),
('16','M111','mod'),
('17','M112','mod'),
('18','M114','mod'),
('19','M114','del'),
('20','M114','add'),
('21','M120','mod'),
('22','M120','del'),
('23','M120','add'),
('24','M129','mod'),
('25','M129','del'),
('26','M129','add'),
('27','M131','mod'),
('28','M132','mod'),
('29','M132','del'),
('30','M132','add'),
('31','M136','mod'),
('32','M136','del'),
('33','M136','add'),
('34','M141','mod'),
('35','M141','del'),
('36','M141','add'),
('37','M142','mod'),
('38','M142','del'),
('39','M142','add'),
('40','M143','mod'),
('41','M143','del'),
('42','M143','add'),
('43','M144','mod'),
('44','M144','del'),
('45','M144','add'),
('46','M145','mod'),
('47','M145','del'),
('48','M145','add'),
('49','M150','del'),
('50','M150','mod'),
('51','M151','mod'),
('52','M151','del'),
('53','M151','add'),
('54','M152','mod'),
('55','M152','del'),
('56','M152','add'),
('57','M155','mod'),
('58','M155','del'),
('59','M155','add'),
('60','M158','mod'),
('61','M158','del'),
('62','M158','add'),
('63','M160','add'),
('64','M160','del'),
('65','M160','mod'),
('66','M1000','add'),
('67','M1000','del'),
('68','M1000','mod'),
('69','M1002','add'),
('70','M1002','del'),
('71','M1002','mod'),
('72','M1003','add'),
('73','M1003','del'),
('74','M1003','mod'),
('75','M1004','add'),
('76','M1004','del'),
('77','M1004','mod'),
('78','M1005','del'),
('79','M162','mod');

-- --------------------------------------------------------

--
-- 表的结构 `ek_message`
--

DROP TABLE IF EXISTS `ek_message`;
CREATE TABLE `ek_message` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '编号',
  `acode` varchar(20) NOT NULL COMMENT '区域编码',
  `contacts` varchar(10) DEFAULT NULL COMMENT '联系人',
  `mobile` varchar(12) DEFAULT NULL COMMENT '联系电话',
  `content` varchar(500) DEFAULT NULL COMMENT '留言内容',
  `user_ip` varchar(11) NOT NULL DEFAULT '0' COMMENT 'IP地址',
  `user_os` varchar(30) NOT NULL COMMENT '操作系统',
  `user_bs` varchar(30) NOT NULL COMMENT '浏览器',
  `recontent` varchar(500) NOT NULL COMMENT '回复内容',
  `status` char(1) NOT NULL DEFAULT '1' COMMENT '是否前台显示',
  `create_user` varchar(30) NOT NULL COMMENT '创建人员',
  `update_user` varchar(30) NOT NULL COMMENT '更新人员',
  `create_time` datetime NOT NULL COMMENT '创建时间',
  `update_time` datetime NOT NULL COMMENT '更新时间',
  `uid` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `ek_message_acode` (`acode`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

--
-- 转存表中的数据 `ek_message`
--

INSERT INTO `ek_message` (`id`,`acode`,`contacts`,`mobile`,`content`,`user_ip`,`user_os`,`user_bs`,`recontent`,`status`,`create_user`,`update_user`,`create_time`,`update_time`,`uid`) VALUES
('1','cn','EkoCMS','16888888888','EkoCMS真心很不错哦！','2130706433','Windows 10','Firefox','谢谢您对我们的大力支持与肯定！','1','admin','admin','2018-04-12 10:56:09','2018-04-12 10:56:42','0');

-- --------------------------------------------------------

--
-- 表的结构 `ek_model`
--

DROP TABLE IF EXISTS `ek_model`;
CREATE TABLE `ek_model` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '序号',
  `mcode` varchar(20) NOT NULL COMMENT '模型编号',
  `name` varchar(50) NOT NULL COMMENT '模型名称',
  `type` char(1) NOT NULL DEFAULT '2' COMMENT '是否列表类型',
  `urlname` varchar(100) NOT NULL DEFAULT '' COMMENT 'URL名称',
  `listtpl` varchar(255) NOT NULL COMMENT '列表页模板',
  `contenttpl` varchar(255) NOT NULL COMMENT '内容页模板',
  `status` char(1) NOT NULL DEFAULT '1' COMMENT '模型状态',
  `issystem` char(1) NOT NULL DEFAULT '0' COMMENT '系统模型',
  `create_user` varchar(30) NOT NULL COMMENT '创建人员',
  `update_user` varchar(30) NOT NULL COMMENT '更新人员',
  `create_time` datetime NOT NULL COMMENT '创建时间',
  `update_time` datetime NOT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `ek_model_mcode` (`mcode`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=utf8;

--
-- 转存表中的数据 `ek_model`
--

INSERT INTO `ek_model` (`id`,`mcode`,`name`,`type`,`urlname`,`listtpl`,`contenttpl`,`status`,`issystem`,`create_user`,`update_user`,`create_time`,`update_time`) VALUES
('1','1','专题','1','about','','about.html','1','1','admin','admin','2018-04-11 17:16:01','2019-08-05 11:11:44'),
('2','2','新闻','2','list','newslist.html','news.html','1','1','admin','admin','2018-04-11 17:17:16','2019-08-05 11:12:04'),
('3','3','产品','2','list','productlist.html','product.html','1','0','admin','admin','2018-04-11 17:17:46','2019-08-05 11:12:17'),
('4','4','案例','2','list','caselist.html','case.html','1','0','admin','admin','2018-04-11 17:19:53','2019-08-05 11:12:26'),
('5','5','招聘','2','list','joblist.html','job.html','1','0','admin','admin','2018-04-11 17:24:34','2019-08-05 11:12:37');

-- --------------------------------------------------------

--
-- 表的结构 `ek_role`
--

DROP TABLE IF EXISTS `ek_role`;
CREATE TABLE `ek_role` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '角色编号',
  `rcode` varchar(20) NOT NULL COMMENT '角色编码',
  `name` varchar(30) NOT NULL COMMENT '角色名称',
  `description` varchar(50) NOT NULL COMMENT '角色描述',
  `create_user` varchar(30) NOT NULL COMMENT '创建人员',
  `update_user` varchar(30) NOT NULL COMMENT '更新人员',
  `create_time` datetime NOT NULL COMMENT '创建时间',
  `update_time` datetime NOT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `ek_role_rcode` (`rcode`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;

--
-- 转存表中的数据 `ek_role`
--

INSERT INTO `ek_role` (`id`,`rcode`,`name`,`description`,`create_user`,`update_user`,`create_time`,`update_time`) VALUES
('1','R101','系统管理员','系统管理员具有所有权限','admin','admin','2017-03-22 11:33:32','2019-08-05 11:22:02'),
('2','R102','内容管理员','内容管理员具有基本内容管理权限','admin','admin','2017-06-01 00:32:02','2019-08-05 11:22:12');

-- --------------------------------------------------------

--
-- 表的结构 `ek_role_area`
--

DROP TABLE IF EXISTS `ek_role_area`;
CREATE TABLE `ek_role_area` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `rcode` varchar(20) NOT NULL,
  `acode` varchar(20) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ek_role_area_rcode` (`rcode`),
  KEY `ek_role_area_acode` (`acode`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;

--
-- 转存表中的数据 `ek_role_area`
--

INSERT INTO `ek_role_area` (`id`,`rcode`,`acode`) VALUES
('3','R101','cn'),
('4','R102','cn');

-- --------------------------------------------------------

--
-- 表的结构 `ek_role_level`
--

DROP TABLE IF EXISTS `ek_role_level`;
CREATE TABLE `ek_role_level` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '编号',
  `rcode` varchar(20) NOT NULL COMMENT '角色编码',
  `level` varchar(50) NOT NULL COMMENT '权限地址',
  PRIMARY KEY (`id`),
  KEY `ek_role_level_rcode` (`rcode`)
) ENGINE=MyISAM AUTO_INCREMENT=219 DEFAULT CHARSET=utf8;

--
-- 转存表中的数据 `ek_role_level`
--

INSERT INTO `ek_role_level` (`id`,`rcode`,`level`) VALUES
('165','R101','/admin/Role/index'),
('164','R101','/admin/Menu/mod'),
('163','R101','/admin/Menu/del'),
('162','R101','/admin/Menu/add'),
('161','R101','/admin/Menu/index'),
('160','R101','/admin/Area/mod'),
('159','R101','/admin/Area/del'),
('158','R101','/admin/Area/add'),
('157','R101','/admin/Area/index'),
('156','R101','/admin/M101/index'),
('155','R101','/admin/Tags/mod'),
('154','R101','/admin/Tags/del'),
('153','R101','/admin/Tags/add'),
('152','R101','/admin/Tags/index'),
('151','R101','/admin/Form/mod'),
('150','R101','/admin/Form/del'),
('149','R101','/admin/Form/add'),
('148','R101','/admin/Form/index'),
('147','R101','/admin/Link/mod'),
('146','R101','/admin/Link/del'),
('145','R101','/admin/Link/add'),
('144','R101','/admin/Link/index'),
('143','R101','/admin/Slide/mod'),
('142','R101','/admin/Slide/del'),
('141','R101','/admin/Slide/add'),
('140','R101','/admin/Slide/index'),
('139','R101','/admin/Message/mod'),
('138','R101','/admin/Message/del'),
('137','R101','/admin/Message/index'),
('136','R101','/admin/M157/index'),
('135','R101','/admin/Content/mod'),
('134','R101','/admin/Content/del'),
('133','R101','/admin/Content/add'),
('132','R101','/admin/Content/index'),
('131','R101','/admin/Single/mod'),
('130','R101','/admin/Single/index'),
('129','R101','/admin/M130/index'),
('128','R101','/admin/ContentSort/mod'),
('127','R101','/admin/ContentSort/del'),
('126','R101','/admin/ContentSort/add'),
('125','R101','/admin/ContentSort/index'),
('124','R101','/admin/Company/mod'),
('123','R101','/admin/Company/index'),
('122','R101','/admin/Site/mod'),
('121','R101','/admin/Site/index'),
('120','R101','/admin/M110/index'),
('119','R101','/admin/ExtField/mod'),
('118','R101','/admin/ExtField/del'),
('117','R101','/admin/ExtField/add'),
('116','R101','/admin/ExtField/index'),
('115','R101','/admin/Model/mod'),
('114','R101','/admin/Model/del'),
('113','R101','/admin/Model/add'),
('112','R101','/admin/Model/index'),
('111','R101','/admin/Label/mod'),
('110','R101','/admin/Label/del'),
('109','R101','/admin/Label/add'),
('108','R101','/admin/Label/index'),
('107','R101','/admin/Config/index'),
('106','R101','/admin/M156/index'),
('205','R102','/admin/Link/add'),
('204','R102','/admin/Link/index'),
('203','R102','/admin/Slide/mod'),
('202','R102','/admin/Slide/del'),
('201','R102','/admin/Slide/add'),
('200','R102','/admin/Slide/index'),
('199','R102','/admin/Message/mod'),
('198','R102','/admin/Message/del'),
('197','R102','/admin/Message/index'),
('196','R102','/admin/M157/index'),
('195','R102','/admin/Content/mod'),
('194','R102','/admin/Content/del'),
('193','R102','/admin/Content/add'),
('192','R102','/admin/Content/index'),
('191','R102','/admin/Single/mod'),
('190','R102','/admin/Single/index'),
('189','R102','/admin/M130/index'),
('188','R102','/admin/ContentSort/mod'),
('187','R102','/admin/ContentSort/del'),
('186','R102','/admin/ContentSort/add'),
('185','R102','/admin/ContentSort/index'),
('184','R102','/admin/Company/mod'),
('183','R102','/admin/Company/index'),
('182','R102','/admin/Site/mod'),
('181','R102','/admin/Site/index'),
('180','R102','/admin/M110/index'),
('166','R101','/admin/Role/add'),
('167','R101','/admin/Role/del'),
('168','R101','/admin/Role/mod'),
('169','R101','/admin/User/index'),
('170','R101','/admin/User/add'),
('171','R101','/admin/User/del'),
('172','R101','/admin/User/mod'),
('173','R101','/admin/Syslog/index'),
('174','R101','/admin/Type/index'),
('175','R101','/admin/Type/add'),
('176','R101','/admin/Type/del'),
('177','R101','/admin/Type/mod'),
('178','R101','/admin/Database/index'),
('179','R101','/admin/Site/server'),
('206','R102','/admin/Link/del'),
('207','R102','/admin/Link/mod'),
('208','R102','/admin/Form/index'),
('209','R102','/admin/Form/add'),
('210','R102','/admin/Form/del'),
('211','R102','/admin/Form/mod'),
('212','R102','/admin/Tags/index'),
('213','R102','/admin/Tags/add'),
('214','R102','/admin/Tags/del'),
('215','R102','/admin/Tags/mod'),
('216','R101','/admin/Config/seo'),
('217','R101','/admin/Spider/index'),
('218','R102','/admin/Spider/index');

-- --------------------------------------------------------

--
-- 表的结构 `ek_site`
--

DROP TABLE IF EXISTS `ek_site`;
CREATE TABLE `ek_site` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '站点编号',
  `acode` varchar(20) NOT NULL COMMENT '区域代码',
  `title` varchar(100) NOT NULL COMMENT '站点标题',
  `subtitle` varchar(200) NOT NULL COMMENT '站点副标题',
  `domain` varchar(50) NOT NULL COMMENT '站点地址',
  `logo` varchar(100) NOT NULL COMMENT '站点LOGO地址',
  `keywords` varchar(200) NOT NULL COMMENT '站点关键字',
  `description` varchar(500) NOT NULL COMMENT '站点描述',
  `icp` varchar(30) NOT NULL COMMENT '站点备案',
  `theme` varchar(30) NOT NULL COMMENT '站点主题',
  `statistical` varchar(500) NOT NULL COMMENT '站点统计码',
  `copyright` varchar(200) NOT NULL COMMENT '版权信息',
  PRIMARY KEY (`id`),
  KEY `ek_site_acode` (`acode`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

--
-- 转存表中的数据 `ek_site`
--

INSERT INTO `ek_site` (`id`,`acode`,`title`,`subtitle`,`domain`,`logo`,`keywords`,`description`,`icp`,`theme`,`statistical`,`copyright`) VALUES
('1','cn','EkoCMS','永久开源免费的PHP企业网站开发建设管理系统','ekocms.d50.cn','/static/images/logo.png','cms,免费cms,开源cms,企业cms,建站cms','EkoCMS是基于PBootCMS翻版开发且永久开源免费的PHP企业网站开发建设管理系统，是一套高效、简洁、 强悍的可免费商用的PHP CMS源码，能够满足各类企业网站开发建设的需要。系统采用简单到想哭的模板标签，只要懂HTML就可快速开发企业网站。官方提供了大量网站模板免费下载和使用，将致力于为广大开发者和企业提供最佳的网站开发建设解决方案。','鲁ICP备88888888号','default','','Copyright © 2018-2026 EkoCMS All Rights Reserved.');

-- --------------------------------------------------------

--
-- 表的结构 `ek_slide`
--

DROP TABLE IF EXISTS `ek_slide`;
CREATE TABLE `ek_slide` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '序号',
  `acode` varchar(20) NOT NULL COMMENT '区域编码',
  `gid` int(10) unsigned NOT NULL COMMENT '分组序号',
  `pic` varchar(100) NOT NULL COMMENT '图片地址',
  `link` varchar(100) NOT NULL COMMENT '跳转链接',
  `title` varchar(50) NOT NULL COMMENT '说明文字',
  `subtitle` varchar(100) NOT NULL COMMENT '副标题/描述',
  `sorting` int(11) NOT NULL COMMENT '排序',
  `create_user` varchar(30) NOT NULL COMMENT '创建人员',
  `update_user` varchar(30) NOT NULL COMMENT '更新人员',
  `create_time` datetime NOT NULL COMMENT '创建时间',
  `update_time` datetime NOT NULL COMMENT '修改时间',
  PRIMARY KEY (`id`),
  KEY `ek_slide_acode` (`acode`),
  KEY `ek_slide_gid` (`gid`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;

--
-- 转存表中的数据 `ek_slide`
--

INSERT INTO `ek_slide` (`id`,`acode`,`gid`,`pic`,`link`,`title`,`subtitle`,`sorting`,`create_user`,`update_user`,`create_time`,`update_time`) VALUES
('1','cn','1','/static/upload/image/20180412/1523500997605565.jpg','http://ekocms.d50.cn','EkoCMS','永久开源、免费的PHP建站系统','255','admin','admin','2018-03-01 16:19:03','2018-04-12 10:43:19'),
('2','cn','1','/static/upload/image/20180412/1523501147676550.jpg','http://ekocms.d50.cn','EkoCMS','高效、简洁、强悍的PHP建站源码','255','admin','admin','2018-04-12 10:46:07','2018-04-12 10:46:07');

-- --------------------------------------------------------

--
-- 表的结构 `ek_syslog`
--

DROP TABLE IF EXISTS `ek_syslog`;
CREATE TABLE `ek_syslog` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '日志编号',
  `level` varchar(20) NOT NULL COMMENT '信息等级',
  `event` varchar(200) NOT NULL COMMENT '事件',
  `user_ip` varchar(11) NOT NULL DEFAULT '0' COMMENT '客户端IP',
  `user_os` varchar(30) NOT NULL COMMENT '客户端系统',
  `user_bs` varchar(30) NOT NULL COMMENT '客户端浏览器',
  `create_user` varchar(30) NOT NULL COMMENT '创建人员',
  `create_time` datetime NOT NULL COMMENT '添加时间',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `ek_tags`
--

DROP TABLE IF EXISTS `ek_tags`;
CREATE TABLE `ek_tags` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '编号',
  `acode` varchar(20) NOT NULL COMMENT '区域',
  `name` varchar(50) NOT NULL COMMENT '名称',
  `link` varchar(200) NOT NULL COMMENT '链接',
  `create_user` varchar(30) NOT NULL COMMENT '添加人员',
  `update_user` varchar(30) NOT NULL COMMENT '更新人员',
  `create_time` datetime NOT NULL COMMENT '添加时间',
  `update_time` datetime NOT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `ek_tags_acode` (`acode`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

--
-- 转存表中的数据 `ek_tags`
--

INSERT INTO `ek_tags` (`id`,`acode`,`name`,`link`,`create_user`,`update_user`,`create_time`,`update_time`) VALUES
('1','cn','EkoCMS','https://ekocms.d50.cn','admin','admin','2019-07-12 14:33:13','2019-07-12 14:33:13');

-- --------------------------------------------------------

--
-- 表的结构 `ek_type`
--

DROP TABLE IF EXISTS `ek_type`;
CREATE TABLE `ek_type` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '类型编号',
  `tcode` varchar(20) NOT NULL COMMENT '类型编码',
  `name` varchar(30) NOT NULL COMMENT '类型名称',
  `item` varchar(30) NOT NULL COMMENT '类型项',
  `value` varchar(20) NOT NULL DEFAULT '0' COMMENT '类型值',
  `sorting` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  `create_user` varchar(30) NOT NULL COMMENT '添加人员',
  `update_user` varchar(30) NOT NULL COMMENT '更新时间',
  `create_time` datetime NOT NULL COMMENT '添加时间',
  `update_time` datetime NOT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `ek_type_tcode` (`tcode`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=utf8;

--
-- 转存表中的数据 `ek_type`
--

INSERT INTO `ek_type` (`id`,`tcode`,`name`,`item`,`value`,`sorting`,`create_user`,`update_user`,`create_time`,`update_time`) VALUES
('1','T101','菜单功能','新增','add','1','admin','admin','2017-04-27 07:28:34','2017-08-09 15:25:56'),
('2','T101','菜单功能','删除','del','2','admin','admin','2017-04-27 07:29:08','2017-08-09 15:23:34'),
('3','T101','菜单功能','修改','mod','3','admin','admin','2017-04-27 07:29:34','2017-08-09 15:23:32'),
('4','T101','菜单功能','导出','export','4','admin','admin','2017-04-27 07:30:42','2017-08-09 15:23:29'),
('5','T101','菜单功能','导入','import','5','admin','admin','2017-04-27 07:31:38','2017-08-09 15:23:27');

-- --------------------------------------------------------

--
-- 表的结构 `ek_user`
--

DROP TABLE IF EXISTS `ek_user`;
CREATE TABLE `ek_user` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '用户编号',
  `ucode` varchar(20) NOT NULL COMMENT '用户编码',
  `username` varchar(30) NOT NULL COMMENT '用户账号',
  `realname` varchar(30) NOT NULL COMMENT '真实名字',
  `password` char(32) NOT NULL COMMENT '用户密码',
  `status` char(1) NOT NULL DEFAULT '1' COMMENT '是否启用',
  `login_count` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '登录次数',
  `last_login_ip` varchar(11) NOT NULL DEFAULT '0' COMMENT '最后登录IP',
  `create_user` varchar(30) NOT NULL COMMENT '添加人员',
  `update_user` varchar(30) NOT NULL COMMENT '更新用户',
  `create_time` datetime NOT NULL COMMENT '创建时间',
  `update_time` datetime NOT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `ek_user_ucode` (`ucode`),
  KEY `ek_user_username` (`username`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

--
-- 转存表中的数据 `ek_user`
--

INSERT INTO `ek_user` (`id`,`ucode`,`username`,`realname`,`password`,`status`,`login_count`,`last_login_ip`,`create_user`,`update_user`,`create_time`,`update_time`) VALUES
('1','10001','admin','超级管理员','14e1b600b1fd579f47433b88e8d85291','1','0','2130706433','admin','admin','2017-05-08 18:50:30','2018-07-17 15:47:27');

-- --------------------------------------------------------

--
-- 表的结构 `ek_user_role`
--

DROP TABLE IF EXISTS `ek_user_role`;
CREATE TABLE `ek_user_role` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '编号',
  `ucode` varchar(20) NOT NULL COMMENT '用户编码',
  `rcode` varchar(20) NOT NULL COMMENT '角色编码',
  PRIMARY KEY (`id`),
  KEY `ek_user_role_ucode` (`ucode`),
  KEY `ek_user_role_rcode` (`rcode`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

--
-- 转存表中的数据 `ek_user_role`
--

INSERT INTO `ek_user_role` (`id`,`ucode`,`rcode`) VALUES
('1','10001','R101');

-- --------------------------------------------------------
