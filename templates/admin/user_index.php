<?php if (!defined('CMSPATH')){exit('error!');}?>
{include:/header}
<style>
.total span{margin-right: 1rem;}
.me_model_user{width: 25rem;}
.me_model_user .userTitle{width: 3rem;text-align-last: justify;}
</style>
<div class="me_body">
	<div class="me_head">
		<div class="me_input me_input_line">
			<a href="javascript:;" class="rp_btn userAdd" data-model-type="user">添加用户</a>
		</div>
		<form action="" class="me_input_line">
			<div class="me_input me_input_line">
				<select name="role">
					<option value="" {php}echo empty($s_role) ? 'selected' : '';{/php}>所有级别</option>
					{foreach $level as $lk=>$lv}
						<option value="{$lk}" {php}echo $s_role == $lk ? 'selected' : '';{/php}>{$lv}</option>
					{/foreach}
				</select>
			</div>
			<div class="me_input me_input_line">
				<select name="status">
					<option value="9" {php}echo $s_status == 9 ? 'selected' : '';{/php}>所有状态</option>
					<option value="0" {php}echo $s_status == 0 ? 'selected' : '';{/php}>正常</option>
					<option value="-1" {php}echo $s_status == -1 ? 'selected' : '';{/php}>禁用</option>
				</select>
			</div>
			<div class="me_input me_input_line">
				<button type="sumbit" class="rp_btn">搜索</button>
			</div>
		</form>
	</div>
	<table class="me_table">
		<colgroup>
			<col width="5%">
			<col width="10%">
			<col width="15%">
			<col width="20%">
			<col width="10%">
			<col width="25%">
			<col>
		</colgroup>
		<thead>
			<tr>
				<th colspan="2">ID</th>
				<th>级别</th>
				<th>名称</th>
				<th>状态</th>
				<th>统计</th>
				<th>操作</th>
			</tr> 
		</thead>
		<tbody class="rowList">
			{foreach $list as $k=>$v}
				<tr data-id="{$v['id']}">
					<td>{if $v['id'] != 1}<input type="checkbox" name="user[]" value="{$v['id']}" class="ids">{/if}</td>
					<td>{$v['id']}</td>
					<td>{$level[$v['role']]}</td>
					<td>{$v['username']}（{$v['nickname']}）</td>
					<td><span class="upStatus" data-value="{$v['status']}" title="点击修改状态">{if $v['status'] == 0}<font>正常</font>{else}<font class="color4">禁用</font>{/if}</span></td>
					<td class="total"><span>文章：{$v['logNum']|default=0}篇</span><span>单页：{$v['pageNum']|default=0}个</span><span>评论：{$v['commentNum']|default=0}条</span></td>
					<td>
						{if $v['id'] != 1}
						<a href="javascript:;" class="operBtn update">编辑</a>
						<a href="javascript:;" class="operBtn delete">删除</a>
						{/if}
					</td>
				</tr>
			{/foreach}
		</tbody>
	</table>
	<div class="rp_row clear">
		<div class="left" style="margin-top: 0.4rem;">
			<span class="inblock allCheck">全选</span>
			<span>选择项：</span>
			<div class="me_input me_input_line">
				<select id="oper_level" onchange="logOper('level');">
					<option value="">设置级别...</option>
					{foreach $level as $lk=>$lv}
						<option value="{$lk}">{$lv}</option>
					{/foreach}
				</select>
				<select id="oper_status" onchange="logOper('status');">
					<option value="">状态操作</option>
					<option value="0">正常</option>
					<option value="-1">禁用</option>
				</select>
			</div>
		</div>
		<div class="right pages">{$pageHtml}</div>
	</div>
</div>
<div class="me_model me_anim_bounce me_model_user">
	<div class="title">添加用户</div>
	<a class="me-icon me-icon-close me_model_close" href="javascript:;"  data-callback="reaset"></a>
	<div class="contentes">
		<form class="me_form" action="" onSubmit="return false">
			<div class="me_input"><label class="userTitle">用户名</label><input type="text" class="username" value=""></div>
			<div class="me_input"><label class="userTitle">密码</label><input type="text" class="password" value="" placeholder="至少6位"></div>
			<div class="me_input"><label class="userTitle">昵称</label><input type="text" class="nickname" value="" placeholder=""></div>
			<div class="me_input"><label class="userTitle">级别</label><select class="role">
				{foreach $level as $lk=>$lv}
					<option value="{$lk}">{$lv}</option>
				{/foreach}
			</select></div>
			<div class="me_input"><label class="userTitle">状态</label>
				<label><input type="radio" name="status" value="0" checked>正常</label>
				<label><input type="radio" name="status" value="-1">禁用</label>
			</div>
			<div class="me_input"><label>发布文章需要审核</label><input type="checkbox" class="isCheck" value="1"></div>
			<button type="sumbit" class="rp_btn success sendPost_useradd">添加</button>
		</form>
	</div>
</div>
<script>
$(document).ready(function(){
	$(".menu_tree").find(".menu_item[data-type='user']").addClass('active');
	$(".allCheck").toggleClick(function(){
		$(".ids").prop("checked", true);
	},function(){
		$(".ids").prop("checked", false);
	});
	$(".upStatus").click(function(){
		var _this=$(this),
			a=_this.parents('tr').data('id'),
			b=_this.data('value');
		$.ajaxpost("{:url('user/upStatus')}",{'id':a,'status':(b == 0 ? -1 : 0)},function(res){
			$.Msg(res.msg);
			res.code == 200 && setTimeout(function(){window.location.reload()},2200);
		});
	}),
	$(".sendPost_useradd").click(function(){
		var type=$(".me_model_user").data('postType') || 'add',
			updateId=$(".me_model_user").data('updateId') || '',
			url=type == "update" ? "{:url('user/doUpdate')}" : "{:url('user/doAdd')}";
		var param={
			'username':$.trim($(".username").val()),
			'password':$.trim($(".password").val()),
			'nickname':$.trim($(".nickname").val()),
			'role':$.trim($(".role").val()),
			'status':$("input[name='status']:checked").val() || 0,
			'isCheck':$(".isCheck:checked").val() || 0,
		};
		if(!param.username){
			$.Msg("用户名不能为空");return !1;
		}
		if(type != "update" && !param.password){
			$.Msg("密码不能为空");return !1;
		}
		if(param.password && (param.password).length < 6){
			$.Msg("密码至少6位");return !1;
		}
		updateId && (param.id=updateId);
		$.ajaxpost(url,param,function(res){
			$.Msg(res.msg);
			res.code == 200 && setTimeout(function(){window.location.reload()},2200);
		});
	}),
	$(".update").click(function(){
		var id=$(this).parents('tr').data('id'),
			box=$(".me_model_user");
		box.data('postType','update'),box.find(".title").text("修改用户"),box.find(".sendPost_useradd").text("保存修改"),$(".veil").show(),box.show();
		$.ajaxpost("{:url('user/getinfo')}",{'id':id},function(res){
			if(res.code == 200){
				var data=res.data;
				box.data('updateId',id),$(".username").val(data.username),$(".password").val('').attr('placeholder','为空则不修改'),$(".nickname").val(data.nickname),$(".role").val(data.role);
				$("input[name='status'][value='"+data.status+"']").prop("checked", true);
				data.isCheck == 1 ? $(".isCheck").prop("checked", true) : $(".isCheck").prop("checked", false);
			}else{
				$.Msg(res.msg);
			}
		});
	}),
	$(".delete").click(function(){
		var id=$(this).parents('tr').data('id');
		if(!id || !confirm('你确定要删除该用户吗？')){return !1;}
		$.ajaxpost("{:url('user/dele')}",{'id':id},function(res){
			$.Msg(res.msg);
			res.code == 200 && setTimeout(function(){window.location.reload()},2200);
		});
	});
});
function logOper(type){
	var a=getChecked('ids'),
		b='#oper_'+type;
	if(!a){
        $.Msg('请选择要操作的用户');return !1;
	}
	if(!$(b) || $(b).val() == ''){return !1;}
	$.ajaxpost("{:url('user/oper')}",{"type":type, "value":$(b).val(), "ids":a},function(res){
		$.Msg(res.msg);
		res.code == 200 && setTimeout(function(){window.location.reload()},2200);
	});
}
function reaset(){
	var box=$(".me_model_user");
	box.data('postType','add').data('updateId','');
	box.find("input[type='text'],textarea").val(''),box.find('select option:first').prop('selected', true),box.find("input[type='checkbox']").prop("checked", false),box.find("input[type='radio']:first").prop("checked", true);
}
</script>
{include:/footer}