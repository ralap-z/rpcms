<?php if (!defined('CMSPATH')){exit('error!');}?>
{include:/header}
{include:$settingFile}
<script>
$(document).ready(function(){
	$(".menu_tree").find(".menu_item[data-type='temp']").addClass('active');
});
</script>
{include:/footer}