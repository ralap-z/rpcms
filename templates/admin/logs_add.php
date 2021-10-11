<?php if (!defined('CMSPATH')){exit('error!');}?>
{include:/header}
<style>
.me_input > label{width:2rem;}
.me_input input[type='text'],.me_input input[type='number'],.me_input select{width: calc(100% - 2.5rem);}
.me_form .right.me_fixed{right: 2rem;}
.allTages{cursor: pointer;color: #1fbae8;}
.taglist{display: none;}
.taglist a{margin:0.2rem;padding: 0 0.3rem;color: #333;display: inline-block;*display:inline;*zoom:1;}
.taglist a.selected{background: #efff00;}
.taglist a:hover{color: #f40;}
#excerpt{display:none;}
.me_input .tips{margin-left: 1rem;color: #f40;}
</style>
<form class="me_form" action="" onSubmit="return false">
	<div class="left" style="width: calc(100% - 23.5rem);">
		<div class="me_input big"><label style="width: auto;">标题<font class="tips"></font></label><input type="text" name="title" value="{$logData['title']|default=''}"></div>
		<div class="me_input big"><label>标签</label><input type="text" name="tagesName" id="tagesName" value="{$logData['tagesName']|default=''}">
			<span class="inblock allTages">选择标签</span>
			<div class="taglist">加载中...</div>
		</div>
		<div class="me_input big extendBox">
			<span class="me_extend_menu" data-extend-box="attrBox">附件管理</span>
			<div class="me_extendBox_attrBox" style="display:none;">
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
			{hook:admin_logs_edit_hook($logData)}
		</div>
		<div class="me_input big"><label>正文</label><textarea name="content" id="log_content" style="height: 30rem;">{$logData['content']|default=''}</textarea></div>
		<div class="me_input big"><label style="width: auto;cursor: pointer;" id="excerptBtn">摘要<font style="color: #888;margin-left: 0.5rem;">系统会自动截取文字摘要，你也可以手动书写</font></label><textarea name="excerpt" id="excerpt" style="">{$logData['excerpt']|default=''}</textarea></div>
		<div class="me_input big"><label style="width: auto;">关键词<font class="tips"></font></label><input type="text" name="keywords" value="{$logData['keywords']|default=''}" onafterpaste="this.value=this.value.replace(/，/g,',')" onkeyup="this.value=this.value.replace(/，/g,',')"/></div>
		<div class="extendBox">{hook:admin_logs_edit_hook2($logData)}</div>
	</div>
	<div class="right fixed_tab" data-offset="3.2" data-unit="rem" style="width: 23.5rem;padding-left: 5rem;padding-top: 1rem;">
		<div class="me_input"><label>分类</label><select name="cateId">
			<option value="">选择分类</option>
			{$categoryHtml}
		</select></div>
		<div class="me_input"><label>作者</label><select name="authorId">{$authorHtml}</select></div>
		<div class="me_input"><label>专题</label><select name="specialId">{$specialHtml}</select></div>
		<div class="me_input"><label>别名</label><input type="text" name="alias" value="{$logData['alias']|default=''}" placeholder="仅字母、数字、-和_"></div>
		<div class="me_input"><label>密码</label><input type="text" name="password" value="{$logData['password']|default=''}"></div>
		<div class="me_input"><label>模板</label><input type="text" name="template" placeholder="指定模板请输入模板名称" value="{$logData['template']|default=''}"></div>
		<div class="me_input"><label>时间</label><input type="text" name="createTime" value="{$logData['createTime']|default=date('Y-m-d H:i:s')}"></div>
		
		<div class="me_input me_input_line"><label>置顶</label><input type="checkbox" name="isTop" value="1" {if $logData['isTop'] != 0}checked{/if}></div>
		<div class="me_input me_input_line"><label>评论</label><input type="checkbox" name="isRemark" value="1" {if $logData['isRemark'] != 0}checked{/if}></div>
		<div class="extendBox">{hook:admin_logs_edit_hook3($logData)}</div>
		<div class="rp_btn_row">
			<input type="hidden" name="tages" value="{$logData['tages']|default=''}"/>
			<input type="hidden" name="status" value="{$logData['status']|default=2}"/>
			<button type="button" class="rp_btn success sendPost" data-type="0">发布文章</button>
			{if $logData['status'] == 2}
				<button type="button" class="rp_btn sendPost" data-type="2">保存草稿</button>
			{else}
				<button type="button" class="rp_btn sendPost" data-type="3">保存</button>
			{/if}
		</div>
	</div>
</form>
<script src="{$cmspath}/static/editor/ueditor.config.js"></script>
<script src="{$cmspath}/static/editor/ueditor.all.min.js"></script>
<script>
var logid="{$logid|default=''}";
var logtype="{$logData['status']|default=2}";
var attrReload={'logid':logid};
var ispost=false;
$(document).ready(function(){
	$(".menu_tree").find(".menu_item[data-type='logs_add']").addClass('active');
	$.config({"ajaxPageDom":document}),$(".fixed_tab").fixedTab();
	bodyUE=UE.getEditor('log_content',{topOffset:$(".top").outerHeight(true)});
	$(document).click(function (e) {
		$('.taglist').slideUp("fast");
	});
	$(".allTages").click(function(e){
		e.stopPropagation();
		$('.taglist').slideDown("fast");
		$(".taglist").find("a").length <= 0 && $.ajaxpost('{:url("tages/getall")}',param,function(res){
			$(".taglist").html(res.data);
			$(".taglist").tagTo("#tagesName");
		});
	}),
	$("#excerptBtn").click(function(){
		$("#excerpt").slideToggle("fast");
	}),
	$(".uploadBtn").click(function(){
		var formData = new FormData(),
			file=$('.attrFile')[0].files[0];
		formData.append("files", file);
		formData.append("logid", logid);
		if(!file){
			$.Msg('请先选择文件');return !1;
		}
		$.ajax({
			'url':'{:url("logs/upload")}',
			'type':'post',
			'data':formData,
			'contentType': false,
			'processData': false,
			'dataType':'json',
			"mimeType": "multipart/form-data",
			'beforeSend':function(){
				$.loading('正在上传...');
			},
			'success':function(res){
				if(res.code == 200){
					$.Msg("上传成功");
					$(".attrFile").val('');delete formData;
					selectAttr(attrReload);
				}else{
					$.Msg(res.msg);
				}
			},
			'complete':function(){
				delete formData;
				$('.attrFile').val('');
			},
			'error':function(){
				$.Msg('请求服务器失败');
			}
		});
	}),
	$(".sendPost").click(sendPostFrom);
	logtype == 2 && setTimeout("sendPostFrom()", 60000);
	logid && selectAttr(attrReload);
});
function sendPostFrom(){
	if(ispost) return !1;
	ispost=true;
	var a='undefined' != typeof $(this).data("type")  ? $(this).data("type") : 2,
		b=$('.me_form').serializeArray(),
		c='undefined' !=  typeof $(this).data("type"),
		param={'type':a,'logid':logid, 'click':c};
	$.each(b, function(d,e){
		param[e.name] = e.value;
    });
	if(!param.title){
		ispost=false;
		c ? $.Msg("标题不能为空") : setTimeout("sendPostFrom()", 60000);
		return !1;
	}
	if(!param.content){
		ispost=false;
		c ? $.Msg("正文不能为空") : setTimeout("sendPostFrom()", 60000);
		return !1;
	}
	if(c && a != 2 && !param.cateId){
		ispost=false;
		c ? $.Msg("请选择分类") : setTimeout("sendPostFrom()", 60000);
		return !1;
	}
	if(param.alias && 0 != isalias(param.alias)){
		ispost=false;
		c ? $.Msg("别名错误，应由字母、数字、短横线组成") : setTimeout("sendPostFrom()", 60000);
		return !1;
	}
	!c && logtype ==  2 && $('.tips').text('正在保存');
	c && $.loading('正在保存数据...');
	$.post('{:url("logs/dopost")}',param,function(res){
		ispost=false;
		res.code == 200 && (logid=res.data,attrReload.logid=res.data);
		if(!c && logtype == 2){
			$('.tips').text('自动保存'+(res.code == 200 ? '' : '错误，')+res.msg);
			setTimeout("sendPostFrom()", 60000);
		}else{
			$.Msg(res.msg);
			res.code == 200 && setTimeout(function(){window.location.reload()},2200);
		}
	},"json");
}
function arrRemove(arr, rm) {
	for(var i = 0, n = 0; i < arr.length; ++i){
		if(arr[i] != rm) {
			arr[n++] = arr[i];
		}
	}
	arr.length--;
}
$.fn.tagTo = function(target, seperator, tclass) {
	target = target && $(target);
	seperator = seperator || ',';
	tclass = tclass || 'selected';
	var tagname = target.get(0).nodeName.toLowerCase();
	if(tagname == "input" || tagname == "textarea"){
		$('a', this).click(function(){
			var arr = target.val().split(seperator),
				text=$(this).data('text'),
				key=arr.indexOf(text);
			if(key >= 0){
				arrRemove(arr, arr[key]),$(this).removeClass(tclass);
			}else{
				arr.push(text),$(this).addClass(tclass);
			}
			arr=arr.filter(function(s){return s && s;});
			target.val(arr.join(seperator));
            return false;
        });
    }
}
</script>
{include:/footer}