<?php
namespace rp\index;

use rp\Hook;
use rp\Cache;
use rp\Url;
use rp\Db;
use rp\index\LogsMod;
use rp\index\Comment;

class Logs extends base{
	private $params;
	private $LogsMod;
	public function __construct($params){
		parent::__construct();
		$this->params=$params;
		$this->LogsMod=new LogsMod();
	}
	
	public function index(){
		$page=isset($this->params[2]) ? intval($this->params[2]) : 1;
		$logData=$this->LogsMod->page($page)->order(array('a.upateTime'=>'desc','a.id'=>'desc'))->select();
		$pageHtml=pageInationHome($logData['count'],$logData['limit'],$logData['page'],'index');
		$this->setKeywords();
		$this->setDescription();
		$this->assign('title',$this->webConfig['webName']);
		$this->assign('logList',$logData['list']);
		$this->assign('pageHtml',$pageHtml);
		return $this->display('/index');
	}
	
	public function dates(){
		$page=isset($this->params[3]) ? intval($this->params[3]) : 1;
		$dateStr=isset($this->params[1]) ? strip_tags(strDeep($this->params[1])) : '';	
		if(empty($dateStr)){
			rpMsg('当前栏目不存在！');
		}
		$logData=$this->LogsMod->page($page)->order(array('a.upateTime'=>'desc','a.id'=>'desc'));
		if(strlen($dateStr) == 6){
			$date2=date('Ym',strtotime($dateStr.'01'));
			$logData=$logData->whereStr('DATE_FORMAT(a.createTime,"%Y%m") = "'.$date2.'"');
		}else{
			$dateStr=str_pad($dateStr,8,0,STR_PAD_RIGHT);
			$date2=date('Ymd',strtotime($dateStr));
			$logData=$logData->whereStr('DATE_FORMAT(a.createTime,"%Y%m%d") = "'.$date2.'"');
		}
		$logData=$logData->select();
		$pageHtml=pageInationHome($logData['count'],$logData['limit'],$logData['page'],'date',$date2);
		$date2.='归档';
		$this->setKeywords();
		$this->setDescription($date2.'整理的文章信息，共'.$logData['count'].'篇');
		$this->assign('title',$date2.'-'.$this->webConfig['webName']);
		$this->assign('listId',$date2);
		$this->assign('listType','date');
		$this->assign('logList',$logData['list']);
		$this->assign('pageHtml',$pageHtml);
		return $this->display('/list');
	}
	
	public function search(){
		if(Hook::hasHook('index_search')){
			$data='';
			return Hook::doHook('index_search',$data,true)[0];
		}
		$key=input('q');
		$page=intval(input('page')) ? intval(input('page')) : 1;
		if(empty($key)){
			redirect($this->App->baseUrl);
		}
		$logData=$this->LogsMod->search($key)->page($page)->order(array('a.upateTime'=>'desc','a.id'=>'desc'))->select();
		$pageHtml=pageInationHome($logData['count'],$logData['limit'],$logData['page'],'search',$key);
		$key2='搜索 '.$key;
		$this->setKeywords();
		$this->setDescription('搜索关键词“'.$key.'”的索引结果',true);
		$this->assign('title',$key2.'-'.$this->webConfig['webName']);
		$this->assign('listId',$key);
		$this->assign('listType','search');
		$this->assign('logList',$logData['list']);
		$this->assign('pageHtml',$pageHtml);
		return $this->display('/search');
	}
	
	public function detail(){
		$dateStr=isset($this->params[1]) ? strip_tags(strDeep($this->params[1])) : '';
		$logAlias=array_filter(Cache::read('logAlias'));
		if(is_numeric($dateStr)){
			$logId=intval($dateStr);
		}else{
			$logAlias2=array_flip($logAlias);
			$logId=isset($logAlias2[$dateStr]) ? $logAlias2[$dateStr] : '';
		}
		$data=Db::name('logs')->where('id='.$logId)->find();
		if(empty($logId) || empty($data)){
			rpMsg('当前文章不存在！');
		}
		if($data['status'] != 0){
			rpMsg('当前文章未发布，请等待发布后再查看！');
		}
		$category=Cache::read('category');
		$data['cateName']=isset($category[$data['cateId']]['cate_name']) ? $category[$data['cateId']]['cate_name'] : '未分类';
		$GLOBALS['title']=$data['title'];
		$this->assign('title',$data['title'].'-'.$data['cateName'].'-'.$this->webConfig['webName']);
		$this->assign('listId',$logId);
		if(!empty($data['password'])){
			$postpwd=input('post.pagepwd');
			$cookiepwd=cookie('rpcms_logspsw_'.$logId);
			$this->checkPassword($postpwd,$cookiepwd,$data['password'],'logspsw_'.$logId);
		}
		$tages=Cache::read('tages');
		$user=Cache::read('user');
		$tagName=array();
		$tagArr=explode(',',$data['tages']);
		foreach($tagArr as $v){
			if(isset($tages[$v])){
				$tagName[]=array(
					'id'=>$v,
					'name'=>$tages[$v]['tagName'],
					'url'=>Url::tag($v),
				);
			}
		}
		$data['tages']=$tagName;
		$data['cateUrl']=!empty($data['cateId']) ? Url::cate($data['cateId']) : '';
		$data['author']=$user[$data['authorId']]['nickname'];
		$data['authorUrl']=Url::other('author',$data['authorId']);
		$data['extend'] =json_decode($data['extend'],true);
		Hook::doHook('index_logs_detail',$data);
		if(!empty($data['template'])){
			$template=$data['template'];
		}elseif(!empty($category[$data['cateId']]['temp_logs'])){
			$template=$category[$data['cateId']]['temp_logs'];
		}else{
			$template='detail';
		}
		$CommentData=(new Comment())->getListByLogs($logId);
		$res=Db::name('logs')->where('id='.$logId)->setInc('views');
		$this->setKeywords($data['keywords']);
		$this->setDescription($data['excerpt']);
		$this->assign('listType','logs');
		$this->assign('data',$data);
		$this->assign('CommentData',$CommentData);
		return $this->display('/'.$template);
	}
	
	public function praise(){
		$id=intval(input('id')) ? intval(input('id')) : 0;
		if(empty($id)){
			return json(array('code'=>-1,'msg'=>'数据错误'));
		}
		if(!$res=Db::name('logs')->where('id='.$id)->field('status,upnum')->find()){
			return json(array('code'=>-1,'msg'=>'该文章不存在'));
		}
		if($res['status'] != 0){
			return json(array('code'=>-1,'msg'=>'该文章暂未发布，不可点赞'));
		}
		$lastTime=cookie('me_praise_'.$id);
		if(!empty($lastTime)){
			return json(array('code'=>-1,'msg'=>'你已点过赞了！'));
		}
		$res2=Db::name('logs')->where('id='.$id)->setInc('upnum');
		if($res2){
			cookie('me_praise_'.$id,$id,365*24*60*60);
			return json(array('code'=>200,'msg'=>'点赞成功，感谢您的支持！', 'data'=>$res['upnum'] + 1));
		}
		return json(array('code'=>-1,'msg'=>'点赞失败，请稍后重试'));
	}
}
