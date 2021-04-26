<?php if (!defined('CMSPATH')){exit('error!');}?>
<div style="width:100%;background:#fff;position: relative;">
	<form class="me_form" action="" method="post">
		<div class="me_input">
			<label>外观模式</label>
			<label><input type="radio" name="layout" value="left" {php}echo (isset($config['layout']) && $config['layout']=='left') ? 'checked' : ''{/php}>侧栏左</label>
			<label><input type="radio" name="layout" value="right" {php}echo (isset($config['layout']) && $config['layout']=='right') ? 'checked' : ''{/php}>侧栏右</label>
		</div>
		<div class="me_input">
			<label>页面宽度</label>
			<label><input type="radio" name="appWidth" value="1000" {php}echo (isset($config['appWidth']) && $config['appWidth']=='1000') ? 'checked' : ''{/php}>1000px</label>
			<label><input type="radio" name="appWidth" value="1200" {php}echo (isset($config['appWidth']) && $config['appWidth']=='1200') ? 'checked' : ''{/php}>1200px</label>
		</div>
		<div class="me_input"><label>背景颜色</label><input type="text" name="bgColor" value="{php}echo isset($config['bgColor']) ? $config['bgColor'] : ''{/php}" id="colorPicker"></div>
		<input type="hidden" name="sendpost" value="1"/>
		<button type="sumbit" class="rp_btn success sendpost">保存设置</button>
	</form>
	<script src="{$tempFile}/source/colorpicker.js" type="text/javascript"></script>
	<script>
		var a = Colorpicker.create({
			el: "colorPicker",
			color: "{php}echo isset($config['bgColor']) ? $config['bgColor'] : '#ffffff'{/php}",
			change: function (elem, hex) {
				elem.value = hex;
			}
		})
	</script>
</div>