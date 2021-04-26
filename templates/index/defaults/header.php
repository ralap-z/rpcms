<?php if (!defined('CMSPATH')){exit('error!');}?>
<!doctype html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>{$title}</title>
<meta name="keywords" content="{$keywords}">
<meta name="description" content="{$description}">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="{$tempUrl}/css/base.css" rel="stylesheet">
{hook:index_header}
<script src="/static/js/jquery-3.2.0.min.js"></script>
<style>
.win{width:{$tempConfig['appWidth']}px}
{if $tempConfig['layout'] == 'left'}
main{float:right;}.sidebar{float: left;}
{else}
main{float:left;}.sidebar{float: right;}
{/if}
</style>
</head>
<body style="background:{$tempConfig['bgColor']}">
<header id="header">
	<div class="navbar">
		<div class="topbox win">
			<p class="welcome">您好，欢迎您访问RPCMS！</p>
			<div class="searchbox">
				<form action="/search/" method="get" name="searchform">
					<input class="input" placeholder="想搜点什么呢.."  name="q" type="text">
					<input class="search_ico" type="submit"/>
				</form>
			</div>
		</div>
	</div>
	<div class="header-navigation">
		<nav class="win">
			<div class="logo"><a href="/" style="display:block;" title="{$webConfig['webName']}"><img src="{$tempUrl}/images/logo.png"/></a></div>
			<h2 id="mnavh"><span class="navicon"></span></h2>
			<ul id="starlist">
				<li class="selected"><a href="/" >首页</a></li>
				{:navs()}
			</ul>
		</nav>
	</div>
</header>
<div class="wrapper win">