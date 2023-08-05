<?php if (!defined('CMSPATH')){exit('error!');}?>
{include:/header}
<style>
.sortInput{width: 3rem;padding: 0.2rem;}
.upSort{margin-top: 0.4rem;margin-left: 0;}
.addSpecial .me_input label{width: 5rem;text-align: right;}
.me_model .rp_row{text-align: right;}
.imgUpload:after{padding-bottom: 57%;}
</style>
<div class="me_body">
	<div class="me_head">
		<div class="me_input me_input_line">
			<a href="javascript:;" class="rp_btn navAdd" data-model-type="special">添加专题</a>
		</div>
	</div>
	<table class="me_table">
		<colgroup>
			<col width="8%">
			<col width="30%">
			<col width="15%">
			<col width="20%">
			<col width="10%">
			<col>
		</colgroup>
		<thead>
			<tr>
				<th>ID</th>
				<th>名称</th>
				<th>别名</th>
				<th>列表模板</th>
				<th>文章数量</th>
				<th>操作</th>
			</tr> 
		</thead>
		<tbody class="rowList">
			{foreach $special as $k=>$v}
				<tr data-id="{$v['id']}">
					<td>{$v['id']}</td>
					<td><a href="{php}echo rp\Url::special($v['id']){/php}" title="点击查看" target="_blank">{$v['title']}</a></td>
					<td>{$v['alias']}</td>
					<td>{$v['temp_list']}</td>
					<td>{$v['logNum']}</td>
					<td>
						<a href="javascript:;" class="operBtn update">编辑</a>
						<a href="javascript:;" class="operBtn delete">删除</a>
					</td>
				</tr>
			{/foreach}
		</tbody>
	</table>
</div>
<div class="me_model me_anim_bounce me_model_special">
	<div class="title">添加专题</div>
	<a class="me-icon me-icon-close me_model_close" href="javascript:;" data-callback="reaset"></a>
	<div class="contentes">
		<form class="me_form addSpecial" action="" onSubmit="return false">
			<div class="me_input me_input_line"><label>名称</label><input type="text" class="special_title" value=""></div>
			<div class="me_input me_input_line"><label>别名</label><input type="text" class="special_alias" value=""></div>
			<div class="me_input"><label>副标题</label><input type="text" class="special_subTitle" value="" style="width: calc(100% - 6rem);"></div>
			<div class="me_input me_input_line"><label>专题图片</label>
				<div class="imgUpload">
					<input type="file" accept="image/*" class="imgFile"/>
					<span>选择图片</span>
				</div>
			</div>
			<div class="me_input me_input_line" style="vertical-align: top;"><label>列表模板</label><select class="temp_list">{$tempFileHtml|raw}</select></div>
			<div class="me_input"><label>SEO标题</label><input type="text" class="seo_title" value="" style="width: calc(100% - 6rem);"></div>
			<div class="me_input"><label>SEO描述</label><textarea class="seo_desc" style="width: calc(100% - 6rem);"></textarea></div>
			<div class="rp_row">
				<button type="sumbit" class="rp_btn success sendPost_special">添加</button>
			</div>
		</form>
	</div>
</div>
<script>
$(document).ready(function(){
	$(".menu_tree").find(".menu_item[data-type='special']").addClass('active');
	$(".imgFile").change(function(){
		var a=$(this)[0].files[0];
		if($(".imgUpload").find("img").length > 0){
			$(".imgUpload").find("img").attr("src",window.URL.createObjectURL(a));
		}else{
			$(".imgUpload").append('<img src="'+(window.URL.createObjectURL(a))+'"/>');
		}
	}),
	$(".sendPost_special").click(function(){
		var param={
			'title':$.trim($(".special_title").val()),
			'alias':$.trim($(".special_alias").val()),
			'subTitle':$.trim($(".special_subTitle").val()),
			'seo_title':$.trim($(".seo_title").val()),
			'seo_desc':$.trim($(".seo_desc").val()),
			'temp_list':$.trim($(".temp_list").val()),
		};
		if(!param.title){
			$.Msg("名称不能为空");return !1;
		}
		if(param.alias && 0 != isalias(param.alias)){
			$.Msg("别名错误，应由字母、数字、短横线组成");return !1;
		}
		var type=$(".me_model_special").data('postType') || 'add',
			updateId=$(".me_model_special").data('updateId') || '',
			url=type == "update" ? "{:url('special/doUpdate')}" : "{:url('special/doAdd')}";
		var formData = new FormData();
		formData.append("title", param.title);
		formData.append("subTitle", param.subTitle);
		formData.append("alias", param.alias);
		formData.append("seo_title", param.seo_title);
		formData.append("seo_desc", param.seo_desc);
		formData.append("temp_list", param.temp_list);
		formData.append("headimg", $(".imgFile")[0].files[0]);
		updateId && formData.append("id", updateId);
		$.ajax({
			'url':url,
			'type':'post',
			'data':formData,
			'contentType': false,
			'processData': false,
			'dataType':'json',
			'mimeType': 'multipart/form-data',
			'beforeSend':function(){
				$.loading('正在加载...');
			},
			'success':function(res){
				$.Msg(res.msg);
				res.code == 200 && setTimeout(function(){window.location.reload()},2200);
			},
			'error':function(){
				$.Msg('服务端响应错误');
			}
		});
	}),
	$(".delete").click(function(){
		var id=$(this).parents('tr').data('id');
		if(!id || !confirm('你确定要删除该专题吗？')){return !1;}
		$.ajaxpost("{:url('special/dele')}",{'id':id},function(res){
			$.Msg(res.msg);
			res.code == 200 && setTimeout(function(){window.location.reload()},2200);
		});
	}),
	$(".update").click(function(){
		var id=$(this).parents('tr').data('id'),
			box=$(".me_model_special");
		box.data('postType','update'),box.find(".title").text("修改专题"),box.find(".sendPost_special").text("保存修改"),$(".veil").show(),box.show();
		$.ajaxpost("{:url('special/getinfo')}",{'id':id},function(res){
			if(res.code == 200){
				var data=res.data;
				box.data('updateId',id),$(".special_title").val(data.title),$(".special_subTitle").val(data.subTitle),$(".special_alias").val(data.alias),$(".seo_title").val(data.seo_title),$(".seo_desc").val(data.seo_desc),$(".temp_list").val(data.temp_list);
				data.headimg && ($(".imgUpload").find("img").remove(),$(".imgUpload").append('<img src="'+data.headimg+'"/>'));
			}else{
				$.Msg(res.msg);
			}
		});
	});
});
function reaset(){
	var box=$(".me_model_special");
	box.data('postType','add').data('updateId','');
	box.find("input[type='text'],textarea").val(''),box.find(".imgUpload img").remove();
}
</script>
{include:/footer}