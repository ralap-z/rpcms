<?php if (!defined('CMSPATH')){exit('error!');}?>
{include:/header}
<style>
.sortInput{width: 3rem;padding: 0.2rem;}
.upSort{margin-top: 0.4rem;margin-left: 0;}
</style>
<div class="me_body">
	<div class="me_head">
		<div class="me_input me_input_line">
			<a href="javascript:;" class="rp_btn" data-model-type="links">添加友链</a>
		</div>
	</div>
	<table class="me_table">
		<colgroup>
			<col width="10%">
			<col width="20%">
			<col width="15%">
			<col width="15%">
			<col width="15%">
			<col width="10%">
			<col>
		</colgroup>
		<thead>
			<tr>
				<th>序号</th>
				<th>名称</th>
				<th>链接</th>
				<th>状态</th>
				<th>描述</th>
				<th>操作</th>
			</tr> 
		</thead>
		<tbody class="rowList">
			{foreach $list as $k=>$v}
				<tr data-id="{$v['id']}">
					<td><input type="text" name="cate[]" value="{$v['sort']}" class="sortInput" maxlength="4"></td>
					<td>{$v['sitename']}</td>
					<td><a href="{$v['siteurl']}" title="点击查看" target="_blank">{$v['siteurl']}</a></td>
					<td><span class="upStatus" data-value="{$v['status']}" title="点击修改状态">{if $v['status'] == 0}<font>显示</font>{elseif $v['status'] == 1}<font class="color3">待审</font>{else}<font class="color4">隐藏</font>{/if}</span></td>
					<td>{$v['sitedesc']}</td>
					<td>
						<a href="javascript:;" class="operBtn update">编辑</a>
						<a href="javascript:;" class="operBtn delete">删除</a>
					</td>
				</tr>
			{/foreach}
		</tbody>
	</table>
	<div class="rp_row clear">
		<button type="button" class="rp_btn upSort">更新排序</button>
	</div>
</div>
<div class="me_model me_anim_bounce me_model_links">
	<div class="title">添加友链</div>
	<a class="me-icon me-icon-close me_model_close" href="javascript:;" data-callback="reaset"></a>
	<div class="contentes">
		<form class="me_form addCate" action="" onSubmit="return false">
			<div class="me_input me_input_line"><label>序号</label><input type="text" class="sort" value="0"></div>
			<div class="me_input me_input_line"><label>名称</label><input type="text" class="sitename" value=""></div>
			<div class="me_input me_input_line"><label>地址</label><input type="text" class="siteurl" value="" placeholder="http(s)://"></div>
			<div class="me_input me_input_line"><label>状态</label>
				<label><input type="radio" name="status" value="0" checked>显示</label>
				<label><input type="radio" name="status" value="-1">隐藏</label>
			</div>
			<div class="me_input"><label>描述</label><textarea class="sitedesc" style="width: calc(100% - 6rem);"></textarea></div>
			<div class="rp_row">
				<button type="sumbit" class="rp_btn success sendPost_links">添加</button>
			</div>
		</form>
	</div>
</div>
<script>
$(document).ready(function(){
	$(".menu_tree").find(".menu_item[data-type='links']").addClass('active');
	$(".sendPost_links").click(function(){
		var param={
			'sort':$.trim($(".sort").val()) || 0,
			'sitename':$.trim($(".sitename").val()),
			'siteurl':$.trim($(".siteurl").val()),
			'status':$("input[name='status']:checked").val() || 0,
			'sitedesc':$.trim($(".sitedesc").val()),
		};
		if(!param.sitename){
			$.Msg("名称不能为空");return !1;
		}
		if(!param.siteurl || !$.checkform(param.siteurl,'url')){
			$.Msg("URL地址格式错误");return !1;
		}
		var updateId=$(".me_model_links").data('updateId') || '';
		updateId && (param.id=updateId);
		$.ajaxpost("{:url('links/dopost')}",param,function(res){
			$.Msg(res.msg);
			res.code == 200 && setTimeout(function(){window.location.reload()},2200);
		})
	})
	$(".upSort").click(function(){
		var param=[];
		$(".sortInput").each(function(a,b){
			param.push({'id':$(b).parents('tr').data('id'),'value':$.trim($(b).val())})
		})
		$.ajaxpost("{:url('links/upSort')}",{'data':param},function(res){
			$.Msg(res.msg);
			res.code == 200 && setTimeout(function(){window.location.reload()},2200);
		})
	})
	$(".upStatus").click(function(){
		var _this=$(this),
			a=_this.parents('tr').data('id'),
			b=_this.data('value');
		switch(b){
			case -1:
			case 1:
				var status=0;break;
			default:
				var status=-1;
		}
		$.ajaxpost("{:url('links/upStatus')}",{'id':a,'status':status},function(res){
			$.Msg(res.msg);
			res.code == 200 && setTimeout(function(){window.location.reload()},2200);
		})
	})
	$(".update").click(function(){
		var id=$(this).parents('tr').data('id'),
			box=$(".me_model_links");
		box.find(".title").text("修改友链"),box.find(".sendPost_links").text("保存修改"),$(".veil").show(),box.show();
		$.ajaxpost("{:url('links/getinfo')}",{'id':id},function(res){
			if(res.code == 200){
				var data=res.data;
				box.data('updateId',id),$(".sort").val(data.sort),$(".sitename").val(data.sitename),$(".siteurl").val(data.siteurl),$(".sitedesc").val(data.sitedesc);
				$("input[name='status'][value='"+data.status+"']").prop("checked", true);
			}else{
				$.Msg(res.msg);
			}
		})
	})

	$(".delete").click(function(){
		var id=$(this).parents('tr').data('id');
		if(!id || !confirm('你确定要删除该友链吗？')){return !1;}
		$.ajaxpost("{:url('links/dele')}",{'id':id},function(res){
			$.Msg(res.msg);
			res.code == 200 && setTimeout(function(){window.location.reload()},2200);
		})
	})
})
function reaset(){
	var box=$(".me_model_links");
	box.data('updateId','');
	box.find("input[type='text'],textarea").val(''),box.find("input[type='radio']:first").prop("checked", true);
	$(".sort").val(0);
}
</script>
{include:/footer}