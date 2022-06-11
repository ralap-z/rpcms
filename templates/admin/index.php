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
	<p style="margin-top: 1rem;color: #f00;">提示：前端URL的分页参数默认使用 _ 分割，别名中请勿使用下划线。若别名需使用下划线，请更改路由规则！</p>
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
			<li><button type="button" class="rp_btn" data-type="hook">重载钩子</button></li>
			<li><button type="button" class="rp_btn" data-type="temp">清除模板缓存</button></li>
			<li><button type="button" class="rp_btn" data-type="plugin">清除插件缓存</button></li>
		</ul>
	</div>
	<div class="cmsUpdate">
		<div class="title">系统升级<button type="button" class="rp_btn checkUpgrade" style="margin-left: 1rem;">检测更新</button></div>
		<div class="contents" style="padding-bottom: 1rem;"></div>
	</div>
	<div class="adminMsg">
		<div class="title">最新动态</div>
		<ul class="contents adminMsg_list"></ul>
	</div>
	<script>
		var upgAllFile=[];
		function getAdminMsg(){
			$.getJSON("//www.rpcms.cn/upgrade/message?v={RP.RPCMS_VERSION}&callback=?", function(res){
				$(".adminMsg_list").html("");
				$.each(res.data, function(i, item){
					$(".adminMsg_list").append('<li>'+(item.type == 'img' ? '<a href="'+item.url+'" target="_blank"><img src="'+item.title+'"/></a>' : '<a href="'+item.url+'" target="_blank" class="'+(item.class == 1 ? 'c' : '')+'">'+item.title+'</a>')+'</li>');
				});
			});
		}
		function upgradeFiles(a){
			if(!upgAllFile[a]){
				upgAllFile=[];
				return $.get("{:url('upgrade/ending')}",function(){
					$.loading("系统更新完毕",1,"color:#10ff62");
					setTimeout(function(){window.location.reload()},2200);
				});
			}
			var c=upgAllFile[a];
			$.ajax({
				"url":"{:url('upgrade/files')}",
				"type":"post",
				"async":false,
				"data":{"file":c},
				"dataType":"json",
				"beforeSend":function(){
					$.loading("准备更新"+c,1);
				},
				"success":function(res){
					a++;
					$.loading(res.msg,2,"color:"+(res.code == 200 ? "#10ff62" : "#ff7420"));
					if(res.code != -2){
						upgradeFiles(a);
					}
				},
				"error":function(){
					$.loading("更新"+c+"失败，服务连接失败<br>",2);
				}
			});	
		}
		$(document).ready(function(){
			getAdminMsg();
			$(".cacheUpdate button.rp_btn").click(function(){
				var a=$(this).data("type");
				a && $.ajaxpost("{:url('index/cacheUpdate')}",{'type':a},function(res){
					$.Msg(res.msg);
				});
			}),
			$(document).on("click",".upgradeFiles",function(){
				$.each($(".ids:checked"),function(a,b){
					var c=$(b).val();
					c && upgAllFile.push(c);
				});
				if(upgAllFile.length <= 0){
					return $.Msg("请选择需更新的文件"), !1;
				}
				$.get("{:url('upgrade/start')}",function(){
					upgradeFiles(0);
				});
			}),
			$(".checkUpgrade").click(function(){
				$(".cmsUpdate .contents").html('');
				$.ajaxpost("{:url('upgrade/check')}",{},function(res){
					if(res.code == 200){
						var upgradeHtml='<table class="me_table" style="width: 98%;margin: 0 auto;"><colgroup><col width="5%"><col width="35%"><col width="10%"><col width="20%"><col></colgroup><thead><tr><th>选择</th><th>文件</th><th>方式</th><th>时间</th></tr></thead><tbody class="rowList">';
						$.each(res.data,function(a,b){
							upgradeHtml+='<tr><td><input type="checkbox" name="upFiles[]" value="'+b.name+'" class="ids"></td><td>'+b.name+'</td><td>'+b.type+'</td><td>'+b.time+'</td></tr> ';
						});
						upgradeHtml+='</tbody></table><div class="rp_row" style="margin-top: 1rem;margin-left: 1%;"><span class="inblock allCheck">全选</span><button type="button" class="rp_btn upgradeFiles">更新选择项</button></div><script>$(".allCheck").toggleClick(function(){$(".ids").prop("checked", true);},function(){$(".ids").prop("checked", false);});<\/script>';
						$(".cmsUpdate .contents").html(upgradeHtml);
					}else{
						$.Msg(res.msg);
					}
				});
			});
		});
	</script>
{include:/footer}