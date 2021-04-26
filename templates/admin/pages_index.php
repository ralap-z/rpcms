<?php if (!defined('CMSPATH')){exit('error!');}?>
{include:/header}
<style>

</style>
<div class="subMenu">
	{hook:admin_pages_submenu_hook}
</div>
<div class="me_body">
	<div class="me_head">
		<div class="me_input me_input_line">
			<a href="{:url('pages/add')}" class="rp_btn">添加单页</a>
		</div>
	</div>
	<table class="me_table">
		<colgroup>
			<col width="5%">
			<col width="10%">
			<col width="35%">
			<col width="10%">
			<col width="8%">
			<col width="8%">
			<col>
		</colgroup>
		<thead>
			<tr>
				<th colspan="2">ID</th>
				<th>标题</th>
				<th>作者</th>
				<th>模板</th>
				<th>评论</th>
				<th>时间</th>
			</tr> 
		</thead>
		<tbody class="rowList">
			{foreach $list as $k=>$v}
				<tr>
					<td><input type="checkbox" name="blog[]" value="{$v['id']}" class="ids"></td>
					<td>{$v['id']}</td>
					<td><a href="{:url('pages/update')}?id={$v['id']}" title="修改单页">{$v['title']}</a></td>
					<td>{$v['nickname']}</td>
					<td>{$v['template']}</td>
					<td>{$v['comnum']}</td>
					<td>{$v['createTime']}</td>
				</tr> 
			{/foreach}
		</tbody>
	</table>
	<div class="rp_row clear">
		<div class="left" style="margin-top: 0.4rem;">
			<span class="inblock allCheck">全选</span>
			<span>选择项：</span>
			<span class="inblock oper_dele" onClick="javascript:logOper('dele');">删除</span>
		</div>
		<div class="right pages">{$pageHtml}</div>
	</div>
</div>
<script>
$(document).ready(function(){
	$(".menu_tree").find(".menu_item[data-type='pages']").addClass('active');
	$(".allCheck").toggleClick(function(){
		$(".ids").prop("checked", true);
	},function(){
		$(".ids").prop("checked", false);
	});
})

function logOper(type){
	var a=getChecked('ids');
	if(!a){
        $.Msg('请选择要操作的单页');return !1;
	}
	if(!confirm('你确定要删除所选单页吗？')){return !1;}
	$.ajaxpost("{:url('pages/dele')}",{"ids":a},function(res){
		$.Msg(res.msg);
		res.code == 200 && setTimeout(function(){window.location.reload()},2200);
	})
}
</script>
{include:/footer}