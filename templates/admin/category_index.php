<?php if (!defined('CMSPATH')){exit('error!');}?>
{include:/header}
<style>
.sortInput{width: 3rem;padding: 0.2rem;}
.upSort{margin-top: 0.4rem;margin-left: 0;}
.addCate .me_input label{width: 5rem;text-align: right;}
.me_model .rp_row{text-align: right;}
</style>
<div class="me_body">
	<div class="me_head">
		<div class="me_input me_input_line">
			<a href="javascript:;" class="rp_btn navAdd" data-model-type="cate">添加分类</a>
		</div>
	</div>
	<table class="me_table">
		<colgroup>
			<col width="8%">
			<col width="8%">
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
				<th>ID</th>
				<th>名称</th>
				<th>别名</th>
				<th>列表模板</th>
				<th>内容模板</th>
				<th>文章数量</th>
				<th>操作</th>
			</tr> 
		</thead>
		<tbody class="rowList">
			{foreach $category as $k=>$v}
				{php}if($v['topId'] != 0){continue;}{/php}
				<tr data-id="{$v['id']}">
					<td><input type="text" name="cate[]" value="{$v['sort']}" class="sortInput" maxlength="4"></td>
					<td>{$v['id']}</td>
					<td><a href="{php}echo rp\Url::cate($v['id']){/php}" title="点击查看" target="_blank">{$v['cate_name']}</a></td>
					<td>{$v['alias']}</td>
					<td>{$v['temp_list']}</td>
					<td>{$v['temp_logs']}</td>
					<td>{$v['logNum']}</td>
					<td>
						<a href="javascript:;" class="operBtn update">编辑</a>
						<a href="javascript:;" class="operBtn delete">删除</a>
					</td>
				</tr>
				{foreach $v['children'] as $sk}
					{$sv = $category[$sk];}
					<tr data-id="{$sv['id']}">
						<td><input type="text" name="cate[{$sv['id']}]" value="{$sv['sort']}" class="sortInput" maxlength="4"></td>
						<td>{$sv['id']}</td>
						<td>└&nbsp;&nbsp;<a href="{php}echo rp\Url::cate($sv['id']){/php}" title="点击查看" target="_blank">{$sv['cate_name']}</a></td>
						<td>{$sv['alias']}</td>
						<td>{$sv['temp_list']}</td>
						<td>{$sv['temp_logs']}</td>
						<td>{$sv['logNum']}</td>
						<td>
							<a href="javascript:;" class="operBtn update">编辑</a>
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
<div class="me_model me_anim_bounce me_model_cate">
	<div class="title">添加分类</div>
	<a class="me-icon me-icon-close me_model_close" href="javascript:;" data-callback="reaset"></a>
	<div class="contentes">
		<form class="me_form addCate" action="" onSubmit="return false">
			<div class="me_input me_input_line"><label>序号</label><input type="text" class="cate_sort" value="0"></div>
			<div class="me_input me_input_line"><label>名称</label><input type="text" class="cate_name" value=""></div>
			<div class="me_input me_input_line"><label>别名</label><input type="text" class="cate_alias" value=""></div>
			<div class="me_input me_input_line"><label>上级</label><select class="cate_topId">
				<option value="0">无</option>
				{foreach $category as $v}
					{if $v['topId'] == 0}<option value="{$v['id']}">{$v['cate_name']}</option>{/if}
				{/foreach}
			</select></div>
			<div class="me_row">
				<div class="me_input me_input_line"><label>列表模板</label><select class="temp_list">{$tempFileHtml|raw}</select></div>
				<div class="me_input me_input_line"><label>内容模板</label><select class="temp_logs">{$tempFileHtml|raw}</select></div>
			</div>
			<div class="me_input"><label>SEO标题</label><input type="text" class="seo_title" value="" style="width: calc(100% - 6rem);"></div>
			<div class="me_input"><label>SEO关键词</label><input type="text" class="seo_key" value="" style="width: calc(100% - 6rem);"></div>
			<div class="me_input"><label>SEO描述</label><textarea class="seo_desc" style="width: calc(100% - 6rem);"></textarea></div>
			<div class="me_input"><label>支持投稿</label><input type="checkbox" class="is_submit" value="1"></div>
			<div class="rp_row">
				<button type="sumbit" class="rp_btn success sendPost_cate">添加</button>
			</div>
		</form>
	</div>
</div>
<script>
$(document).ready(function(){
	$(".menu_tree").find(".menu_item[data-type='category']").addClass('active');
	$(".sendPost_cate").click(function(){
		var param={
			'sort':$.trim($(".cate_sort").val()) || 0,
			'cate_name':$.trim($(".cate_name").val()),
			'alias':$.trim($(".cate_alias").val()),
			'topId':$.trim($(".cate_topId").val()),
			'seo_title':$.trim($(".seo_title").val()),
			'seo_key':$.trim($(".seo_key").val()),
			'seo_desc':$.trim($(".seo_desc").val()),
			'temp_list':$.trim($(".temp_list").val()),
			'temp_logs':$.trim($(".temp_logs").val()),
			'is_submit':$(".is_submit:checked").val() || 0,
		};
		if(!param.cate_name){
			$.Msg("名称不能为空");return !1;
		}
		if(param.alias && 0 != isalias(param.alias)){
			$.Msg("别名错误，应由字母、数字、短横线组成");return !1;
		}
		var type=$(".me_model_cate").data('postType') || 'add',
			updateId=$(".me_model_cate").data('updateId') || '',
			url=type == "update" ? "{:url('category/doUpdate')}" : "{:url('category/doAdd')}";
		updateId && (param.id=updateId);
		$.ajaxpost(url,param,function(res){
			$.Msg(res.msg);
			res.code == 200 && setTimeout(function(){window.location.reload()},2200);
		});
	}),
	$(".upSort").click(function(){
		var param=[];
		$(".sortInput").each(function(a,b){
			param.push({'id':$(b).parents('tr').data('id'),'value':$.trim($(b).val())});
		});
		$.ajaxpost("{:url('category/upSort')}",{'data':param},function(res){
			$.Msg(res.msg);
			res.code == 200 && setTimeout(function(){window.location.reload()},2200);
		});
	}),
	$(".delete").click(function(){
		var id=$(this).parents('tr').data('id');
		if(!id || !confirm('你确定要删除该分类吗？')){return !1;}
		$.ajaxpost("{:url('category/dele')}",{'id':id},function(res){
			$.Msg(res.msg);
			res.code == 200 && setTimeout(function(){window.location.reload()},2200);
		});
	}),
	$(".update").click(function(){
		var id=$(this).parents('tr').data('id'),
			box=$(".me_model_cate");
		box.data('postType','update'),box.find(".title").text("修改分类"),box.find(".sendPost_cate").text("保存修改"),$(".veil").show(),box.show();
		$.ajaxpost("{:url('category/getinfo')}",{'id':id},function(res){
			if(res.code == 200){
				var data=res.data;
				box.data('updateId',id),$(".cate_sort").val(data.sort),$(".cate_name").val(data.cate_name),$(".cate_alias").val(data.alias),$(".cate_topId").val(data.topId),$(".seo_title").val(data.seo_title),$(".seo_key").val(data.seo_key),$(".seo_desc").val(data.seo_desc),$(".temp_list").val(data.temp_list),$(".temp_logs").val(data.temp_logs);
				data.is_submit == 1 ? $(".is_submit").prop("checked", true) : $(".is_submit").prop("checked", false);
			}else{
				$.Msg(res.msg);
			}
		});
	});
});
function reaset(){
	var box=$(".me_model_cate");
	box.data('postType','add').data('updateId','');
	box.find("input[type='text'],textarea").val(''),box.find('select option:first').prop('selected', true),box.find("input[type='checkbox']").prop("checked", false);
	$(".cate_sort").val(0);
}
</script>
{include:/footer}