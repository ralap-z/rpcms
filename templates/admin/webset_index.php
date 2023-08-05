<?php if (!defined('CMSPATH')){exit('error!');}?>
{include:/header}
<style>
.me_form{width: 50%;}
.sendPost{position: fixed;top: 5.2rem;right: 2rem;width: 15rem;height: 2.6rem;line-height: 2.6rem;}
</style>
<div style="width:100%;background:#fff;position: relative;">
	<form class="me_form" action="" onSubmit="return false">
		<div class="tab_list">
			<a href="#tab1" class="tab_link active">基本设置</a>
			<a href="#tab2" class="tab_link">优化设置</a>
			<a href="#tab3" class="tab_link">API/其他</a>
			<a href="#tab4" class="tab_link">评论</a>
		</div>
		<div class="tabs">
			<div id="tab1" class="tab active">
				<div class="me_input big"><label>网站名称</label><input type="text" name="webName" value="{$option['webName']|default=''}"></div>
				<div class="me_input big"><label>网站LOGO</label>
					<div class="imgUpload">
						<input type="file" accept="image/*" class="imgFile"/>
						<span>选择图片</span>
						<input type="hidden" name="webLogo" value="{$option['webLogo']|default=''}"/>
						{if !empty($option['webLogo'])}<img src="{$option['webLogo']}"/>{/if}
					</div>
				</div>
				<div class="me_input big"><label>SEO标题</label><input type="text" name="seoTitle" value="{$option['seoTitle']|default=''}"><p class="tips">seo标题优先使用此内容</p></div>
				<div class="me_input big"><label>关键字</label><input type="text" name="keyword" value="{$option['keyword']|default=''}"><p class="tips">多个关键词用“,”隔开</p></div>
				<div class="me_input big"><label>描述</label><textarea name="description">{$option['description']|default=''}</textarea></div>
				<div class="me_input big"><label>ICP备案号</label><input type="text" name="icp" value="{$option['icp']|default=''}"></div>
				<div class="me_input big"><label>系统KEY</label><input type="text" name="key" value="{$option['key']|default=''}"><p class="tips">系统升级、插件安装、授权的唯一标识，请勿随意修改，如果没有KEY，请联系客服获取</p></div>
				<div class="me_input big"><label>统计代码</label><textarea name="totalCode">{$option['totalCode']|default=''|stripslashes}</textarea></div>
			</div>
			<div id="tab2" class="tab">
				<div class="me_input me_input_line"><label>开发模式</label><input type="checkbox" name="isDevelop" value="1" {if isset($option['isDevelop']) && $option['isDevelop'] == 1}checked{/if}></div>
				<div class="me_input me_input_line"><label>网站关闭</label><input type="checkbox" name="webStatus" value="1" {if isset($option['webStatus']) && $option['webStatus'] == 1}checked{/if}></div>
				<div class="me_input big"><label>闭站说明</label><textarea name="closeText">{$option['closeText']|default=''}</textarea></div>
				<div class="me_input big"><label>前台分页大小</label><input type="number" name="pagesize" value="{$option['pagesize']|default=''}"></div>
				<div class="me_input big"><label>前台最大分页</label><input type="number" name="pageMax" value="{$option['pageMax']|default=''}"><p class="tips">为空或0，则不限制</p></div>
				<div class="me_input big"><label>附件类型</label><input type="text" name="fileTypes" value="{$option['fileTypes']|default=''}"></div>
				<div class="me_input big"><label>附件大小(MB)</label><input type="text" name="fileSize" value="{$option['fileSize']|default=''}"></div>
				<div class="me_input big">
					<label>排序方式</label>
					<div class="me_input me_input_line"><label>创建时间</label><input type="radio" name="logOrder" value="id" {if isset($option['logOrder']) && $option['logOrder'] == 'id'}checked{/if}></div>
					<div class="me_input me_input_line"><label>修改时间</label><input type="radio" name="logOrder" value="updateTime" {if isset($option['logOrder']) && $option['logOrder'] == 'updateTime'}checked{/if}></div>
					<div class="me_input me_input_line"><label>智能权重</label><input type="radio" name="logOrder" value="weight" {if isset($option['logOrder']) && $option['logOrder'] == 'weight'}checked{/if}></div>
				</div>
				<div class="me_input big"><label>权重配比</label><textarea name="logWeight">{$option['logWeight']|default=''}</textarea><p class="tips">仅“智能权重”项勾选时生效，支持变量：views,comnum,upnum，格式：变量=权重数，一行一个</p></div>
				<div class="me_input me_input_line"><label>启用分类别名</label><input type="checkbox" name="cateAlias" value="1" {if isset($option['cateAlias']) && $option['cateAlias'] == 1}checked{/if}></div>
				<div class="me_input me_input_line"><label>启用文章别名</label><input type="checkbox" name="logAlias" value="1" {if isset($option['logAlias']) && $option['logAlias'] == 1}checked{/if}></div>
				<div class="me_input me_input_line"><label>启用单页别名</label><input type="checkbox" name="pageAlias" value="1" {if isset($option['pageAlias']) && $option['pageAlias'] == 1}checked{/if}></div>
				<div class="me_input me_input_line"><label>启用标签别名</label><input type="checkbox" name="tagAlias" value="1" {if isset($option['tagAlias']) && $option['tagAlias'] == 1}checked{/if}></div>
				<div class="me_input me_input_line"><label>启用专题别名</label><input type="checkbox" name="specialAlias" value="1" {if isset($option['specialAlias']) && $option['specialAlias'] == 1}checked{/if}></div>
				<div class="me_input"><label>发布自动更新缓存</label><input type="checkbox" name="isPostUpCache" value="1" {if isset($option['isPostUpCache']) && $option['isPostUpCache'] == 1}checked{/if}><p class="tips" style="line-height: 2.4rem;">关闭后不会自动更新，需手动更新缓存（标签、分类、专题、统计、文章归档）</p></div>
			</div>
			<div id="tab3" class="tab">
				<div class="me_group">
					<label>API</label>
					<div class="me_group_content">
						<div class="me_input me_input_line"><label>开启API</label><input type="checkbox" name="api_status" value="1" {if isset($option['api_status']) && $option['api_status'] == 1}checked{/if}></div>
						<div class="me_input big"><label>API-token加密key</label><input type="text" name="api_token_key" value="{$option['api_token_key']|default=''}"><p class="tips">如果API的token泄露，可以更换此参数</a></p></div>
						<div class="me_input big"><label>API限流(分钟)</label><input type="text" name="api_max_req" value="{$option['api_max_req']|default=''}"><p class="tips">每分钟最大的请求次数，为空或者0时表示不限流，如果需要限流，请先下载并启用<a href="http://app.rpcms.cn/index/app.html?id=116" target="_blank">filecache插件</a></p></div>
					</div>
				</div>
				<div class="me_group">
					<label>手机端</label>
					<div class="me_group_content">
						<div class="me_input"><label>自动跳转</label><input type="checkbox" name="wap_auto" value="1" {if isset($option['wap_auto']) && $option['wap_auto'] == 1}checked{/if}><p class="tips" style="line-height: 2.4rem;">开启后，手机端用户访问将自动跳转到设置的二级域名</p></div>
						<div class="me_input big"><label>手机端域名</label><input type="text" name="wap_domain" value="{$option['wap_domain']|default=''}"><p class="tips">手机端二级域名，如为m.xxx.com，则填写m</p></div>
						<div class="me_input big"><label>手机端模板</label><select name="wap_template">
							<option value="">选择手机端模板</option>
						{foreach $tempList as $k=>$v}
							<option value="{$v}" {php}echo $option['wap_template'] == $v ? 'selected' : '';{/php}>{$v}</option>
						{/foreach}
						</select><p class="tips" style="line-height: 2.4rem;">手机端模板名称，请确保填写的模板存在</p></div>
					</div>
				</div>
				<div class="me_group">
					<label>ID设置</label>
					<div class="me_group_content">
						<div class="me_input me_input_line"><label>加密</label><input type="checkbox" name="id_encrypt" value="1" {if isset($option['id_encrypt']) && $option['id_encrypt'] == 1}checked{/if}></div>
						<div class="me_input big"><label>salt</label><input type="text" name="id_encrypt_salt" value="{$option['id_encrypt_salt']|default=''}"><p class="tips">不同salt加密结果不一样，更改salt将使之前的加密结果失效。支持：字母或数字</a></p></div>
					</div>
				</div>
				<div class="me_group">
					<label>登录安全</label>
					<div class="me_group_content">
						<div class="me_input big"><label>错误次数</label><input type="number" name="adminLoginErrMax" value="{$option['adminLoginErrMax']|default=''}"><p class="tips">当登录错误次数达到设置限值时，账号将被封锁。空或0表示不开启</a></p></div>
						<div class="me_input big"><label>封锁时长</label><input type="number" name="adminLoginErrTime" value="{$option['adminLoginErrTime']|default='30'}"><p class="tips">单位：分钟</a></p></div>
						<div class="me_input me_input_line"><label>单点登录</label><input type="checkbox" name="adminLoginUnique" value="1" {if isset($option['adminLoginUnique']) && $option['adminLoginUnique'] == 1}checked{/if}></div>
					</div>
				</div>
				<div class="me_input me_input_line"><label>缩略图</label>
					<input type="number" name="attImgWitch" value="{$option['attImgWitch']|default=''}" placeholder="缩略图宽度">
					<span class="text" style="width: auto;background: transparent;">x</span>
					<input type="number" name="attImgHeight" value="{$option['attImgHeight']|default=''}" placeholder="缩略图高度">
				</div>
				<div class="me_input"><label>验证码类型</label><select name="captha_style"><option value="1">字符型</option><option value="2">计算型</option></select></div>
			</div>
			<div id="tab4" class="tab">
				<div class="me_input me_input_line"><label>评论开启</label><input type="checkbox" name="commentStatus" value="1" {if isset($option['commentStatus']) && $option['commentStatus'] == 1}checked{/if}></div>
				<div class="me_input me_input_line"><label>评论审核</label><input type="checkbox" name="commentCheck" value="1" {if isset($option['commentCheck']) && $option['commentCheck'] == 1}checked{/if}></div>
				<div class="me_input me_input_line"><label>内容包含中文</label><input type="checkbox" name="commentCN" value="1" {if isset($option['commentCN']) && $option['commentCN'] == 1}checked{/if}></div>
				<div class="me_input me_input_line"><label>评论验证码</label><input type="checkbox" name="commentVcode" value="1" {if isset($option['commentVcode']) && $option['commentVcode'] == 1}checked{/if}></div>
				<div class="me_input big"><label>显示排序</label><select name="commentSort">
					<option value="new" {php}echo $option['commentSort'] == 'new' ? 'selected' : '';{/php}>最新</option>
					<option value="old" {php}echo $option['commentSort'] == 'old' ? 'selected' : '';{/php}>最早</option>
				</select></div>
				<div class="me_input big"><label>每页显示数量</label><input type="number" name="commentPage" value="{$option['commentPage']|default=''}"></div>
				<div class="me_input big"><label>评论间隔时间(秒)</label><input type="number" name="commentInterval" value="{$option['commentInterval']|default='0'}"></div>
			</div>
		</div>
		<button type="sumbit" class="rp_btn success sendPost">保存设置</button>
	</form>
</div>
<script>
$(document).ready(function(){
	$(".menu_tree").find(".menu_item[data-type='webset']").addClass('active');
	$(".imgFile").on("change",function(){
		var parentBox=$(this).parents(".imgUpload"),
			val=$(this)[0].files[0];
		if(!val){
			return !0;
		}
		var formData = new FormData();
		formData.append("files", val);
		$.ajax({
			url: "{:url('index/upload')}",
			type: "post",
			data: formData,
			dataType: 'json',
			async:false,
			processData: false,
			contentType: false,
			beforeSend:function(){
				$.Msg("上传中");
			},
			success:function(res){
				if(res.code == 200){
					$.closeModal();
					parentBox.find("img").length ? parentBox.find("img").prop("src",res.data) : parentBox.append("<img src='"+res.data+"'/>");
					parentBox.find("input[type='hidden']").val(res.data);
				}else{
					$.Msg(res.msg);
				}
			},
			error: function(){
				$.Msg("上传失败");
			}
		});
	});
	$(".sendPost").click(function(){
		var a=$('.me_form').serializeArray(),
			param={
				'isDevelop':0,
				'webStatus':0,
				'cateAlias':0,
				'logAlias':0,
				'pageAlias':0,
				'tagAlias':0,
				'commentStatus':0,
				'commentCheck':0,
				'commentCN':0,
				'commentVcode':0,
				'logOrder':'id',
			};
		$.each(a, function(d,e){
			param[e.name] = e.value;
		});
		$.ajaxpost('{:url("index/webPost")}',param,function(res){
			$.Msg(res.msg);
			res.code == 200 && setTimeout(function(){window.location.reload()},2200);
		});
	});
});
</script>
{include:/footer}