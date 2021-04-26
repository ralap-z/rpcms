<?php
	return array(
		//数据库信息
		'db'=>array(
			'hostname'=>'127.0.0.1',
			'username'=>'',
			'password'=>'',
			'database'=>'',
			'prefix'=>'rp_',
			'charset'=>'utf8',
		),
		//cms安装目录，适用于子文件适用
		'app_default_path'       => '',
		// 域名根，如：rpcms.cn
		'domain_root'        => '',
		//二级域名绑定关系
		'domain_root_rules'        => array(),
		//默认跳转地址，当没有referer的时候
		'app_default_referer'    => '',
		//数据加密key
		'app_key'                => 'rpcms',
		//默认module
		'default_module'         => 'index',
		//默认controller
		'default_controller'     => 'Index',
		//默认action
		'default_action'         => 'index',
		//禁止通过URL访问的module，多个用“,”隔开
		'deny_module'			 => '',
		//自定义后台地址，请勿和伪静态命名和二级域名重复，否则可能会被规则覆盖
		'diy_admin'        		 => '',
		//url后缀
		'url_html_suffix'        => 'html',
		//是否缓存模板，当适用模板标签的时候必须开启
		'tpl_cache'              => true,
		//模板禁用函数
		'tpl_deny_func_list'     => 'echo,exit',
		//验证码
		'captha_style_width'     => 90,
		'captha_style_height'     => 30,
		//默认启用的hook，请勿修改
		'default_hook'=>array(
			'admin_left_menu'=>[],
			'admin_top_menu'=>[],
		),
	);