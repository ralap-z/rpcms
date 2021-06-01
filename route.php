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

return array(
	array(
		'model'=>'index/logs/index',
		'pattern'=>'|^index(_(\d+))?/?$|',
	),
	array(
		'model'=>'index/logs/detail',
		'pattern'=>'|^post/(\w+)(_(\d+))?/?$|',
	),
	array(
		'model'=>'index/logs/search',
		'pattern'=>'|^search?/?$|',
	),
	array(
		'model'=>'index/rss/index',
		'pattern'=>'|^sitemap(/\w+)?/?$|',
	),
	array(
		'model'=>'index/author/index',
		'pattern'=>'|^author/(\w+)(_(\d+))?/?$|',
	),
	array(
		'model'=>'index/category/index',
		'pattern'=>'|^category/(\w+)(_(\d+))?/?$|',
	),
	array(
		'model'=>'index/special/index',
		'pattern'=>'|^special/(\w+)(_(\d+))?/?$|',
	),
	array(
		'model'=>'index/pages/index',
		'pattern'=>'|^html/(\w+)(_(\d+))?/?$|',
	),
	array(
		'model'=>'index/tags/index',
		'pattern'=>'|^tag/(.*)(_(\d+))?/?$|',
	),
	array(
		'model'=>'index/logs/dates',
		'pattern'=>'|^date/(\d{6,8})(_(\d+))?/?$|',
	),
	array(
		'model'=>'index/comment/add',
		'pattern'=>'|^comment/(addcom)?/?$|',
	),
	array(
		'model'=>'index/plugin/run',
		'pattern'=>'|^plugin/(\w+)(/\w+)(/\w+)?/?$|',
	),
	array(
		'model'=>'index/base/captcha',
		'pattern'=>'|^captcha(/\w+)?/?$|',
	),
	array(
		'model'=>'index/logs/praise',
		'pattern'=>'|^praise(/\w+)?/?$|',
	),
	array(
		'model'=>'index/logs/index',
		'pattern'=>'|^/?([\?&].*)?$|',
	),
	
);