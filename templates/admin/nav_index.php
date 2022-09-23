<?php if (!defined('CMSPATH')){exit('error!');}?>
{include:/header}
<style>
.navType{width: 30rem;}
.sortInput{width: 3rem;padding: 0.2rem;}
.upSort{margin-top: 0.4rem;margin-left: 0;}
</style>
<div class="me_body">
	<div class="me_head">
		<div class="me_input me_input_line">
			<a href="javascript:;" class="rp_btn navAdd" data-model-type="diy">添加自定义导航</a>
		</div>
		<div class="me_input me_input_line">
			<a href="javascript:;" class="rp_btn navAdd" data-model-type="cate">添加分类到导航</a>
		</div>
		<div class="me_input me_input_line">
			<a href="javascript:;" class="rp_btn navAdd" data-model-type="page">添加单页到导航</a>
		</div>
	</div>
	<table class="me_table">
		<colgroup>
			<col width="10%">
			<col width="15%">
			<col width="10%">
			<col width="10%">
			<col width="20%">
			<col>
		</colgroup>
		<thead>
			<tr>
				<th>序号</th>
				<th>名称</th>
				<th>类型</th>
				<th>状态</th>
				<th>地址</th>
				<th>操作</th>
			</tr> 
		</thead>
		<tbody class="rowList">
			{foreach $list as $k=>$v}
				{php}if($v['topId'] != 0){continue;}{/php}
				<tr data-id="{$v['id']}">
					<td><input type="text" name="nav[]" value="{$v['sort']}" class="sortInput" maxlength="4"></td>
					<td>{$v['navname']}</td>
					<td class="color{$v['types']}">{$types[$v['types']]}</td>
					<td><span class="upStatus" data-value="{$v['status']}" title="点击修改状态">{if $v['status'] == 0}<font>显示</font>{else}<font class="color4">隐藏</font>{/if}</span></td>
					<td><a href="{php}echo rp\Url::nav($v['types'],$v['typeId'],$v['url']){/php}" title="点击查看" target="_blank">{php}echo rp\Url::nav($v['types'],$v['typeId'],$v['url']){/php}</a></td>
					<td>
						<a href="{:url('nav/update')}?id={$v['id']}" class="operBtn">编辑</a>
						<a href="javascript:;" class="operBtn delete">删除</a>
					</td>
				</tr>
				{foreach $v['children'] as $sv}
				<tr data-id="{$sv['id']}">
					<td><input type="text" name="nav[{$sv['id']}]" value="{$sv['sort']}" class="sortInput" maxlength="4"></td>
					<td>└&nbsp;&nbsp;{$sv['navname']}</td>
					<td class="color{$sv['types']}">{$types[$sv['types']]}</td>
					<td><span class="upStatus" data-value="{$sv['status']}" title="点击修改状态">{if $sv['status'] == 0}<font>显示</font>{else}<font class="color4">隐藏</font>{/if}</span></td>
					<td><a href="{php}echo rp\Url::nav($sv['types'],$sv['typeId'],$sv['url']){/php}" title="点击查看" target="_blank">{php}echo rp\Url::nav($sv['types'],$sv['typeId'],$sv['url']){/php}</a></td>
					<td>
						<a href="{:url('nav/update')}?id={$sv['id']}" class="operBtn">编辑</a>
						<a href="javascript:;" class="operBtn delete">删除</a>
					</td>
				</tr>
				{/foreach}
			{/foreach}
		</tbody>
	</table>
	<div class="rp_row clear">
		<button type="button" class="rp_btn upSort">更新排序</button>
	</div>
</div>
<div class="me_model me_anim_bounce navType me_model_diy">
	<div class="title">添加自定义导航</div>
	<a class="me-icon me-icon-close me_model_close" href="javascript:;"></a>
	<div class="contentes">
		<form class="me_form" action="" onSubmit="return false">
			<div class="me_input"><label>序号</label><input type="text" class="diy_sort" value=""></div>
			<div class="me_input"><label>名称</label><input type="text" class="diy_navname" value=""></div>
			<div class="me_input"><label>网址</label><input type="text" class="diy_url" value="" placeholder="请带上http(s)://"></div>
			<div class="me_input"><label>上级</label><select class="diy_topId">
				<option value="">无</option>
				{foreach $diyTop as $v}
				<option value="{$v['id']}">{$v['navname']}</option>
				{/foreach}
			</select></div>
			<div class="me_input"><label>在新窗口打开</label><input type="checkbox" class="diy_newtab" value="1"></div>
			<button type="sumbit" class="rp_btn success sendPost_diy">添加</button>
		</form>
	</div>
</div>
<div class="me_model me_anim_bounce navType me_model_cate">
	<div class="title">添加分类到导航</div>
	<a class="me-icon me-icon-close me_model_close" href="javascript:;"></a>
	<div class="contentes">
		<div class="me_input"><label>上级</label><select class="diy_cateTopId">
			<option value="">无</option>
			{foreach $diyTop as $v}
			<option value="{$v['id']}">{$v['navname']}</option>
			{/foreach}
		</select></div>
		{$cateCheckbox|raw}
		<button type="sumbit" class="rp_btn success sendPost_cate">添加</button>
	</div>
</div>
<div class="me_model me_anim_bounce navType me_model_page">
	<div class="title">添加单页到导航</div>
	<a class="me-icon me-icon-close me_model_close" href="javascript:;"></a>
	<div class="contentes">
		<div class="me_input"><label>上级</label><select class="diy_pageTopId">
			<option value="">无</option>
			{foreach $diyTop as $v}
			<option value="{$v['id']}">{$v['navname']}</option>
			{/foreach}
		</select></div>
		{foreach $pages as $v}
			<div class="me_input"><input type="checkbox" name="pages[]" value="{$v['id']}" class="page_ids"/><label>{$v['title']}</label></div>
		{/foreach}
		<button type="sumbit" class="rp_btn success sendPost_page">添加</button>
	</div>
</div>
<script>
$(document).ready(function(){
	$(".menu_tree").find(".menu_item[data-type='nav']").addClass('active');
	$(".sendPost_diy").click(function(){
		var param={
			'sort':$.trim($(".diy_sort").val()) || 0,
			'navname':$.trim($(".diy_navname").val()),
			'url':$.trim($(".diy_url").val()),
			'topId':$.trim($(".diy_topId").val()),
			'newtab':$(".diy_newtab:checked").val() || 0,
		};
		if(!param.navname){
			$.Msg("名称不能为空");return !1;
		}
		if(!param.url){
			$.Msg("地址不能为空");return !1;
		}
		sendPost("{:url('nav/addDiy')}",param);
	}),
	$(".sendPost_cate").click(function(){
		var ids=[];
		$(".me_cate_ids").each(function(a,b){
			$(b).is(":checked") && ids.push($(b).val());
		});
		var param={'topId':$.trim($(".diy_cateTopId").val()),'ids':ids.join(',')};
		ids.length > 0 && sendPost("{:url('nav/addCate')}",param);
	}),
	$(".sendPost_page").click(function(){
		var ids=[];
		$(".page_ids").each(function(a,b){
			$(b).is(":checked") && ids.push($(b).val());
		});
		var param={'topId':$.trim($(".diy_pageTopId").val()),'ids':ids.join(',')};
		ids.length > 0 && sendPost("{:url('nav/addPage')}",param);
	}),
	$(".upStatus").click(function(){
		var _this=$(this),
			a=_this.parents('tr').data('id'),
			b=_this.data('value');
		$.ajaxpost("{:url('nav/upStatus')}",{'id':a,'status':(b == 0 ? 1 : 0)},function(res){
			$.Msg(res.msg);
			res.code == 200 && setTimeout(function(){window.location.reload()},2200);
		});
	}),
	$(".upSort").click(function(){
		var param=[];
		$(".sortInput").each(function(a,b){
			param.push({'id':$(b).parents('tr').data('id'),'value':$.trim($(b).val())})
		});
		$.ajaxpost("{:url('nav/upSort')}",{'data':param},function(res){
			$.Msg(res.msg);
			res.code == 200 && setTimeout(function(){window.location.reload()},2200);
		});
	}),
	$(".delete").click(function(){
		var id=$(this).parents('tr').data('id');
		if(!id || !confirm('你确定要删除所选单页吗？')){return !1;}
		$.ajaxpost("{:url('nav/dele')}",{'id':id},function(res){
			$.Msg(res.msg);
			res.code == 200 && setTimeout(function(){window.location.reload()},2200);
		});
	});
});
function sendPost(url,param){
	$.ajaxpost(url,param,function(res){
		$.Msg(res.msg);
		res.code == 200 && setTimeout(function(){window.location.reload()},2200);
	});
}
</script>
{include:/footer}