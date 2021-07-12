<?php if (!defined('CMSPATH')){exit('error!');}?>
{include:/header}
<style>
.me_input input[type='text'],.me_input input[type='number'],.me_input select{width: calc(100% - 2.5rem);}
.me_form .right.me_fixed{right: 2rem;}
</style>
<form class="me_form" action="" onSubmit="return false">
	<div class="left" style="width: calc(100% - 23.5rem);">
		<div class="me_input big"><label style="width: auto;">标题</label><input type="text" name="title" value="{$pageData['title']|default=''}"></div>
		<div class="me_input big extendBox">
			<span class="me_extend_menu" data-extend-box="attrBox">附件管理</span>
			<div class="me_extend_attrBox" style="display:none;">
				<div class="tab_list" style="width: 14rem;">
					<a href="#tab1" class="tab_link active">上传</a>
					<a href="#tab2" class="tab_link">附件库(<i class="attrNums">0</i>)</a>
				</div>
				<div class="tabs">
					<div id="tab1" class="tab active">
						<input type="file" name="attrFile" class="attrFile" style="width: 15rem;"/>
						<button type="button" class="rp_btn uploadBtn">上传</button>
						<p style="font-size: 0.6rem;color: #7d7d7d;">类型：{RP.fileTypes}，单文件大小：{RP.fileSize}MB</p>
					</div>
					<div id="tab2" class="tab attr_list"></div>
				</div>
			</div>
			{hook:admin_pages_edit_hook($pageData)}
		</div>
		<div class="me_input big"><label>内容</label><textarea name="content" id="page_content" style="height: 30rem;">{$pageData['content']|default=''}</textarea></div>
		<div class="me_input big"><label>SEO关键词</label><input type="text" name="seo_key" value="{$pageData['seo_key']|default=''}"></div>
		<div class="me_input big"><label>SEO描述</label><textarea name="seo_desc">{$pageData['seo_desc']|default=''}</textarea></div>
		<div class="extendBox">{hook:admin_pages_edit_hook2($pageData)}</div>
	</div>
	<div class="right fixed_tab" data-offset="3.2" data-unit="rem" style="width: 23.5rem;padding-left: 5rem;padding-top: 1rem;">
		<div class="me_input"><label>作者</label><select name="authorId">{$authorHtml}</select></div>
		<div class="me_input"><label>别名</label><input type="text" name="alias" value="{$pageData['alias']|default=''}"></div>
		<div class="me_input"><label>密码</label><input type="text" name="password" value="{$pageData['password']|default=''}"></div>
		<div class="me_input"><label>模板</label><input type="text" name="template" placeholder="指定模板请输入模板名称" value="{$pageData['template']|default=''}"></div>
		<div class="me_input"><label>时间</label><input type="text" name="createTime" value="{$pageData['createTime']|default=date('Y-m-d H:i:s')}"></div>
		<div class="me_input"><label>评论</label><input type="checkbox" name="isRemark" value="1" {if $pageData['isRemark'] != 0}checked{/if}></div>
		<div class="extendBox">{hook:admin_pages_edit_hook3($pageData)}</div>
		<div class="rp_btn_row">
			<button type="button" class="rp_btn success sendPost" style="width: 100%;">保存</button>
		</div>
	</div>
</form>
<script src="{$cmspath}/static/editor/ueditor.config.js"></script>
<script src="{$cmspath}/static/editor/ueditor.all.min.js"></script>
<script>
var pageId="{$pageId|default=''}";
var attrReload={'pageId':pageId};
$(document).ready(function(){
	$(".menu_tree").find(".menu_item[data-type='pages']").addClass('active');
	$.config({"ajaxPageDom":document}),$(".fixed_tab").fixedTab();
	bodyUE=UE.getEditor('page_content',{topOffset:$(".top").outerHeight(true)});
	$(".uploadBtn").click(function(){
		var formData = new FormData(),
			file=$('.attrFile')[0].files[0];
		formData.append("files", file);
		formData.append("pageId", pageId);
		if(!file){
			$.Msg('请先选择文件');return !1;
		}
		$.ajax({
			'url':'{:url("pages/upload")}',
			'type':'post',
			'data':formData,
			'contentType': false,
			'processData': false,
			'dataType':'json',
			"mimeType": "multipart/form-data",
			'success':function(res){
				if(res.code == 200){
					$(".attrFile").val('');delete formData;
					selectAttr(attrReload);
				}else{
					$.Msg(res.msg);
				}
			},
			'error':function(){
				$.Msg('请求服务器失败');
			}
		})
	})
	pageId && selectAttr(attrReload);
	$(".sendPost").click(sendPostFrom);
})
function sendPostFrom(){
	var a=$('.me_form').serializeArray(),
		param={'pageId':pageId};
	$.each(a, function(d,e){
		param[e.name] = e.value;
    });
	if(!param.title){
		$.Msg("标题不能为空");return !1;
	}
	if(!param.content){
		$.Msg("正文不能为空");return !1;
	}
	if(param.alias && 0 != isalias(param.alias)){
		$.Msg("别名错误，应由字母、数字、短横线组成");return !1;
	}
	$.loading('正在保存数据...');
	$.post('{:url("pages/dopost")}',param,function(res){
		if(attrClick){
			pageId=res.data || '';
			attrReload={'pageId':pageId};
		}else{
			$.Msg(res.msg);
			res.code == 200 && setTimeout(function(){window.location.reload()},2200);
		}
	},"json");
}
</script>
{include:/footer}