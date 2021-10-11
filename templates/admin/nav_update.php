<?php if (!defined('CMSPATH')){exit('error!');}?>
{include:/header}
<style>
.me_input input[type='text'],.me_input select{width: calc(100% - 2.5rem);}
.me_form .right.me_fixed{right: calc(2rem + 3px);}
</style>
<form class="me_form" action="" onSubmit="return false">
	<div style="width: 25rem;">
		<div class="me_input"><label>名称</label><input type="text" name="navname" value="{$info['navname']|default=''}"></div>
		<div class="me_input"><label>网址</label>
			{if $info['types'] != 4}
				<p style="line-height: 2.4rem;background: #dedede;float: left;padding: 0 2rem;">该导航由系统生成，无法修改地址</p>
			{else}
				<input type="text" name="url" value="{$info['url']|default=''}" placeholder="请带上http(s)://">
			{/if}
		</div>
		<div class="me_input"><label>上级</label><select name="topId">
			<option value="">无</option>
			{if $diyTop[0]['id'] != $id}
				{foreach $diyTop as $v}
					{if $v['id'] != $id}
						<option value="{$v['id']}" {if $v['id'] == $info['topId']}selected{/if}>{$v['navname']}</option>
					{/if}
				{/foreach}
			{/if}
		</select></div>
		<div class="me_input"><label>在新窗口打开</label><input type="checkbox" name="newtab" value="1" {if $info['newtab'] == 1}checked{/if}></div>
		<div class="rp_btn_row">
			<input type="hidden" name="id" value="{$id|default=''}"/>
			<button type="button" class="rp_btn success sendPost" style="width: 100%;">修改</button>
		</div>
	</div>
</form>
<script>
var id="{$id|default=''}";
var navType="{$info['types']|default=''}";
$(document).ready(function(){
	$(".menu_tree").find(".menu_item[data-type='nav']").addClass('active');
	$(".sendPost").click(function(){
		var b=$('.me_form').serializeArray(),
			param={'id':id};
		$.each(b, function(d,e){
			param[e.name] = e.value;
		});
		if(!param.navname){
			$.Msg("名称不能为空");return !1;
		}
		if(navType == 4 && !param.url){
			$.Msg("地址不能为空");return !1;
		}
		$.ajaxpost("{:url('nav/doUpdate')}",param,function(res){
			$.Msg(res.msg);
			res.code == 200 && setTimeout(function(){window.location.href="{:url('nav/index')}"},2200);
		});
	});
});
</script>
{include:/footer}