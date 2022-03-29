<?php
// +----------------------------------------------------------------------
// | RPCMS
// +----------------------------------------------------------------------
// | Copyright (c) 2019 http://www.rpcms.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: ralap <www.rpcms.cn>
// +----------------------------------------------------------------------

return [
	array(
		'model'=>'/index/logs/index',
		'pattern'=>'index<page#_?>',
	),
	array(
		'model'=>'/index/logs/detail',
		'pattern'=>'post/<id><page#_?>',
	),
	array(
		'model'=>'/index/logs/search',
		'pattern'=>'search',
	),
	array(
		'model'=>'/index/rss/index',
		'pattern'=>'sitemap<type#\?>',
		'ext'=>'xml|html'
	),
	array(
		'model'=>'/index/author/index',
		'pattern'=>'author/<id><page#_?>',
	),
	array(
		'model'=>'/index/category/index',
		'pattern'=>'category/<id><page#_?>',
	),
	array(
		'model'=>'/index/special/index',
		'pattern'=>'special/<id><page#_?>',
	),
	array(
		'model'=>'/index/pages/index',
		'pattern'=>'html/<id>',
	),
	array(
		'model'=>'/index/tags/index',
		'pattern'=>'tag/<id><page#_?>',
	),
	array(
		'model'=>'/index/logs/dates',
		'pattern'=>'date/<date><page#_?>',
		'replace'=>['date'=>'\d{6,8}'],
	),
	array(
		'model'=>'/index/comment/add',
		'pattern'=>'comment/addcom',
	),
	array(
		'model'=>'/index/plugin/run',
		'pattern'=>'plugin/<plugin><controller#\?><action#\?>',
		'replace'=>['plugin'=>'[A-Za-z0-9-_]+'],
	),
	array(
		'model'=>'/index/base/captcha',
		'pattern'=>'captcha<type#\?>',
	),
	array(
		'model'=>'/index/logs/praise',
		'pattern'=>'praise',
	)
];