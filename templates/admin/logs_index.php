<?php if (!defined('CMSPATH')){exit('error!');}?>
{include:/header}
<style>
.rp_row .me_input_line{margin-bottom:0;}
.order{margin-left: 5px;font-weight: bold;color: #ccc;cursor: pointer;}
.order.active{color: #1fbae8;}
</style>
<div class="subMenu">
	{hook:admin_logs_submenu_hook}
</div>
<div class="me_body">
	<div class="me_head">
		<form action="">
			<div class="me_input me_input_line">
				<select name="cateId">
					<option value="">所有分类</option>
					{$categoryHtml|raw}
				</select>
			</div>
			<div class="me_input me_input_line">
				<select name="status">
					<option value="9" {php}echo $s_status == 9 ? 'selected' : '';{/php}>所有状态</option>
					<option value="0" {php}echo $s_status == 0 ? 'selected' : '';{/php}>公开</option>
					<option value="1" {php}echo $s_status == 1 ? 'selected' : '';{/php}>审核</option>
					<option value="2" {php}echo $s_status == 2 ? 'selected' : '';{/php}>草稿</option>
					<option value="-1" {php}echo $s_status == -1 ? 'selected' : '';{/php}>下架</option>
					<option value="-2" {php}echo $s_status == -2 ? 'selected' : '';{/php}>不通过</option>
				</select>
			</div>
			<div class="me_input me_input_line">
				<input type="text" name="key" autocomplete="off" placeholder="搜索文章" style="float: none;width: 20rem;" value="{$s_key}">
			</div>
			<div class="me_input me_input_line">
				<button type="sumbit" class="rp_btn">搜索</button>
			</div>
		</form>
	</div>
	<table class="me_table">
		<colgroup>
			<col width="5%">
			<col width="6%">
			<col width="35%">
			<col width="10%">
			<col width="10%">
			<col width="11%">
			<col width="8%">
			<col>
		</colgroup>
		<thead>
			<tr>
				<th colspan="2">ID</th>
				<th>标题</th>
				<th>分类</th>
				<th>作者</th>
				<th>评论<i class="me-icon order {$s_sort == 'comnum' ? 'active ' : ' '}{$s_sort == 'comnum' && $s_order == 'asc' ? 'me-icon-up' : 'me-icon-down'}" data-sort="comnum" data-order="{$s_order|default='desc'}"></i>/点赞<i class="me-icon order {$s_sort == 'upnum' ? 'active ' : ''}{$s_sort == 'upnum' && $s_order == 'asc' ? 'me-icon-up' : 'me-icon-down'}" data-sort="upnum" data-order="{$s_order|default='desc'}"></i></th>
				<th>阅读<i class="me-icon order {$s_sort == 'views' ? 'active ' : ''}{$s_sort == 'views' && $s_order == 'asc' ? 'me-icon-up' : 'me-icon-down'}" data-sort="views" data-order="{$s_order|default='desc'}"></i></th>
				<th>时间</th>
			</tr> 
		</thead>
		<tbody class="rowList">
			{foreach $list as $k=>$v}
				<tr>
					<td><input type="checkbox" name="blog[]" value="{$v['id']}" class="ids"></td>
					<td>{$v['id']}</td>
					<td>
						{php}
							switch($v['status']){
								case -2:$text='失败';$tstyle='color:#f40';break;
								case -1:$text='下';$tstyle='color:#f40';break;
								case 1:$text='审';$tstyle='color:#ff8d00';break;
								case 2:$text='草';$tstyle='color:#008000';break;
								default:$text='';$tstyle='';
							}
							echo !empty($text) ? '<span class="tag" style="'.$tstyle.'">['.$text.']</span>' : '';
						{/php}
						<a href="{:url('logs/update')}?id={$v['id']}" title="修改文章">{$v['title']}</a>{$v['isTop'] == 1 ? '<span class="badge">顶</span>' : ''}
					</td>
					<td>{if !empty($v['cate_name'])}{$v['cate_name']}{else}未分类{/if}</td>
					<td>{$v['nickname']}</td>
					<td>{$v['comnum']} / {$v['upnum']}</td>
					<td>{$v['views']}</td>
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
			<div class="me_input me_input_line">
				<select id="oper_move" onchange="logOper('move');">
					<option value="">移动到...</option>
					{$categoryHtml}
				</select>
				<select id="oper_top" onchange="logOper('top');">
					<option value="">置顶操作</option>
					<option value="1">置顶</option>
					<option value="0">取消</option>
				</select>
				<select id="oper_status" onchange="logOper('status');">
					<option value="">状态设置为...</option>
					<option value="0">公开</option>
					<option value="1">审核</option>
					<option value="2">草稿</option>
					<option value="-1">下架</option>
					<option value="-2">不通过</option>
				</select>
			</div>
		</div>
		<div class="right pages">{$pageHtml|raw}</div>
	</div>
</div>
<script>
$(document).ready(function(){
	$(".menu_tree").find(".menu_item[data-type='logs_list']").addClass('active');
	$(".allCheck").toggleClick(function(){
		$(".ids").prop("checked", true);
	},function(){
		$(".ids").prop("checked", false);
	});
	$(".order").click(function(){
		var sort=$(this).data("sort"),
			order=$(this).data("order"),
			url=window.location.href,
			reSort = new RegExp("([?&])sort=.*?(&|$)", "i"),
			reOrder = new RegExp("([?&])order=.*?(&|$)", "i");
		if($(this).hasClass("active")){
			order=order=='asc' ? 'desc' : 'asc';
			$(this).data('order',order);
		}
		if(url.match(reSort)){
			url=url.replace(reSort, "$1sort="+sort+"$2");
		}else{
			url+=(url.indexOf('?') !== -1 ? "&" : "?")+"sort="+sort;
		}
		if(url.match(reOrder)){
			url=url.replace(reOrder, "$1order="+order+"$2");
		}else{
			url+=(url.indexOf('?') !== -1 ? "&" : "?")+"order="+order;
		}
		window.location.href=url;
	});
});
function logOper(type){
	var a=getChecked('ids'),
		b='#oper_'+type;
	if(!a){
        $.Msg('请选择要操作的文章');return !1;
	}
	if(type == 'dele' && !confirm('你确定要删除所选文章吗？')){return !1;}
	if(!$(b) || $(b).val() == ''){return !1;}
	$.ajaxpost("{:url('logs/oper')}",{"type":type, "value":$(b).val(), "ids":a},function(res){
		$.Msg(res.msg);
		res.code == 200 && setTimeout(function(){window.location.reload()},2200);
	});
}
</script>
{include:/footer}