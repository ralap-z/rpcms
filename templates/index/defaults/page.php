<?php if (!defined('CMSPATH')){exit('error!');}?>
{include:header}
<main>
	<h2 class="place">您现在的位置是：{:mianbao($listType,$listId)}</h2>
	<div class="abinfos">
        <h3>{$data['title']}</h3>
		<p></p>
		<div>
			{$data['content']}
		</div>
	</div>
	<div class="comment_box" id="commentlist">
		{:comment_list($CommentData)}
	</div>
	<div class="comment_post">
		<h3 class="comment_title">欢迎 <b>你</b> 来评论</h3>
		<div class="comment_post_form">
			{:comment_post('pages',$listId,$user,$data['isRemark'])}
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