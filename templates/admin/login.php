<?php if (!defined('CMSPATH')){exit('error!');}?>
<!DOCTYPE html>
<html>
<head>
    <title>RPCMS登录</title>
    <meta name="renderer" content="webkit">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=0">
    <meta name="apple-mobile-web-app-capable" content="yes" />
    <link rel="stylesheet" href="{$cmspath}/static/css/admin.css">
	<style>
	html,body{font-size:16px;background-color: #fff;}
	</style>
</head>
<body>
	<div class="loginBox">
		<div class="loginForm">
			<div class="me_input">
				<label>用户名</label>
				<input type="text" name="username" class="username"/>
			</div>
			<div class="me_input">
				<label>密码</label>
				<input type="password" name="password" class="password"/>
			</div>
			<div class="rp_row">
				<button class="rp_btn loginBtn">登录</button>
			</div>
		</div>
	</div>
	<script src="{$cmspath}/static/js/jquery-3.2.0.min.js"></script>
	<script src="{$cmspath}/static/js/me.min.js"></script>
	<script>
		var base='{:url("index")}';
		$(document).ready(function(){
			$('.loginBtn').click(function(){
				var param={
					'username':$.trim($('.username').val()),
					'password':$.trim($('.password').val()),
				}
				if(!param.username || !param.password){
					$.Msg('请填写所有信息');return !1;
				}
				$.ajaxpost('{:url("login/dologin")}',param,function(res){
					if(res.code == 200){
						location.href=base;
					}else{
						$.Msg(res.msg);
					}
				})
			})
		})
	</script>
</body>
</html>
