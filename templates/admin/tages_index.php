<?php if (!defined('CMSPATH')){exit('error!');}?>
{include:/header}
<style>
.addTages .me_input label{width: 4rem;text-align: right;}
.me_model .rp_row{text-align: right;}
</style>
<div class="me_body">
	<div class="me_head">
		<div class="me_input me_input_line">
			<a href="javascript:;" class="rp_btn navAdd" data-model-type="tages">添加标签</a>
		</div>
	</div>
	<table class="me_table">
		<colgroup>
			<col width="10%">
			<col width="20%">
			<col width="20%">
			<col width="20%">
			<col width="10%">
			<col>
		</colgroup>
		<thead>
			<tr>
				<th>ID</th>
				<th>名称</th>
				<th>别名</th>
				<th>模板</th>
				<th>文章数量</th>
				<th>操作</th>
			</tr> 
		</thead>
		<tbody class="rowList">
			{foreach $list as $k=>$v}
				<tr data-id="{$v['id']}">
					<td>{$v['id']}</td>
					<td><a href="{php}echo rp\Url::tag($v['id']){/php}" title="点击查看" target="_blank">{$v['tagName']}</a></td>
					<td>{$v['alias']}</td>
					<td>{$v['template']}</td>
					<td>{$v['logNum']}</td>
					<td><a href="javascript:;" class="operBtn update">编辑</a><a href="javascript:;" class="operBtn delete">删除</a></td>
				</tr> 
			{/foreach}
		</tbody>
	</table>
	<div class="rp_row clear">
		<div class="right pages">{$pageHtml}</div>
	</div>
</div>
<div class="me_model me_anim_bounce me_model_tages">
	<div class="title">添加标签</div>
	<a class="me-icon me-icon-close me_model_close" href="javascript:;" data-callback="reaset"></a>
	<div class="contentes">
		<form class="me_form addTages" action="" onSubmit="return false">
			<div class="me_input me_input_line"><label>名称</label><input type="text" class="tagName" value=""></div>
			<div class="me_input me_input_line"><label>别名</label><input type="text" class="alias" value="" placeholder="大小写英文、数字、短横线组成；请勿和标签重名"></div>
			<div class="me_input me_input_line"><label>模板</label><input type="text" class="template" value=""></div>
			<div class="me_input"><label>SEO描述</label><textarea class="seo_desc" style="width: calc(100% - 4.5rem);"></textarea></div>
			<div class="rp_row">
				<button type="sumbit" class="rp_btn success sendPost_tages">添加</button>
			</div>
		</form>
	</div>
</div>
<script>
$(document).ready(function(){
	$(".menu_tree").find(".menu_item[data-type='tages']").addClass('active');
	$(".sendPost_tages").click(function(){
		var param={
			'tagName':$.trim($(".tagName").val()),
			'alias':$.trim($(".alias").val()),
			'template':$.trim($(".template").val()),
			'seo_desc':$.trim($(".seo_desc").val()),
		};
		if(!param.tagName){
			$.Msg("名称不能为空");return !1;
		}
		if(param.alias && 0 != isalias(param.alias)){
			$.Msg("别名错误，应由字母、数字、短横线组成");return !1;
		}
		var type=$(".me_model_tages").data('postType') || 'add',
			updateId=$(".me_model_tages").data('updateId') || '',
			url=type == "update" ? "{:url('tages/doUpdate')}" : "{:url('tages/doAdd')}";
		updateId && (param.id=updateId);
		$.ajaxpost(url,param,function(res){
			$.Msg(res.msg);
			res.code == 200 && setTimeout(function(){window.location.reload()},2200);
		})
	})
	$(".update").click(function(){
		var id=$(this).parents('tr').data('id'),
			box=$(".me_model_tages");
		box.data('postType','update'),box.find(".title").text("修改标签"),box.find(".sendPost_tages").text("保存修改"),$(".veil").show(),box.show();
		$.ajaxpost("{:url('tages/getinfo')}",{'id':id},function(res){
			if(res.code == 200){
				var data=res.data;
				box.data('updateId',id),$(".tagName").val(data.tagName),$(".alias").val(data.alias),$(".template").val(data.template),$(".seo_desc").val(data.seo_desc);
			}else{
				$.Msg(res.msg);
			}
		})
	})
	$(".delete").click(function(){
		var id=$(this).parents('tr').data('id');
		if(!id || !confirm('你确定要删除所选标签吗？')){return !1;}
		$.ajaxpost("{:url('tages/dele')}",{'id':id},function(res){
			$.Msg(res.msg);
			res.code == 200 && setTimeout(function(){window.location.reload()},2200);
		})
	})
})
function reaset(){
	var box=$(".me_model_tages");
	box.data('postType','add').data('updateId','');
	box.find("input[type='text'],textarea").val(''),box.find('select option:first').prop('selected', true),box.find("input[type='checkbox']").prop("checked", false);
}
</script>
{include:/footer}