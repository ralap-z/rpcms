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
		Cache::update('total');
		Hook::doHook('comment_reply',$data);
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
		$ids=input('ids') ? input('ids') : '';
		if(!method_exists($this,'me_'.$type)){
			return json(array('code'=>-1,'msg'=>'无效操作'));
		}
		$idsArr=explode(',',$ids);
		foreach($idsArr as $k=>$v){
			if(!intval($v)) unset($idsArr[$k]);
		}
		if(empty($idsArr)){
			return json(array('code'=>-1,'msg'=>'提交评论项为空'));
		}
		return call_user_func(array($this, 'me_' . $type),join(',',$idsArr));
	}
	
	/*删除评论*/
	private function me_dele($ids){
		$vid=Db::name('comment')->where(array('id'=>array('in',$ids)))->field('logId,pageId')->select();
		$logIds=$pageIds=array();
		foreach($vid as $k=>$v){
			!empty($v['logId']) && $logIds[]=$v['logId'];
			!empty($v['pageId']) && $pageIds[]=$v['pageId'];
		}
		$res1=$res2=array();
		!empty($logIds) && $res1=Db::name('comment')->where(array('topId'=>array('<>',0),'logId'=>array('in',join(',',$logIds))))->field('id,topId')->order('id','asc')->select();
		!empty($pageIds) && $res2=Db::name('comment')->where(array('topId'=>array('<>',0),'pageId'=>array('in',join(',',$pageIds))))->field('id,topId')->order('id','asc')->select();
		$sonList=array_filter(array_merge($res1,$res2));
		$sonArr=explode(',',$ids);
		foreach($sonList as $k=>$v){
			if(in_array($v['topId'],$sonArr)){
                $sonArr[] = $v['id'];
            }
		}
		$res=Db::name('comment')->where(array('id'=>array('in',join(',',$sonArr))))->dele();
		Cache::update('total');
		return json(array('code'=>200,'msg'=>'删除成功'));
	}
	
	/*审核评论*/
	private function me_exam($ids){
		$res=Db::name('comment')->where(array('id'=>array('in',$ids)))->update(array('status'=>0));
		Cache::update('total');
		return json(array('code'=>200,'msg'=>'审核成功'));
	}
	
	/*审核评论*/
	private function me_unexam($ids){
		$res=Db::name('comment')->where(array('id'=>array('in',$ids)))->update(array('status'=>1));
		Cache::update('total');
		return json(array('code'=>200,'msg'=>'反审成功'));
	}
	
	
}
