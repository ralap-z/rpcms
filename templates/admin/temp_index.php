<?php if (!defined('CMSPATH')){exit('error!');}?>
{include:/header}
<style>
.me_body{background: transparent;padding: 0;}
.temp_list{}
.temp_item{width: 20rem;float: left;background: #fff;margin: 0 1rem 1rem 0;padding: 0.3rem;}
.temp_item .img{cursor: pointer;max-height: 15rem;overflow: hidden;background: #fff;}
.temp_item .img img{width: 100%;height: auto;max-height: 100%;}
.temp_item .title{height: 3rem;line-height: 3rem;padding: 0 0.5rem;}
.temp_item .info{padding: 0 0.5rem;height: 1.8rem;line-height: 1rem;}
.temp_item.active{background: #19afdc;}
.temp_item.active .title{color: #fff;}
.temp_item.active .info,.temp_item.active a{color: #c3ecf9;}
.temp_item.active a.delete{color: #ffeb00;}
</style>
<div class="me_body">
	<div class="me_head">
		<div class="me_input me_input_line">
		</div>
	</div>
	
	<ul class="temp_list clear">
		{foreach $list as $k=>$v}
			<li title="{$v['description']}" class="temp_item {if $tempDefault == $k}active{/if}" data-value="{$k}">
				<div class="img" title="点击切换模板">
					<img src="{$v['preview']}"/>
				</div>
				<p class="title">{$v['name']} {$v['version']}</p>
				<div class="info clear">
					{if !empty($v['author'])}
						<p class="left">作者：
						{if !empty($v['authorUrl'])}<a href="{$v['authorUrl']}" title="查看作者" target="_blank">{$v['author']}</a>{else}{$v['author']}{/if}
						</p>
					{/if}
					<p class="right">
					{if $v['setting']}
						<a href="{:url('temp/setting')}?temp={$k}" title="点击设置模板">设置</a>
					{/if}
						<a href="javascript:;" class="operBtn delete" title="点击删除模板">删除</a>
					</p>
				</div>
			</li>
		{/foreach}
	</ul>
</div>
<script>
$(document).ready(function(){
	$(".menu_tree").find(".menu_item[data-type='temp']").addClass('active');
	$(".temp_item .img").click(function(){
		var _this=$(this),
			a=_this.parents('.temp_item').data('value');
		$.ajaxpost("{:url('temp/upTemp')}",{'value':a},function(res){
			$.Msg(res.msg);
			res.code == 200 && setTimeout(function(){window.location.reload()},2200);
		})
	})
	$(".delete").click(function(){
		var a=$(this).parents('.temp_item').data('value');
		if(!a || !confirm('你确定要删除该模板吗？')){return !1;}
		$.ajaxpost("{:url('temp/dele')}",{'value':a},function(res){
			$.Msg(res.msg);
			res.code == 200 && setTimeout(function(){window.location.reload()},2200);
		})
	})
})
</script>
{include:/footer}