<?php
namespace rp\index;

use rp\Db;
use rp\Config;
use rp\Hook;
use rp\index\CommentMod;

class Comment extends base{
	private $CommentMod;
	private $isAjax;
	public function __construct(){
		parent::__construct();
		$this->CommentMod=new CommentMod();
		$this->isAjax=$this->App->isAjax();
	}
	
	public function getListByLogs($id,$page=1){
		$pageNumb=intval(input('comment-page')) ? intval(input('comment-page')) : 1;
		$page = !empty($pageNumb) ? $pageNumb : $page;
		$CommentData=$this->CommentMod->logs($id)->page($page)->select();
		$CommentData['pageHtml']=pageInationHome($CommentData['count'],$CommentData['limit'],$CommentData['page'],'comment','#commentlist');
		return $CommentData;
	}
	
	public function getListByPages($id,$page=1){
		$pageNumb=intval(input('comment-page')) ? intval(input('comment-page')) : 1;
		$page = !empty($pageNumb) ? $pageNumb : $page;
		$CommentData=$this->CommentMod->html($id)->page($page)->select();
		$CommentData['pageHtml']=pageInationHome($CommentData['count'],$CommentData['limit'],$CommentData['page'],'comment','#commentlist');
		return $CommentData;
	}
	
	private function msg($msg,$code=200,$data=''){
		if($this->isAjax){
			return json(array('code'=>$code, 'msg'=>$msg, 'data'=>$data));
		}else{
			return rpMsg($msg);
		}
	}
	
	public function add(){
		$param=input('post.');
		$param['vid']=intval($param['vid']);
		$param['topId']=intval($param['topId']);
		if(empty($param['vid']) || !isset($param['topId'])){
			return $this->msg('数据错误',-1);
		}
		if(Config::get('webConfig.commentStatus') != 1){
			return $this->msg('系统评论功能已关闭',-1);
		}
		if($param['types'] == 'pages'){
			$res=Db::name('pages')->where('id='.$param['vid'])->field('authorId,isRemark')->find();
			$msgType='页面';
		}else{
			$res=Db::name('logs')->where('id='.$param['vid'])->field('authorId,isRemark')->find();
			$msgType='文章';
		}
		if($res['isRemark'] != 1){
			return $this->msg('该'.$msgType.'评论功能已关闭',-1);
		}
		$top=Db::name('comment')->where('id='.$param['topId'])->field('levels')->find();
		if(!empty($top) && $top['levels'] >= 4){
			return $this->msg('回复级别最大4级',-1);
		}
		$lastTime=cookie('comment_cookie_last');
		$newTime=time();
		if(!empty($lastTime) && ($newTime-$lastTime) < Config::get('webConfig.commentInterval')){
			return $this->msg('速度太快了，休息一下吧',-1);
		}
		if(empty($param['username'])){
			return $this->msg('评论名称不可为空',-1);
		}
		if(!empty($param['email']) && !checkForm('email',$param['email'])){
			return $this->msg('邮箱格式错误',-1);
		}
		if(!empty($param['home']) && !checkForm('url',$param['home'])){
			return $this->msg('主页网址格式错误',-1);
		}
		if(empty($param['content'])){
			return $this->msg('评论内容不可为空',-1);
		}
		if(Config::get('webConfig.commentCN') == 1 && !preg_match('/[\x{4e00}-\x{9fa5}]/iu', $param['content'])){
			return $this->msg('评论内容需包含中文',-1);
		}
		if(Config::get('webConfig.commentVcode') == 1){
			if(empty($param['verifyCode'])){
				return $this->msg('验证码不可为空',-1);
			}
			if(!(new \rp\Captcha())->check($param['verifyCode'],'comment')){
				return $this->msg('验证码错误',101);
			}
		}
		$user=self::$user;
		$data=array();
		$data['logId']=$param['types'] == 'logs' ? $param['vid'] : 0;
		$data['pageId']=$param['types'] == 'pages' ? $param['vid'] : 0;
		$data['topId']=$param['topId'];
		$data['authorId']=$res['authorId'];
		$data['userId']=isset($user['id']) ? $user['id'] : 0;
		$data['levels']=$top['levels'] + 1;
		$data['nickname']=subString($param['username'],0,20);
		$data['email']=strip_tags($param['email']);
		$data['home']=strip_tags($param['home']);
		$data['content']=subString($param['content'],0,1000);
		$data['ip']=ip();
		$data['agent']=$this->App::server('HTTP_USER_AGENT');
		$data['createTime']=date('Y-m-d H:i:s');
		$data['status']=Config::get('webConfig.commentCheck') == 1 ? 1 : 0;
		Hook::doHook('comment_post',$data);
		$res1=Db::name('comment')->insert($data);
		if($param['types'] == 'pages'){
			$res2=Db::name('pages')->where('id='.$param['vid'])->setInc('comnum');
		}else{
			$res2=Db::name('logs')->where('id='.$param['vid'])->setInc('comnum');
		}
		cookie('comment_cookie_last',$newTime, Config::get('webConfig.commentInterval') + 5);
		if($res1 && $res2){
			$msg=Config::get('webConfig.commentCheck') == 1 ? '，等待后台审核' : '';
			Hook::doHook('comment_save',$data);
			return $this->msg('评论成功'.$msg, 200);
		}
		return $this->msg('评论失败，请稍后重试', -1);
	}
}
