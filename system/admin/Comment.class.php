<?php
namespace rp\admin;
use rp\View;
use rp\Db;
use rp\Cache;
use rp\Hook;

class Comment extends Base{
	
	public function __construct(){
		parent::__construct();
	}
	
	public function index(){
		$page=intval(input('page')) ? intval(input('page')) : 1;
		$limit=10;
		$count=Db::name('comment')->alias('a')->count();
		$pages = ceil($count / $limit);
        if($page >= $pages && $pages > 0){
            $page = $pages;
        }
		$res=Db::name('comment')->alias('a')->join(array(
			array('logs b','a.logId=b.id','left'),
			array('pages c','a.pageId=c.id','left'),
		))->field('a.*,b.title as logTitle,c.title as pagesTitle')->order(array('a.status'=>'desc','a.id'=>'desc'))->limit(($page-1)*$limit.','.$limit)->select();
		$pageHtml=pageInation($count,$limit,$page);
		View::assign('list',$res);
		View::assign('pageHtml',$pageHtml);
		return View::display('/comment_index');
	}
	
	public function getInfo(){
		$id=intval(input('id')) ? intval(input('id')) : 0;
		if(empty($id)){
			return json(array('code'=>-1,'msg'=>'ID数据错误'));
		}
		$comment=Db::name('comment')->where('id='.$id)->field('id,nickname,email,home,content,createTime')->find();
		if(empty($comment)){
			return json(array('code'=>-1,'msg'=>'该评论不存在'));
		}
		return json(array('code'=>200,'msg'=>'success', 'data'=>$comment));
	}
	
	public function replay(){
		$id=intval(input('id')) ? intval(input('id')) : 0;
		$content=!empty(input('content')) ? strip_tags(input('content')) : '';
		if(empty($id)){
			return json(array('code'=>-1,'msg'=>'ID数据错误'));
		}
		if(empty($content)){
			return json(array('code'=>-1,'msg'=>'回复内容不能为空'));
		}
		$comment=Db::name('comment')->where('id='.$id)->find();
		if(empty($comment)){
			return json(array('code'=>-1,'msg'=>'该评论不存在，回复失败'));
		}
		$app=$this->App;
		$data=array(
			'logId'=>$comment['logId'],
			'pageId'=>$comment['pageId'],
			'topId'=>$id,
			'authorId'=>$comment['authorId'],
			'userId'=>$this->user['id'],
			'levels'=>$comment['levels'] + 1,
			'nickname'=>$this->user['nickname'],
			'home'=>$app->baseUrl,
			'content'=>$content,
			'ip'=>ip(),
			'agent'=>$app::server('HTTP_USER_AGENT'),
			'createTime'=>date('Y-m-d H:i:s'),
			'status'=>0,
		);
		if($comment['status'] != 0){
			$res=Db::name('comment')->where('id='.$id)->update(array('status'=>0));
		}
		$res=Db::name('comment')->insert($data);
		Hook::doHook('comment_reply',array($data));
		$this->me_updateCommentNum($data);
		$this->me_updateCache();
		return json(array('code'=>200,'msg'=>'回复成功'));
	}
	
	public function doUpdate(){
		$id=intval(input('id')) ? intval(input('id')) : 0;
		$nickname=!empty(input('nickname')) ? strip_tags(input('nickname')) : '';
		$email=!empty(input('email')) ? strip_tags(input('email')) : '';
		$home=!empty(input('home')) ? strip_tags(input('home')) : '';
		$content=!empty(input('content')) ? strip_tags(input('content')) : '';
		if(empty($id)){
			return json(array('code'=>-1,'msg'=>'ID数据错误'));
		}
		if(empty($nickname)){
			return json(array('code'=>-1,'msg'=>'评论人不能为空'));
		}
		if(empty($content)){
			return json(array('code'=>-1,'msg'=>'评论内容不能为空'));
		}
		$res=Db::name('comment')->where('id='.$id)->update(array('nickname'=>$nickname,'email'=>$email,'home'=>$home,'content'=>$content));
		if(!$res){
			return json(array('code'=>-1,'msg'=>'修改失败，请刷新重试'));
		}
		return json(array('code'=>200,'msg'=>'修改成功'));
	}
	
	public function oper(){
		$type=input('type') ? input('type') : '';
		$ids=(string)input('ids') ? (string)input('ids') : '';
		if(!method_exists($this,'me_'.$type)){
			return json(array('code'=>-1,'msg'=>'无效操作'));
		}
		$idsArr=arrayIdFilter($ids);
		if(empty($idsArr)){
			return json(array('code'=>-1,'msg'=>'提交评论项为空'));
		}
		return call_user_func(array($this, 'me_' . $type),$idsArr);
	}
	
	/*删除评论*/
	private function me_dele($ids){
		$ids=explode(',',$ids);
		foreach($ids as $k=>$v){
			$data=Db::name('comment')->where(array('id'=>$v))->find();
			$res=Db::name('comment')->where(array('id'=>$v))->dele();
			if($data['status'] == 0){
				$this->me_updateCommentNum($data,-1);
			}
			if($res){
				$this->me_dele_child($v);
			}
		}
		$this->me_updateCache();
		return json(array('code'=>200,'msg'=>'删除成功'));
	}
	
	/*审核评论*/
	private function me_exam($ids){
		$res=Db::name('comment')->where(array('id'=>array('in',$ids)))->update(array('status'=>0));
		$data=Db::name('comment')->where(array('id'=>array('in',$ids)))->select();
		foreach($data as $k=>$v){
			$this->me_updateCommentNum($v);
		}
		$this->me_updateCache();
		return json(array('code'=>200,'msg'=>'审核成功'));
	}
	
	/*审核评论*/
	private function me_unexam($ids){
		$res=Db::name('comment')->where(array('id'=>array('in',$ids)))->update(array('status'=>1));
		$data=Db::name('comment')->where(array('id'=>array('in',$ids)))->select();
		foreach($data as $k=>$v){
			$this->me_updateCommentNum($v,-1);
		}
		$this->me_updateCache();
		return json(array('code'=>200,'msg'=>'反审成功'));
	}
	
	private function me_dele_child($id){
		$childList=Db::name('comment')->where(array('topId'=>$id))->select();
		foreach($childList as $k=>$v){
			$data=Db::name('comment')->where(array('id'=>$v['id']))->find();
			$res=Db::name('comment')->where(array('id'=>$v['id']))->dele();
			if($data['status'] == 0){
				$this->me_updateCommentNum($data,-1);
			}
			if($res){
				$this->me_dele_child($v['id']);
			}
		}
	}
	
	private function me_updateCommentNum($data,$num=1){
		if(!empty($data['pageId'])){
			$res=Db::name('pages')->where('id='.$data['pageId'])->setInc('comnum',$num);
		}else{
			$res2=Db::name('logs')->where('id='.$data['logId'])->setInc('comnum',$num);
		}
	}
	
	private function me_updateCache(){
		Cache::update('total');
		Cache::update('pages');
		Cache::update('user');
	}
}