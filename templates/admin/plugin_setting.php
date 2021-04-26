<?php if (!defined('CMSPATH')){exit('error!');}?>
{include:/header}
{php}
	$pluginName='plugin\\'.strtolower($plugin).'\\Setting';
	$pluginClass=new $pluginName;
	$res='';
	if(method_exists($pluginClass,'index')){
		$res=$pluginClass->index();
	}
	echo $res;
{/php}
<script>
$(document).ready(function(){
	$(".menu_tree").find(".menu_item[data-type='plugin']").addClass('active');
})
</script>
{include:/footer}