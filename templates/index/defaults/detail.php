<?php if (!defined('CMSPATH')){exit('error!');}?>
{include:header}
<main>
	<h2 class="place">您现在的位置是：{:mianbao($listType,$data)}</h2>
	<div class="infosbox">
		<div class="newsview">
			<h3 class="news_title">{$data['title']}</h3>
			<div class="bloginfo">
				<ul>
					<li class="author"><a href="{$data['authorUrl']}">{$data['author']}</a></li>
					<li class="lmname"><a href="{$data['cateUrl']}">{$data['cateName']}</a></li>
					<li class="timer">{$data['createTime']}</li>
					<li class="view">{$data['views']}阅读 <a href="#commentlist">{$data['comnum']}评论</a></li>
				</ul>
			</div>
			<div class="news_about"><strong>简介</strong>{$data['excerpt']}</div>
			<div class="news_content">
				{$data['content']|content2keyword}
			</div>
		</div>
		{hook:down_show($listId)}
		<div class="share">
			<p class="diggit"><a href="javascript:;" class="praise" data-val="{$listId}">赞一下(<i>{$data['upnum']}</i>)</a></p>
		</div>
		<div class="tags">
			{foreach $data['tages'] as $tk=>$tv}
			<a href="{$tv['url']}">{$tv['name']}</a>
			{/foreach}
		</div>
	</div>
	<div class="nextinfo">{:neighbor($listId)}</div>
	<div class="otherlink">
		<h2>相关文章</h2>
		<ul>{:related($data)}</ul>
	</div>
	
	<div class="comment_box" id="commentlist">
		{:comment_list($CommentData)}
	</div>
	<div class="comment_post">
		<h3 class="comment_title">欢迎 <b>你</b> 来评论</h3>
		<div class="comment_post_form">
			{:comment_post('logs',$listId,$user,$data['isRemark'])}
		</div>
	</div>
	
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