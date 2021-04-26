<?php if (!defined('CMSPATH')){exit('error!');}?>
</div>
<footer>
	<div class="footer win">
		{:links()}
		<div class="bzjj">
			<h2>本站简介</h2>
			<p>RPCMS网站系统，我的第一个网站。</p>
		</div>
		<div class="other">
			<h2>网站版权</h2>
			<p>未经授权禁止转载、摘编、复制或建立镜像，如有违反，追究法律责任。</p>
			<p>Powered by <a href="http://www.rpcms.cn" style="color: inherit;">rpcms V{RP.RPCMS_VERSION}</a>  备案号：{$webConfig['icp']}</p>
		</div>
	</div>
</footer>
<a href="javascript:;" class="cd-top">Top</a>
{$webConfig['totalCode']}
<script src="/static/js/me.min.js"></script>
<script src="{$tempUrl}/js/default.js"></script>
{hook:index_footer}
{hook:wstools_show}
</body>
</html>