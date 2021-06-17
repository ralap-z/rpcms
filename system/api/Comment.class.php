<?php
namespace rp\api;

use rp\Db;
use rp\Cache;
use rp\Url;
use rp\Config;
use rp\Hook;

class Comment extends Base{
	
	public function __construct(){
		parent::__construct();
	}
	
	public function getList(){
		$logId=(int)input('logId');
		$pageId=(int)input('pageId');
		$where=array('a.status'=>0);
		$page=intval(input('page')) ? intval(input('page')) : 1;
		$limit=!empty(Config::get('webConfig.commentPage')) ? Config::get('webConfig.commentPage') : 10;
		$order=Config::get('webConfig.commentSort') == 'old' ? array('a.id'=>'asc') : array('a.id'=>'desc');
		if(empty($logId) && empty($pageId)){
			//获取所有评论
			$count=Db::name('comment')->alias('a')->where($where)->count();
			$list=Db::name('comment')->alias('a')->join(array(
				array('logs b','a.logId=b.id','left'),
				array('pages c','a.pageId=c.id','left'),
			))->field('a.*,if(a.pageId,c.title,b.title) as title')->where($where)->order($order)->limit(($page-1)*$limit.','.$limit)->select();
			foreach($list as $k=>&$v){
				$v['url']=!empty($v['pageId']) ? Url::page($v['pageId']) : Url::logs($v['logId']);
			}
			Hook::doHook('api_comment_list',array(&$list));
			$this->response(array('count'=>$count,'limit'=>$limit,'page'=>$page,'list'=>$list));
		}else{
			if(!empty($pageId)){
				$where['a.pageId']=$pageId;
			}else{
				$where['a.logId']=$logId;
			}
			$count=Db::name('comment')->alias('a')->where($where)->where(array('a.topId'=>0))->field('a.id')->count();
			$commentTop=Db::name('comment')->alias('a')->where($where)->where(array('a.topId'=>0))->field('a.id')->limit(($page-1)*$limit.','.$limit)->order($order)->select();
			$commentTop=array_column($commentTop,'id');
			$list=array();
			if(!empty($commentTop)){
				$commentSon=$this->getSon($commentTop,$where);
				$list=Db::name('comment')->alias('a')->where($where)->where(array('a.id'=>array('in',join(',',array_merge($commentTop,$commentSon)))))->field('a.*')->order($order)->select();
				$list=array_column($list,NULL,'id');
				foreach($list as $k=>$v){
					$list[$k]['nickname'] = strip_tags($v['nickname']);
					$list[$k]['email'] = htmlspecialchars($v['email']);
					$list[$k]['home'] = htmlspecialchars($v['home']);
					$list[$k]['content'] = strip_tags($v['content']);
					!isset($list[$k]['children']) && $list[$k]['children']=array();
					$v['topId'] != 0 && isset($list[$v['topId']]) && $list[$v['topId']]['children'][]=$v['id'];
				}
			}
			$this->response(array('count'=>$count,'limit'=>$limit,'page'=>$page,'list'=>array('lists'=>$list,'top'=>$commentTop)));
		}
	}
	
	public function getData(){
		$id=(int)input('id');
		$data=Db::name('comment')->where('id='.$id)->find();
		if(empty($data) || $data['status'] != 0){
			$this->response('',404,'未找到评论数据！');
		}
		$this->response($data);
	}
	
	public function post(){
		$param=input('post.');
		$default=array(
			'types'=>'',
			'vid'=>0,
			'logId'=>'',
			'pageId'=>'',
			'topId'=>0,
			'authorId'=>'',
			'userId'=>'',
			'levels'=>1,
			'nickname'=>'',
			'email'=>'',
			'home'=>'',
			'content'=>'',
			'ip'=>'',
			'agent'=>'',
			'createTime'=>'',
			'status'=>1,
		);
		$param=array_merge($default,$param);
		$param['vid']=intval($param['vid']);
		$param['topId']=intval($param['topId']);
		$param['userId']=0;
		if(empty($param['vid']) || empty($param['types'])){
			$this->response('',401,'数据错误');
		}
		if(Config::get('webConfig.commentStatus') != 1){
			$this->response('',401,'系统评论功能已关闭');
		}
		if($param['types'] == 'pages'){
			$res=Db::name('pages')->where('id='.$param['vid'])->field('authorId,isRemark')->find();
			$msgType='页面';
		}else{
			$res=Db::name('logs')->where('id='.$param['vid'])->field('authorId,isRemark')->find();
			$msgType='文章';
		}
		if($res['isRemark'] != 1){
			$this->response('',401,'该'.$msgType.'评论功能已关闭');
		}
		$top=Db::name('comment')->where('id='.$param['topId'])->field('levels')->find();
		if(!empty($top) && $top['levels'] >= 4){
			$this->response('',401,'回复级别最大4级');
		}
		$lastTime=cookie('comment_cookie_last');
		$newTime=time();
		if(!empty($lastTime) && ($newTime-$lastTime) < Config::get('webConfig.commentInterval')){
			$this->response('',401,'速度太快了，休息一下吧');
		}
		$user=self::$user;
		if(isset($user['id']) && !empty($user['id'])){
			$param['username']=$user['nickname'];
			$param['email']=$user['email'];
			$param['home']=Url::other('author',$user['id']);
			$param['userId']=$user['id'];
		}
		if(empty($param['username'])){
			$this->response('',401,'评论名称不可为空');
		}
		if(!empty($param['email']) && !checkForm('email',$param['email'])){
			$this->response('',401,'邮箱格式错误');
		}
		if(!empty($param['home']) && !checkForm('url',$param['home'])){
			$this->response('',401,'主页网址格式错误');
		}
		if(empty($param['content'])){
			$this->response('',401,'评论内容不可为空');
		}
		if(Config::get('webConfig.commentCN') == 1 && !preg_match('/[\x{4e00}-\x{9fa5}]/iu', $param['content'])){
			$this->response('',401,'评论内容需包含中文');
		}
		if(Config::get('webConfig.commentVcode') == 1){
			if(empty($param['verifyCode'])){
				$this->response('',401,'验证码不可为空');
			}
			if(!(new \rp\Captcha())->check($param['verifyCode'],'comment')){
				$this->response('',402,'验证码错误');
			}
		}
		$data=array();
		$data['logId']=$param['types'] == 'logs' ? $param['vid'] : 0;
		$data['pageId']=$param['types'] == 'pages' ? $param['vid'] : 0;
		$data['topId']=$param['topId'];
		$data['authorId']=$res['authorId'];
		$data['userId']=$param['userId'];
		$data['levels']=$top['levels'] + 1;
		$data['nickname']=subString($param['username'],0,20);
		$data['email']=strip_tags($param['email']);
		$data['home']=strip_tags($param['home']);
		$data['content']=subString($param['content'],0,1000);
		$data['ip']=ip();
		$data['agent']=input('SERVER.HTTP_USER_AGENT');
		$data['createTime']=date('Y-m-d H:i:s');
		$data['status']=Config::get('webConfig.commentCheck') == 1 ? 1 : 0;
		Hook::doHook('api_comment_post',array(&$data));
		$res=Db::name('comment')->insert($data);
		cookie('comment_cookie_last',$newTime, Config::get('webConfig.commentInterval') + 5);
		if($res){
			$msg=Config::get('webConfig.commentCheck') == 1 ? '，等待后台审核' : '';
			Hook::doHook('api_comment_save',array($data));
			Cache::update('total');
			$this->response($res,200,'评论成功'.$msg);
		}
		$this->response('',401,'评论失败');
	}
	
	public function dele(){
		$this->chechAuth(true);
		if(self::$user['role'] != 'admin'){
			$this->response('',401,'无权限操作！');
		}
		$ids=(string)input('ids') ? (string)input('ids') : '';
		$ids=arrayIdFilter($ids);
		$idsArr=explode(',',$ids);
		if(empty($idsArr)){
			$this->response('',401,'无效参数！');
		}
		foreach($idsArr as $k=>$v){
			$data=Db::name('comment')->where(array('id'=>$v))->find();
			$res=Db::name('comment')->where(array('id'=>$v))->dele();
			if($data['status'] == 0){
				$this->updateCommentNum($data,-1);
			}
			if($res){
				$this->dele_child($v);
			}
		}
		Cache::update('total');
		Cache::update('pages');
		Cache::update('user');
		Hook::doHook('api_comment_dele',array($ids));
		$this->response($ids,200,'操作成功！');
	}
	
	public function check(){
		$this->chechAuth(true);
		if(self::$user['role'] != 'admin'){
			$this->response('',401,'无权限操作！');
		}
		$ids=(string)input('ids') ? (string)input('ids') : '';
		$ids=arrayIdFilter($ids);
		if(empty($ids)){
			$this->response('',401,'无效参数！');
		}
		$res=Db::name('comment')->where(array('id'=>array('in',$ids)))->update(array('status'=>0));
		$data=Db::name('comment')->where(array('id'=>array('in',$ids)))->select();
		foreach($data as $k=>$v){
			$this->updateCommentNum($v);
		}
		Cache::update('total');
		Cache::update('pages');
		Cache::update('user');
		Hook::doHook('api_comment_check',array($ids));
		$this->response($ids,200,'操作成功！');
	}
	
	public function replay(){
		global $App;
		$this->chechAuth(true);
		if(self::$user['role'] != 'admin'){
			$this->response('',401,'无权限操作！');
		}
		$id=intval(input('id')) ? intval(input('id')) : 0;
		$content=!empty(input('content')) ? strip_tags(input('content')) : '';
		if(empty($id)){
			$this->response('',401,'无效参数！');
		}
		if(empty($content)){
			$this->response('',401,'回复内容不能为空');
		}
		$comment=Db::name('comment')->where('id='.$id)->find();
		if(empty($comment)){
			$this->response('',401,'该评论不存在，回复失败');
		}
		$data=array(
			'logId'=>$comment['logId'],
			'pageId'=>$comment['pageId'],
			'topId'=>$id,
			'authorId'=>$comment['authorId'],
			'userId'=>self::$user['id'],
			'levels'=>$comment['levels'] + 1,
			'nickname'=>self::$user['nickname'],
			'home'=>$App->baseUrl,
			'content'=>$content,
			'ip'=>ip(),
			'agent'=>input('SERVER.HTTP_USER_AGENT'),
			'createTime'=>date('Y-m-d H:i:s'),
			'status'=>0,
		);
		if($comment['status'] != 0){
			$res=Db::name('comment')->where('id='.$id)->update(array('status'=>0));
		}
		$res=Db::name('comment')->insert($data);
		$this->updateCommentNum($data);
		Cache::update('total');
		Cache::update('pages');
		Cache::update('user');
		Hook::doHook('api_comment_reply',array($data));
		$this->response($id,200,'回复成功！');
	}
	
	
	private function getSon($ids,$where,$sonList=array()){
		$son=Db::name('comment')->alias('a')->where($where)->where(array('a.topId'=>array('in',join(',',$ids))))->field('a.id')->select();
		if(!empty($son)){
			$son=array_column($son,'id');
			$sonList=array_merge($sonList,$son);
			return $this->getSon($son,$where,$sonList);
		}
		return $sonList;
	}
	
	private function dele_child($id){
		$childList=Db::name('comment')->where(array('topId'=>$id))->select();
		foreach($childList as $k=>$v){
			$data=Db::name('comment')->where(array('id'=>$v['id']))->find();
			$res=Db::name('comment')->where(array('id'=>$v['id']))->dele();
			if($data['status'] == 0){
				$this->updateCommentNum($data,-1);
			}
			if($res){
				$this->dele_child($v['id']);
			}
		}
	}
	
	private function updateCommentNum($data,$num=1){
		if(!empty($data['pageId'])){
			$res=Db::name('pages')->where('id='.$data['pageId'])->setInc('comnum',$num);
		}else{
			$res2=Db::name('logs')->where('id='.$data['logId'])->setInc('comnum',$num);
		}
	}
	
}