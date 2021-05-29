<?php

use rp\Cache;
use rp\Url;
use rp\Config;
use rp\index\LogsMod;

/*
*导航
*/
function navs(){
	$nav=Cache::read('nav');
	$category=Cache::read('category');
	foreach($nav as $k=>$v){
		$newtab = $v['newtab'] == 1 ? 'target="_blank"' : '';
		echo '<li '.((!empty($v['children']) || ($v['types'] == 2 && !empty($category[$v['typeId']]['children']))) ? 'class="menu"' : '').'><a href="'.$v['url'].'" '.$newtab.'>'.$v['navname'].'</a>';
		if(!empty($v['children'])){
			echo '<ul class="sub">';
			foreach($v['children'] as $sk=>$sv){
				$newtab2 = $sv['newtab'] == 1 ? 'target="_blank"' : '';
				echo '<li><a href="'.$sv['url'].'" '.$newtab2.'>'.$sv['navname'].'</a></li>';
			}
			echo '</ul>';
		}
		if($v['types'] == 2 && !empty($category[$v['typeId']]['children'])){
			echo '<ul class="sub">';
			foreach($category[$v['typeId']]['children'] as $ck=>$cv){
				$data=$category[$cv];
				$url=Url::cate($cv);
				$newtab3 = $v['newtab'] == 1 ? 'target="_blank"' : '';
				echo '<li><a href="'.$url.'" '.$newtab3.'>'.$data['cate_name'].'</a></li>';
			}
			echo '</ul>';
		}
		echo'</li>';
	}
}

/*最新文章*/
function newLogs($limit=10){
	$LogsMod=new LogsMod();
	$logData=$LogsMod->limit($limit)->order(array('a.id'=>'desc'))->select();
	return $logData['list'];
}

/*随机文章*/
function randLog($limit=10){
	$LogsMod=new LogsMod();
	$logData=$LogsMod->limit($limit)->order('RAND()')->select();
	return $logData['list'];
}

/*文章分类*/
function category(){
	$category=Cache::read('category');
	foreach($category as $k=>$v){
		$category[$k]['url']=Url::cate($v['id']);
	}
	return $category;
}

/*文章归档*/
function record(){
	$logRecord=Cache::read('logRecord');
	foreach($logRecord as $k=>$v){
		$logRecord[$k]['url']=Url::other('date',$v['date']);
	}
	return $logRecord;
}

/*相邻文章*/
function neighbor($logId){
	$LogsMod=new LogsMod();
	$res=$LogsMod->neighbor($logId);
	$prev='<p>上一篇：没有了 </p>';
	$next='<p>下一篇：没有了 </p>';
	if(!empty($res['prev'])){
		$prev='<p>上一篇：<a href="'.$res['prev']['url'].'">'.$res['prev']['title'].'</a></p>';
	}
	if(!empty($res['next'])){
		$next='<p>下一篇：<a href="'.$res['next']['url'].'">'.$res['next']['title'].'</a></p>';
	}
	return $prev.$next;
}

/*相关文章*/
function related($data,$type='cate',$limit=10){
	$LogsMod=new LogsMod();
	//随机获取order传参rand()
	$res=$LogsMod->order(array('views'=>'desc'))->related($data,$type,$limit);
	$html='';
	foreach($res as $k=>$v){
		$html.='<li><a href="'.$v['url'].'" title="'.$v['title'].'">'.$v['title'].'</a></li>';
	}
	return $html;
}

/*友情链接*/
function links(){
	$links=Cache::read('links');
	$linksHtml='<ul class="links">';
	foreach($links as $k=>$v){
		$linksHtml.='<li><a href="'.$v['siteurl'].'" target="_blank" title="'.$v['sitedesc'].'">'.$v['sitename'].'</a></li>';
	}
	return $linksHtml.'</ul>';
}

/*面包屑导航*/
function mianbao($type,$value){
	global $App;
	$html='<a href="'.$App->baseUrl.'" title="返回首页">首页</a>&nbsp;&gt;&nbsp;';
	switch($type){
		case 'search':
			$html.='搜索&nbsp;<b>&nbsp;'.$value.'&nbsp;</b>&nbsp;的结果';
			break;
		case 'tages':
			$tages=Cache::read('tages');
			if(isset($tages[$value])){
				$html.='<a href="'.Url::other('tages',$value).'">'.$tages[$value]['tagName'].' 的文章</a>';
			}
			break;
		case 'logs':
			$html.='<a href="'.$value['cateUrl'].'">'.$value['cateName'].'</a>&nbsp;&gt;&nbsp;正文';
			break;
		case 'author':
			$user=Cache::read('user');
			if(isset($user[$value])){
				$html.='<a href="'.Url::other('author',$value).'">'.$user[$value]['nickname'].' 的文章</a>';
			}
			break;
		case 'cate':
			$category=Cache::read('category');
			if(isset($category[$value])){
				$html.='<a href="'.Url::cate($value).'">'.$category[$value]['cate_name'].'</a>';
			}
			break;
		case 'special':
			$special=Cache::read('special');
			if(isset($special[$value])){
				$html.='<a href="'.Url::special($value).'">'.$special[$value]['title'].'</a>';
			}
			break;
		case 'page':
			$pages=Cache::read('pages');
			if(isset($pages[$value])){
				$html.='<a href="'.Url::page($value).'">'.$pages[$value]['title'].'</a>';
			}
			break;
		case 'date':
			$html.='<a href="'.Url::other('date',$value).'">'.$value.'</a>';
			break;
			
	}
	return $html;
}

/*评论列表*/
function comment_list($CommentData){
	$html='<h3 class="comment_title">有 <b>'.$CommentData['count'].'</b> 位网友评论：</h3><div class="comment_list">';
	$comments=$CommentData['list'];
	$commentsData=$comments['lists'];
	foreach($comments['top'] as $k=>$v){
		$comment = $commentsData[$v];
		$html.= '<div class="comment_msg" data-id="'.$comment['id'].'"><img class="comment_avatar" src="'.getHead($comment['email']).'"/>
			<div class="comment_content">
				<p class="comment_name">
					<a href="'.(!empty($comment['home']) ? $comment['home'] : 'javascript:;').'" rel="nofollow" target="_blank">'.$comment['nickname'].'</a>
					<span class="comment_time"><i class="iconfont icon-time"></i> '.formatDate($comment['createTime']).'</span>
					<button type="button" class="quickReplay"><i class="iconfont icon-comment"></i>回复</button>
				</p>
				<p class="comment_info"><i class="iconfont"></i>'.$comment['content'].'</p>
			</div>';
		$html.=comment_list_children($comment['children'],$commentsData);
		$html.='</div>';
	}
	return $html.'</div><div class="pages comment_page">'.$CommentData['pageHtml'].'</div>';
}
/*子评论列表*/
function comment_list_children($children,$commentsData){
	$html='';
	if(!empty($children)){
		foreach($children as $k=>$v){
			$comment = $commentsData[$v];
			$html.='<div class="comment_msg comment_msg_son" data-id="'.$comment['id'].'"><img class="comment_avatar" src="'.getHead($comment['email']).'"/>
				<div class="comment_content">
					<p class="comment_name">
						<a href="'.(!empty($comment['home']) ? $comment['home'] : 'javascript:;').'" rel="nofollow" target="_blank">'.$comment['nickname'].'</a>
						<span class="comment_time"><i class="iconfont icon-time"></i> '.formatDate($comment['createTime']).'</span>
						<button type="button" class="quickReplay"><i class="iconfont icon-comment"></i>回复</button>
					</p>
					<p class="comment_info"><i class="iconfont"></i>'.$comment['content'].'</p>
				</div>';
				$html.=comment_list_children($comment['children'],$commentsData);
			$html.='</div>';
		}
	}
	return $html;
}
/*发表评论*/
function comment_post($types,$id,$user,$isRemark){
	$types = $types == 'pages' ? 'pages' : 'logs';
	$id=intval($id);
	$html='<p style="padding: 3rem;text-align: center;">评论功能暂时关闭</p>';
	if(Config::get('webConfig.commentStatus') == 1 && $isRemark == 1){
		$html='<form id="commentSumbit" target="_self" method="post" action="/comment/addcom" class="form_box" onSubmit="return false;">
			<input type="hidden" name="types" id="types" value="'.$types.'">
			<input type="hidden" name="vid" id="vid" value="'.$id.'">
			<input type="hidden" name="topId" id="topId" value="0">
			<div class="input_line"><input type="text" name="username" id="username" value="'.$user['nickname'].'" tabindex="1" placeholder="名称(*)"></div>
			<div class="input_line"><input type="text" name="email" id="email" tabindex="2" value="'.$user['email'].'" placeholder="邮箱"></div>
			<div class="input_line"><input type="text" name="home" id="home" tabindex="3" value="'.$user['home'].'" placeholder="主页网站http(s)://"></div>';
		Config::get('webConfig.commentVcode') == 1 && $html.='<div class="input_line input_qrcode" style="padding-right: 0;"><input type="text" name="verifyCode" id="verifyCode" tabindex="4" placeholder="验证码(*)"><img id="commentVcode" src="'.Url::other('captcha','comment').'" alt="请填写验证码" onclick="javascript:this.src=this.src+\'?v=\'+new Date().getTime();"></div>';
		$html.='<div class="input_textarea">
				<textarea name="content" id="content" cols="50" rows="4" tabindex="5" placeholder="欢迎你的交流和评论，还请不要无意义的灌水呢(°ー°〃)"></textarea>
				<input name="sumbit" type="submit" tabindex="6" value="提交评论" onclick="return commentPost()" class="form_btn">
			</div>
		</form>';
	}
	return $html;
}

function getHead($email){
	$headImg=TEMPURL .'/images/avatar.gif';
	if(empty($email)){
		return $headImg;
	}
	$emailArr=explode('@', $email,2);
	if($emailArr[1] == 'qq.com' && is_numeric($emailArr[0])){
		$headImg='http://q1.qlogo.cn/g?b=qq&nk='.$emailArr[0].'&s=100&t='. time();
	}else{
		$hash = md5($email);
		$s=45;
		$d='mm';
		$g='g';
		$avatar = 'http://secure.gravatar.com/avatar/'.$hash.'?s='.$s.'&d='.$d.'&r='.$g;
		$headImg=!empty($avatar) ? $avatar : '';
	}
	return $headImg;
}


/*正文中匹配缩略图*/
function thumb($data,$len=1){
	preg_match_all("|<img[^>]+src=\"([^>\"]+)\"?[^>]*>|is", $data['content'], $img);
	$thumb=array();
	if(isset($img[1]) && !empty($img[1])){
		$max=min(count($img[1]),$len);
		for($i=0;$i<$max;$i++){
			$imgs=$img[1][$i];
			$newimg=dirname($imgs). '/thum-' .basename($imgs);
			$thumb[]='<img src="'.(file_exists(CMSPATH .'/'. $newimg) ? $newimg : $imgs).'" alt="'.$data['title'].'"/>';
		}
	}else{
		$thumb[]= '<img src="'. TEMPURL .'/images/random/'.rand(1,20).'.jpg" alt="'.$data['title'].'"/>';
	}
	return join('',$thumb);
}

