<?php if (!defined('CMSPATH')){exit('error!');}?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta http-equiv="X-UA-Compatible" content="IE=7" />
<title>RPCMS V{RP.RPCMS_VERSION} 安装向导</title>
<link href="{$cmspath}/static/css/install.css" rel="stylesheet" type="text/css" />
<script language="JavaScript" src="{$cmspath}/static/js/jquery-3.2.0.min.js"></script>
</head>
<body>
	<div class="install">
		<div class="title">RPCMS V{RP.RPCMS_VERSION}</div>
		<div class="content"></div>
		<div class="footer">
			<button type="button" class="step1 success">同意</button>
			<button type="button" class="close">关闭</button>
		</div>
	</div>
	<script>
		var diyname='admin',
			time=(new Date()).getTime();
		$(document).ready(function(){
			getStep1();
			$("body").on("click",".footer button",function(){
				var a=$(this).data("step"),
					b="getStep"+a;
				a && window[b] && window[b]();
			})
		})
		function getStep1(){
			$.ajax({
				url:"{:url('index/step1')}",
				data:{"_t":time},
				type:"post",
				dataType:"json",
				success:function(res){
					$(".content").html(res.data);
					$(".footer").html('<button type="button" class="success" data-step="2">同意</button><button type="button" class="close">关闭</button>');
				},
				error:function(){
					alert("数据获取失败");
				}
			})
		}
		function getStep2(){
			$.ajax({
				url:"{:url('index/step2')}",
				data:{"_t":time},
				type:"post",
				dataType:"json",
				success:function(res){
					$(".content").html('');
					$(".footer").html('<button type="button" class="success"  data-step="3">下一步</button><button type="button" class=""  data-step="1">上一步</button>');
					for(var a in res.data){
						$(".content").append('<p class="step2_item"><span class="tit">'+a+'</span><span>'+res.data[a]+'</span></p>');
					}
				},
				error:function(){
					alert("数据获取失败");
				}
			})
		}
		function getStep3(){
			var a=$(".content").find(".step2_item font").length;
			if(a > 0){
				alert("请修改相应的权限或配置后刷新重试");return !1;
			}
			$(".content").html([
				"<p class='group_title'>数据库信息</p>",
				"<div class='me_input'><label>数据库主机</label><input type='text' name='dbhost' class='dbhost' value='127.0.0.1'></div>",
				"<div class='me_input'><label>数据库账号</label><input type='text' name='dbuser' class='dbuser' value='root'></div>",
				"<div class='me_input'><label>数据库密码</label><input type='text' name='dbpsw' class='dbpsw' value=''></div>",
				"<div class='me_input'><label>数据库名称</label><input type='text' name='dbname' class='dbname' value=''></div>",
				"<div class='me_input'><label>数据表前缀</label><input type='text' name='tablepre' class='tablepre' value='rp_'></div>",
				"<p class='group_title'>管理员信息</p>",
				"<div class='me_input'><label>自定义地址</label><input type='text' name='diyname' class='diyname' value='admin'><span class='tips'>自定义的后台访问地址，为空则默认为admin</span></div>",
				"<div class='me_input'><label>管理员名称</label><input type='text' name='username' class='username' value=''></div>",
				"<div class='me_input'><label>管理员密码</label><input type='text' name='userpsw' class='userpsw' value=''></div>",
			].join(''));
			$(".footer").html('<button type="button" class="success"  data-step="4">下一步</button><button type="button" class=""  data-step="2">上一步</button>');
		}
		
		function getStep4(){
			var param={
				'dbhost':$.trim($(".dbhost").val()),
				'dbuser':$.trim($(".dbuser").val()),
				'dbpsw':$.trim($(".dbpsw").val()),
				'dbname':$.trim($(".dbname").val()),
				'tablepre':$.trim($(".tablepre").val()),
				'diyname':$.trim($(".diyname").val()),
				'username':$.trim($(".username").val()),
				'userpsw':$.trim($(".userpsw").val()),
			};
			if(!param.dbhost){
				alert('请填写数据库主机');return !1;
			}
			if(!param.dbuser){
				alert('请填写数据库账号');return !1;
			}
			if(!param.dbpsw){
				alert('请填写数据库密码');return !1;
			}
			if(!param.dbname){
				alert('请填写数据库名称');return !1;
			}
			if(!param.username){
				alert('请填写管理员名称');return !1;
			}
			if(!param.userpsw){
				alert('请填写管理员密码');return !1;
			}
			diyname=param.diyname || "admin";
			$.ajax({
				url:"{:url('index/step4')}",
				type:"post",
				data:param,
				dataType:"json",
				beforeSend:function(){
					$("body").append("<div class='tip'><span>正在安装...</span></div>");
				},
				complete:function(){
					$("body").find(".tip").remove();
				},
				success:function(res){
					if(res.code == 200){
						$(".content").html('<div class="successBox"><div class="msg"><b>恭喜，安装完成！</b><p>为了系统安全，请手动删除install应用模块或更换文件夹名称</p></div></div><div class="successBtn"><a href="'+res.data+'/'+diyname+'" class="color">访问后台</a><a href="'+res.data+'">立即体验</a></div>');
						$(".footer").html('');
					}else{
						alert(res.msg);
					}
				},
				error:function(){
					alert("数据获取失败");
				}
			})
		}
	</script>
</body>
</html>