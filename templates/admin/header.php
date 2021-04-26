<?php if (!defined('CMSPATH')){exit('error!');}?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<title>RPCMS</title>
	<meta name="renderer" content="webkit">
	<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
	<meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=0">
	<meta name="renderer" content="webkit|ie-comp|ie-stand">
	<meta http-equiv="Cache-Control" content="no-siteapp">
	<link rel="stylesheet" href="{$cmspath}/static/css/admin.css" media="all">
	<script>
		var baseUrl="{$baseUrl}",
			attrSelectUrl="{:url('index/attrSelect')}",
			attrDeleUrl="{:url('index/attrDele')}",
			adminUpdatePsw="{:url('index/updatePsw')}";
	</script>
	<script src="{$cmspath}/static/js/jquery-3.2.0.min.js"></script>
	<script src="{$cmspath}/static/js/me.min.js"></script>
	<script src="{$cmspath}/static/js/common.js"></script>
</head>
<body>
<div class="menu">
	<div class="logo"><span>RPCMS</span></div>
	<ul class="menu_tree">
		<li class="menu_item" data-type="logs_add"><a href="{:url('logs/add')}"><i class="me-icon me-icon-edit"></i><span>发布文章</span></a></li>
		<li class="menu_item" data-type="logs_list"><a href="{:url('logs/index')}"><i class="me-icon me-icon-list"></i><span>文章</span></a></li>
		<li class="menu_item" data-type="pages"><a href="{:url('pages/index')}"><i class="me-icon me-icon-template"></i><span>单页</span></a></li>
		<li class="menu_item" data-type="nav"><a href="{:url('nav/index')}"><i class="me-icon me-icon-spread-left"></i><span>导航</span></a></li>
		<li class="menu_item" data-type="category"><a href="{:url('category/index')}"><i class="me-icon me-icon-flag"></i><span>分类</span></a></li>
		<li class="menu_item" data-type="tages"><a href="{:url('tages/index')}"><i class="me-icon me-icon-note"></i><span>标签</span></a></li>
		<li class="menu_item" data-type="special"><a href="{:url('special/index')}"><i class="me-icon me-icon-engine"></i><span>专题</span></a></li>
		<li class="menu_item" data-type="comment"><a href="{:url('comment/index')}"><i class="me-icon me-icon-dialogue"></i><span>评论{$commentExamNum ? '('.$commentExamNum.')' : ''}</span></a></li>
		<li class="menu_item" data-type="user"><a href="{:url('user/index')}"><i class="me-icon me-icon-user"></i><span>用户</span></a></li>
		<li class="menu_item" data-type="links"><a href="{:url('links/index')}"><i class="me-icon me-icon-link"></i><span>友链</span></a></li>
		<li class="menu_item" data-type="temp"><a href="{:url('temp/index')}"><i class="me-icon me-icon-theme"></i><span>模板</span></a></li>
		<li class="menu_item" data-type="webset"><a href="{:url('index/webset')}"><i class="me-icon me-icon-set"></i><span>设置</span></a></li>
		<li class="menu_item" data-type="plugin"><a href="{:url('plugin/index')}"><i class="me-icon me-icon-app"></i><span>插件</span></a></li>
		{if $hasLeftMenu}
			<li class="menu_item menu_son" data-type="extendMenu">
				<a href="javascript:;"><i class="me-icon me-icon-senior"></i><span>扩展菜单</span></a>
				<dl class="menu_child">
					{hook:admin_left_menu}
				</dl>
			</li>
		{/if}
	</ul>
	<div style="height: 2rem;line-height: 2rem;text-align: center;font-size: 0.8rem;color: #666;">&copy;{:date('Y')} RPCMS v{RP.RPCMS_VERSION}</div>
</div>
<div class="top">
	<ul class="top_left">
		<li class="top_item"><a href="{:url('index/index')}" title="主页"><i class="me-icon me-icon-home"></i></a></li>
	</ul>
	<ul class="top_right">
		{hook:admin_top_menu}
		<li class="top_item fullScreen"><a href="javascript:;" title="全屏"><i class="me-icon me-icon-screen-full"></i></a></li>
		<li class="top_item">
			<a href="javascript:;"><span>管理员</span><span class="menu_more"></span></a>
			<dl class="top_child">
				<dd><a href="javascript:;" class="updateAdmin" data-model-type="updateAdmin">修改资料</a></dd>
			</dl>
		</li>
		<li class="top_item"><a href="{:url('login/out')}" title="退出" class="loginOut"><i class="me-icon me-icon-radio"></i></a></li>
	</ul>
</div>
<div class="content page_centent">