CREATE TABLE IF NOT EXISTS `%pre%attachment` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `filename` varchar(255) NOT NULL,
  `filesize` varchar(100) NOT NULL,
  `filepath` varchar(255) NOT NULL,
  `filetype` varchar(40) NOT NULL,
  `token` varchar(255) NULL DEFAULT '' COMMENT '关联码',
  `logId` int(10) NULL DEFAULT '0',
  `pageId` int(10) NULL DEFAULT '0' COMMENT '单页ID',
  `authorId` int(10) NOT NULL,
  `createTime` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `filetype` (`filetype`),
  KEY `logId` (`logId`),
  KEY `authorId` (`authorId`),
  KEY `pageId` (`pageId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='附件';

CREATE TABLE IF NOT EXISTS `%pre%category` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `cate_name` varchar(255) NOT NULL,
  `alias` varchar(200) DEFAULT '',
  `sort` int(10) DEFAULT '0',
  `topId` int(10) DEFAULT '0',
  `seo_key` varchar(255) DEFAULT '',
  `seo_desc` text,
  `temp_list` varchar(200) DEFAULT '',
  `temp_logs` varchar(200) DEFAULT '',
  `is_submit` tinyint(1) DEFAULT '0' COMMENT '是否支持投稿, 0不支持 1支持',
  `createTime` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `alias` (`alias`),
  KEY `sort` (`sort`),
  KEY `topId` (`topId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='文章分类';

CREATE TABLE IF NOT EXISTS `%pre%comment` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `logId` int(10) NULL DEFAULT '0' COMMENT '文章ID',
  `pageId` int(10) NULL DEFAULT '0' COMMENT '单页ID',
  `topId` int(10) DEFAULT '0',
  `authorId` int(10) DEFAULT '0',
  `userId` int(10) DEFAULT '0',
  `levels` tinyint(1) DEFAULT '1' COMMENT '层级',
  `nickname` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT '',
  `home` varchar(255) NULL DEFAULT '',
  `content` text NOT NULL,
  `ip` varchar(15) NOT NULL,
  `agent` text NOT NULL,
  `createTime` datetime DEFAULT NULL,
  `status` tinyint(1) DEFAULT '0' COMMENT '状态, 0发布 1审核',
  PRIMARY KEY (`id`),
  KEY `logId` (`logId`),
  KEY `pageId` (`pageId`),
  KEY `authorId` (`authorId`),
  KEY `topId` (`topId`),
  KEY `createTime` (`createTime`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='评论';

CREATE TABLE IF NOT EXISTS `%pre%config` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `cname` varchar(50) NOT NULL,
  `cvalue` longtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `cname` (`cname`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='系统配置';

CREATE TABLE IF NOT EXISTS `%pre%links` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `sitename` varchar(200) NOT NULL,
  `sitedesc` text,
  `siteurl` varchar(255) NOT NULL,
  `sort` int(10) DEFAULT '0',
  `status` tinyint(1) DEFAULT '0' COMMENT '状态, 0正常 1审核 -1关闭',
  PRIMARY KEY (`id`),
  KEY `sort` (`sort`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='友情链接';

CREATE TABLE IF NOT EXISTS `%pre%logs` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `alias` varchar(200) DEFAULT NULL,
  `authorId` int(10) UNSIGNED NOT NULL COMMENT '作者ID',
  `cateId` int(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT '分类ID',
  `specialId` INT(11) UNSIGNED NULL DEFAULT '0',
  `excerpt` varchar(255) NOT NULL COMMENT '摘要',
  `keywords` VARCHAR(255) NULL DEFAULT '' COMMENT '关键词',
  `password` varchar(255) DEFAULT '' COMMENT '访问密码',
  `content` longtext NOT NULL,
  `tages` varchar(255) NULL DEFAULT '',
  `isTop` tinyint(1) DEFAULT '0' COMMENT '是否置顶 0否 1是',
  `isRemark` tinyint(1) DEFAULT '0' COMMENT '是否评论 0否 1是',
  `views` int(10) DEFAULT '0' COMMENT '浏览量',
  `comnum` int(10) DEFAULT '0' COMMENT '评论量',
  `upnum` int(10) DEFAULT '0' COMMENT '点赞量',
  `template` varchar(200) DEFAULT '',
  `upateTime` datetime DEFAULT NULL,
  `createTime` datetime DEFAULT NULL,
  `extend` longtext NULL DEFAULT NULL COMMENT '扩展数据',
  `status` tinyint(1) DEFAULT '0' COMMENT '状态, 0发布 1审核 2草稿 -1下架 -2审核不通过',
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`),
  UNIQUE KEY `alias` (`alias`) USING BTREE,
  KEY `comnum` (`comnum`),
  KEY `upateTime` (`upateTime`),
  KEY `views` (`views`),
  KEY `title` (`title`),
  KEY `authorId` (`authorId`),
  KEY `cateId` (`cateId`),
  KEY `specialId` (`specialId`),
  KEY `tages` (`tages`),
  KEY `upnum` (`upnum`),
  KEY `isTop` (`status`,`isTop`,`upateTime`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='文章列表';

CREATE TABLE IF NOT EXISTS `%pre%nav` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `navname` varchar(200) NOT NULL,
  `alias` varchar(200) DEFAULT '',
  `url` varchar(255) DEFAULT '',
  `topId` int(10) DEFAULT '0',
  `types` tinyint(1) DEFAULT '4' COMMENT '类型 1:系统,2:分类,3:单页,4:自定',
  `typeId` int(10) DEFAULT '0' COMMENT '分类ID',
  `newtab` tinyint(1) DEFAULT '0' COMMENT '打开方式 0self 1新窗口',
  `sort` int(10) DEFAULT '0',
  `status` tinyint(1) DEFAULT '0' COMMENT '状态, 0正常 -1关闭',
  PRIMARY KEY (`id`),
  KEY `alias` (`alias`),
  KEY `cateId` (`typeId`),
  KEY `sort` (`sort`),
  KEY `status` (`status`),
  KEY `type` (`types`),
  KEY `topId` (`topId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='导航';

CREATE TABLE IF NOT EXISTS `%pre%pages` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `alias` varchar(200) DEFAULT '',
  `content` longtext NOT NULL,
  `seo_key` varchar(255) DEFAULT '',
  `seo_desc` text,
  `password` varchar(255) DEFAULT '' COMMENT '访问密码',
  `authorId` int(10) NOT NULL,
  `comnum` int(10) DEFAULT '0' COMMENT '评论量',
  `template` varchar(200) DEFAULT '',
  `createTime` datetime DEFAULT NULL,
  `isRemark` tinyint(1) DEFAULT '0' COMMENT '是否评论 0否 1是',
  `extend` longtext NULL DEFAULT NULL COMMENT '扩展数据',
  `status` tinyint(1) DEFAULT '0' COMMENT '状态, 0发布 1审核 2草稿 -1下架',
  PRIMARY KEY (`id`),
  KEY `title` (`title`),
  KEY `alias` (`alias`),
  KEY `authorId` (`authorId`),
  KEY `status` (`status`),
  KEY `comnum` (`comnum`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='单页';

CREATE TABLE IF NOT EXISTS `%pre%plugin` (
  `ppath` varchar(255) NOT NULL,
  `config` longtext COMMENT '配置',
  `status` tinyint(1) DEFAULT '0' COMMENT '状态, 0正常 -1关闭',
  KEY `ppath` (`ppath`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='插件';

CREATE TABLE IF NOT EXISTS `%pre%tages` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `tagName` varchar(100) NOT NULL,
  `alias` varchar(200) DEFAULT '',
  `seo_desc` text,
  `template` varchar(200) DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `tagName` (`tagName`),
  KEY `alias` (`alias`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='标签';

CREATE TABLE IF NOT EXISTS `%pre%user` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(64) NOT NULL,
  `phone` varchar(11) NULL DEFAULT NULL COMMENT '手机号码',
  `email` varchar(100) NULL DEFAULT NULL COMMENT 'email',
  `nickname` varchar(30) NOT NULL COMMENT '昵称',
  `role` varchar(10) DEFAULT 'admin' COMMENT '昵称',
  `isCheck` tinyint(1) DEFAULT '0' COMMENT '文章是否需要审核0,1',
  `status` tinyint(1) DEFAULT '0' COMMENT '状态, -1禁用',
  PRIMARY KEY (`id`),
  KEY `username` (`username`),
  KEY `role` (`role`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='用户表';

CREATE TABLE IF NOT EXISTS `%pre%special` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `subTitle` varchar(255) NULL DEFAULT '',
  `headimg` varchar(255) NULL DEFAULT '',
  `alias` varchar(200) NULL DEFAULT '',
  `seo_desc` text,
  `upnum` int(10) DEFAULT '0' COMMENT '点赞量',
  `temp_list` varchar(200) DEFAULT '',
  `updateTime` datetime DEFAULT NULL,
  `createTime` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `alias` (`alias`),
  KEY `title` (`title`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='专题';