<?php if (!defined('CMSPATH')){exit('error!');}?>
{include:/header}
<style>
.replayForm .me_input label{width: 3rem;text-align: right;}
.me_model .rp_row{text-align: right;}
.exam{color:#f40;}
</style>
<div class="subMenu">
	{hook:admin_comment_submenu_hook}
</div>
<div class="me_body">
	<table class="me_table">
		<colgroup>
			<col width="5%">
			<col width="8%">
			<col width="8%">
			<col width="30%">
			<col width="18%">
			<col width="20%">
			<col>
		</colgroup>
		<thead>
			<tr>
				<th colspan="2">ID</th>
				<th>上级</th>
				<th>内容</th>
				<th>评论者</th>
				<th>文章</th>
				<th>操作</th>
			</tr> 
		</thead>
		<tbody class="rowList">
			{foreach $list as $k=>$v}
				<tr data-id="{$v['id']}">
					<td><input type="checkbox" name="comment[]" value="{$v['id']}" class="ids"></td>
					<td>{$v['id']}</td>
					<td>{$v['topId'] ? $v['topId'] : ''}</td>
					<td>
						{if $v['status'] == 1}<span class="exam">[审]</span>{/if}
						<a href="javascript:;" title="快速回复" data-id="{$v['id']}" class="replay">{$v['content']|htmlspecialchars|subString=###,0,50}</a>
						<p class="commentTime">{$v['createTime']|formatDate=###,3}</p>
					</td>
					<td>
						<a href="javascript:;" title="{$v['home']}">{$v['nickname']}</a>
						{if !empty($v['email'])}<span class="email">({$v['email']})</span>{/if}
						<p>IP：{$v['ip']}</p>
					</td>
					<td>
						{if !empty($v['logTitle'])}
							<a href="{php}echo rp\Url::logs($v['logId']){/php}" title="点击查看该文章" target="_blank">{$v['logTitle']}</a>
						{else}
							<a href="{php}echo rp\Url::page($v['pageId']){/php}" title="点击查看该单页" target="_blank">{$v['pagesTitle']}</a>
						{/if}
					</td>
					<td><a href="javascript:;" class="operBtn update">编辑</a><a href="javascript:;" class="operBtn delete">删除</a></td>
				</tr> 
			{/foreach}
		</tbody>
	</table>
	<div class="rp_row clear">
		<div class="left" style="margin-top: 0.4rem;">
			<span class="inblock allCheck">全选</span>
			<span>选择项：</span>
			<span class="inblock oper_dele" onClick="javascript:logOper('dele');">删除</span>
			<span class="inblock oper_dele" onClick="javascript:logOper('exam');">审核</span>
			<span class="inblock oper_dele" onClick="javascript:logOper('unexam');">反审</span>
		</div>
		<div class="right pages">{$pageHtml}</div>
	</div>
</div>

<div class="me_model me_anim_bounce me_model_replay">
	<div class="title">回复评论</div>
	<a class="me-icon me-icon-close me_model_close" href="javascript:;"></a>
	<div class="contentes">
		<form class="me_form replayForm" action="" onSubmit="return false">
			<div class="me_input me_input_line"><label>评论者</label><span class="text nickname"></span></div>
			<div class="me_input me_input_line"><label>时间</label><span class="text createTime"></span></div>
			<div class="me_input"><label>内容</label><p class="longText commentContent"></p></div>
			<div class="me_input"><label>回复</label><textarea class="replayBody" style="width: calc(100% - 3.5rem);"></textarea></div>
			<div class="rp_row">
				<p class="commentTips" style="margin-bottom: 0.5rem;color: #f40;"><p>
				<input type="hidden" class="replayId" value=""/>
				<button type="sumbit" class="rp_btn success sendPost_replay">回复</button>
			</div>
		</form>
	</div>
</div>
<div class="me_model me_anim_bounce me_model_update">
	<div class="title">编辑评论</div>
	<a class="me-icon me-icon-close me_model_close" href="javascript:;"></a>
	<div class="contentes">
		<form class="me_form replayForm" action="" onSubmit="return false">
			<div class="me_input me_input_line"><label>评论者</label><input type="text" class="up_nickname" value=""/></div>
			<div class="me_input me_input_line"><label>email</label><input type="text" class="up_email" value=""/></div>
			<div class="me_input me_input_line"><label>主页</label><input type="text" class="up_home" value=""/></div>
			<div class="me_input"><label>回复</label><textarea class="up_content" style="width: calc(100% - 3.5rem);"></textarea></div>
			<div class="rp_row">
				<input type="hidden" class="up_id" value=""/>
				<button type="sumbit" class="rp_btn success sendPost_upate">保存修改</button>
			</div>
		</form>
	</div>
</div>
<script>
$(document).ready(function(){
	$(".menu_tree").find(".menu_item[data-type='comment']").addClass('active');
	$(".allCheck").toggleClick(function(){
		$(".ids").prop("checked", true);
	},function(){
		$(".ids").prop("checked", false);
	});
	$(".replay").click(function(){
		var a=$(this).data('id');
		a && ($(".veil").show(),$(".me_model_replay").show(),$.ajaxpost("{:url('comment/getInfo')}",{"id":a},function(res){
			var data=res.data;
			$(".replayId").val(a),$(".nickname").text(data.nickname),$(".createTime").text(data.createTime),$(".commentContent").text(data.content),$(".commentTips").text(data.status == 1 ? "该评论未审核，提交回复将自动审核" : "");
		}));
	}),
	$(".sendPost_replay").click(function(){
		var param={
			"id":$(".replayId").val(),
			"content":$.trim($(".replayBody").val()),
		};
		if(!param.id){
			$.Msg('数据错误，请刷新重试');return !1;
		}
		if(!param.content){
			$.Msg('回复内容不能为空');return !1;
		}
		$.ajaxpost("{:url('comment/replay')}",param,function(res){
			$.Msg(res.msg);
			res.code == 200 && setTimeout(function(){window.location.reload()},2200);
		});
	}),
	$(".sendPost_upate").click(function(){
		var param={
			"id":$(".up_id").val(),
			"nickname":$.trim($(".up_nickname").val()),
			"email":$.trim($(".up_email").val()),
			"home":$.trim($(".up_home").val()),
			"content":$.trim($(".up_content").val()),
		};
		if(!param.id){
			$.Msg('数据错误，请刷新重试');return !1;
		}
		if(!param.nickname){
			$.Msg('评论人不能为空');return !1;
		}
		if(!param.content){
			$.Msg('评论内容不能为空');return !1;
		}
		$.ajaxpost("{:url('comment/doUpdate')}",param,function(res){
			$.Msg(res.msg);
			res.code == 200 && setTimeout(function(){window.location.reload()},2200);
		});
	}),
	$(".update").click(function(){
		var id=$(this).parents("tr").data("id");
		id && ($(".veil").show(),$(".me_model_update").show(),$.ajaxpost("{:url('comment/getInfo')}",{"id":id},function(res){
			var data=res.data;
			$(".up_id").val(id),$(".up_nickname").val(data.nickname),$(".up_email").val(data.email),$(".up_home").val(data.home),$(".up_content").val(data.content);
		}));
	}),
	$(".delete").click(function(){
		var id=$(this).parents("tr").data("id");
		if(!id || !confirm('你确定要删除该评论吗？')){return !1;}
		$.ajaxpost("{:url('comment/oper')}",{"type":"dele", "ids":id},function(res){
			$.Msg(res.msg);
			res.code == 200 && setTimeout(function(){window.location.reload()},2200);
		});
	});
});
function logOper(type){
	var a=getChecked('ids');
	if(!a){
        $.Msg('请选择要操作的评论');return !1;
	}
	if(type == 'dele' && !confirm('你确定要删除所选评论吗？')){return !1;}
	$.ajaxpost("{:url('comment/oper')}",{"type":type, "ids":a},function(res){
		$.Msg(res.msg);
		res.code == 200 && setTimeout(function(){window.location.reload()},2200);
	});
}
</script>
{include:/footer}