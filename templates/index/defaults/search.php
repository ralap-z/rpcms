<?php if (!defined('CMSPATH')){exit('error!');}?>
{include:header}
<main>
	<h2 class="place">您现在的位置是：{:mianbao($listType,$listId)}</h2>
	<div class="bloglist">
		<ul>
			{foreach $logList as $k=>$v}
				<li> 
					<i class="blogpic"><a href="{$v['url']}" title="{$v['title']}" target="_blank">{:thumb($v)}</a></i>
					<dl>
						<dt><a href="{$v['url']}" title="{$v['title']}" target="_blank">{$v['title']|str_replace=$listId,'<font style="color: #f40;">'.$listId.'</font>',###}</a></dt>
						<dd><span class="bloginfo">{$v['excerpt']}</span>
						<p class="timeinfo"><span class="lanmu"><a href="{$v['cateUrl']}">{$v['cateName']|default='未分类'}</a></span><span class="date">{$v['createTime']}</span></p>
						<a class="read" href="{$v['url']}">阅读更多</a> </dd>
					</dl>
				</li>
			{/foreach}
		</ul>
	</div>
	<div class="pages">{$pageHtml|raw}</div>
</main>
<aside class="sidebar">
	<ul class="sidenews">
		<h2>最新文章</h2>
		{$newLogs=newLogs(5)}
		{foreach $newLogs as $k=>$v}
			<li> 
				<i>{:thumb($v)}</i>
				<p><a href="{$v['url']}" title="{$v['title']}">{$v['title']}</a></p>
				<span>{$v['createTime']|strtotime|date='Y-m-d',###}</span>
			</li>
		{/foreach}
	</ul>
	<ul class="sidenews">
		<h2>随机阅读</h2>
		{$randLog=randLog(5)}
		{foreach $randLog as $k=>$v}
			<li> 
				<i>{:thumb($v)}</i>
				<p><a href="{$v['url']}" title="{$v['title']}">{$v['title']}</a></p>
				<span>{$v['createTime']|strtotime|date='Y-m-d',###}</span>
			</li>
		{/foreach}
	</ul>
	<div class="tjlm">
		<h2 class="hometitle">推荐栏目</h2>
		<ul>
			{$category=category()}
			{foreach $category as $k=>$v}
				<li> <a href="{$v['url']}">{$v['cate_name']}</a></li>
			{/foreach}
		</ul>
	</div>
	<div class="tjlm">
		<h2 class="hometitle">文章归档</h2>
		<ul>
			{$record=record()}
			{foreach $record as $k=>$v}
				<li style="width: 100%;line-height: normal;text-align: left;"> <a href="{$v['url']}" style="border:0">{$v['record']} ({$v['logNum']})</a></li>
			{/foreach}
		</ul>
	</div>
	
	
</aside>
{include:footer}