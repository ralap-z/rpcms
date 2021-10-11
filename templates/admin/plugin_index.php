<?php if (!defined('CMSPATH')){exit('error!');}?>
{include:/header}
<style>
.sortInput{width: 3rem;padding: 0.2rem;}
.upSort{margin-top: 0.4rem;margin-left: 0;}
</style>
<div class="me_body">
	<div class="me_head">
		<div class="me_input me_input_line">
			<!--a href="javascript:;" class="rp_btn" data-model-type="plugin">安装插件</a-->
		</div>
	</div>
	<table class="me_table">
		<colgroup>
			<col width="25%">
			<col width="10%">
			<col width="10%">
			<col width="40%">
			<col>
		</colgroup>
		<thead>
			<tr>
				<th>名称</th>
				<th>状态</th>
				<th>版本</th>
				<th>描述</th>
				<th>操作</th>
			</tr> 
		</thead>
		<tbody class="rowList">
			{foreach $list as $k=>$v}
				<tr data-id="{$k}">
					<td><img src="{$v['icon']}" style="width: 40px;height: 40px;"/>
						{if in_array($k,$pluginUse) && $v['setting']}
							<a href="{:url('plugin/setting')}?plugin={$k}" title="点击设置插件">{$v['name']}</a>
						{else}
							{$v['name']}
						{/if}
					</td>
					<td>
						{if in_array($k,$pluginUse)}
							<span class="upStatus" data-value="-1" title="点击禁用插件"><font>已启用</font></span>
						{else}
							<span class="upStatus" data-value="1" title="点击启用插件"><font class="color4">未启用</font></span>
						{/if}
					</td>
					<td>{$v['version']}</td>
					<td>{$v['description']}
						{if !empty($v['url'])}
							<a href="{$v['url']}" title="查看更多信息" target="_blank">查看</a>
						{/if}
						{if !empty($v['author'])}
							<p>作者：
							{if !empty($v['authorUrl'])}<a href="{$v['authorUrl']}" title="查看作者" target="_blank">{$v['author']}</a>{else}{$v['author']}{/if}
							</p>
						{/if}
					</td>
					<td>
						{if in_array($k,$pluginUse) && $v['setting']}
							<a href="{:url('plugin/setting')}?plugin={$k}" title="点击设置插件">设置</a>
						{/if}
						<a href="javascript:;" class="operBtn delete">删除</a>
					</td>
				</tr>
			{/foreach}
		</tbody>
	</table>
</div>
<script>
$(document).ready(function(){
	$(".menu_tree").find(".menu_item[data-type='plugin']").addClass('active');
	$(".upStatus").click(function(){
		var _this=$(this),
			a=_this.parents('tr').data('id'),
			b=_this.data('value');
		$.ajaxpost("{:url('plugin/upStatus')}",{'id':a,'status':(b == 1 ? 1 : -1)},function(res){
			$.Msg(res.msg);
			res.code == 200 && setTimeout(function(){window.location.reload()},2200);
		});
	}),
	$(".delete").click(function(){
		var id=$(this).parents('tr').data('id');
		if(!id || !confirm('你确定要删除该插件吗？')){return !1;}
		$.ajaxpost("{:url('plugin/dele')}",{'id':id},function(res){
			$.Msg(res.msg);
			res.code == 200 && setTimeout(function(){window.location.reload()},2200);
		});
	});
});
</script>
{include:/footer}