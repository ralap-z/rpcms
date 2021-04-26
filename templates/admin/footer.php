<?php if (!defined('CMSPATH')){exit('error!');}?>
	<div class="me_model me_anim_bounce me_model_updateAdmin" style="width: 24rem">
		<div class="title">修改密码</div>
		<a class="me-icon me-icon-close me_model_close" href="javascript:;"  data-callback="reaset"></a>
		<div class="contentes">
			<form class="me_form" action="" onSubmit="return false">
				<div class="me_input"><label style="width: 4rem;">用户名</label><span class="text">{$user['username']}</span></div>
				<div class="me_input"><label style="width: 4rem;">昵称</label><input type="text" class="upA_nickname" value="{$user['nickname']}" placeholder=""></div>
				<div class="me_input"><label style="width: 4rem;">新密码</label><input type="text" class="upA_password" value="" placeholder="至少6位"></div>
				<div class="me_input"><label style="width: 4rem;">重复密码</label><input type="text" class="upA_password2" value="" placeholder=""></div>
				<button type="sumbit" class="rp_btn success sendPost_updateAdmin" style="width: 100%;margin-top: 1rem;">修改密码</button>
			</form>
		</div>
	</div>
	<div class="veil"></div>
</div>
</body>
</html>