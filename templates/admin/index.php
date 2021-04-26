<?php if (!defined('CMSPATH')){exit('error!');}?>
{include:/header}
	<ul class="totalBox flex">
		<li><label>当前用户</label>{$user['username']}</li>
		<li><label>当前版本</label>{RP.RPCMS_VERSION}</li>
		<li><label>文章数量</label>{$totalData['logs']}</li>
		<li><label>单页数量</label>{$totalData['page']}</li>
		<li><label>分类数量</label>{$totalData['cate']}</li>
		<li><label>标签数量</label>{$totalData['tages']}</li>
		<li><label>评论数量</label>{$totalData['comment']}</li>
	</ul>
	<div class="cacheUpdate">
		<div class="title">缓存更新</div>
		<ul class="contents flex">
			<li><button type="button" class="rp_btn" data-type="all">更新所有</button></li>
			<li><button type="button" class="rp_btn" data-type="cms">更新系统</button></li>
			<li><button type="button" class="rp_btn" data-type="user">更新用户</button></li>
			<li><button type="button" class="rp_btn" data-type="nav">更新导航</button></li>
			<li><button type="button" class="rp_btn" data-type="logs">更新文章</button></li>
			<li><button type="button" class="rp_btn" data-type="cate">更新分类</button></li>
			<li><button type="button" class="rp_btn" data-type="tages">更新标签</button></li>
			<li><button type="button" class="rp_btn" data-type="pages">更新单页</button></li>
			<li><button type="button" class="rp_btn" data-type="special">更新专题</button></li>
			<li><button type="button" class="rp_btn" data-type="temp">清除模板缓存</button></li>
			<li><button type="button" class="rp_btn" data-type="plugin">清除插件缓存</button></li>
		</ul>
	</div>
	<div class="cmsUpdate">
		<div class="title">系统升级<button type="button" class="rp_btn checkUpgrade" style="margin-left: 1rem;">检测更新</button></div>
		<div class="contents" style="padding-bottom: 1rem;"></div>
	</div>
	
	<script>
		$(document).ready(function(){
			$(".cacheUpdate button.rp_btn").click(function(){
				var a=$(this).data("type");
				a && $.ajaxpost("{:url('index/cacheUpdate')}",{'type':a},function(res){
					$.Msg(res.msg);
				})
			})
			$(document).on("click",".upgradeFiles",function(){
				var allFile=$(".ids:checked").length,exNum=0;
				$.each($(".ids:checked"),function(a,b){
					var c=$(b).val();
					c && setTimeout(function(){
						$.ajax({
							"url":"{:url('upgrade/files')}",
							"type":"post",
							"async":false,
							"data":{"file":c},
							"dataType":"json",
							"beforeSend":function(){
								$.Msg("准备更新"+c);
							},
							"success":function(res){
								exNum++;
								$.Msg(res.msg);
								exNum >= allFile && setTimeout(function(){window.location.reload()},2200);
							},
							"error":function(){
								$.Msg("更新"+c+"失败，服务连接失败<br>");
							}
						});	
					},a*1000);
				})
			})
			$(".checkUpgrade").click(function(){
				$.ajaxpost("{:url('upgrade/check')}",{},function(res){
					if(res.code == 200){
						var upgradeHtml='<table class="me_table" style="width: 98%;margin: 0 auto;"><colgroup><col width="5%"><col width="35%"><col width="10%"><col width="20%"><col></colgroup><thead><tr><th>选择</th><th>文件</th><th>方式</th><th>时间</th></tr></thead><tbody class="rowList">';
						$.each(res.data,function(a,b){
							upgradeHtml+='<tr><td><input type="checkbox" name="upFiles[]" value="'+b.name+'" class="ids"></td><td>'+b.name+'</td><td>'+b.type+'</td><td>'+b.time+'</td></tr> ';
						})
						upgradeHtml+='</tbody></table><div class="rp_row" style="margin-top: 1rem;margin-left: 1%;"><span class="inblock allCheck">全选</span><button type="button" class="rp_btn upgradeFiles">更新选择项</button></div><script>$(".allCheck").toggleClick(function(){$(".ids").prop("checked", true);},function(){$(".ids").prop("checked", false);});<\/script>';
						$(".cmsUpdate .contents").html(upgradeHtml);
					}else{
						$.Msg(res.msg);
					}
				})
			})
		})
	</script>
{include:/footer}