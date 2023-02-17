<?php
namespace rp\api;

use rp\Db;
use rp\Cache;
use rp\Url;
use rp\Config;
use rp\Hook;

class Logs extends Base{
	
	private $limit;
	private $tagesData;
	private $cateData;
	private $config;
	
	public function __construct(){
		parent::__construct();
		$limit=(int)input('limit');
		$this->limit=!empty($limit) ? $limit : (!empty(Config::get('webConfig.pagesize')) ? Config::get('webConfig.pagesize') : 10);
		$this->tagesData=Cache::read('tages');
		$this->cateData=Cache::read('category');
		$this->config=Cache::read('option');
	}
	
	public function getList(){
		$cateId=(string)input('cate');//支持多分类，如：1,2,3
		$authorId=(int)input('author');
		$date=input('date');//支持202102和20210325
		$tag=(int)input('tag');
		$key=(string)input('q');
		$page=(int)input('page') ? (int)input('page') : 1;
		$where=array('a.status'=>0);
		$wherestr=array();
		$cateId=arrayIdFilter($cateId);
		$pageMax=Config::get('webConfig.pageMax');
		if(!empty($pageMax)){
			$page=min($page,$pageMax);
		}
		if(!empty($cateId)){
			$where['a.cateId']=array('in',$cateId);
		}
		if(!empty($authorId)){
			$where['a.authorId']=$authorId;
		}
		if(!empty($date)){
			if(strlen($date) == 6){
				$date2=date('Ym',strtotime($date.'01'));
				$dataStart=$date2.'01';
				$wherestr[]='a.createTime BETWEEN "'.date('Y-m-d 00:00:00',strtotime($dataStart)).'" AND "'.date('Y-m-d 23:59:59',strtotime($dataStart." +1 month -1 day")).'"';
			}else{
				$date=str_pad($date,8,0,STR_PAD_RIGHT);
				$date2=date('Ymd',strtotime($date));
				$wherestr[]='a.createTime BETWEEN "'.date('Y-m-d 00:00:00',strtotime($date2)).'" AND "'.date('Y-m-d 23:59:59',strtotime($date2)).'"';
			}
		}
		if(!empty($tag)){
			$tag=arrayIdFilter($tag);
			$where['a.tages']=array('find_in_set',$tag);
		}
		if(!empty($key)){
			$key=strip_tags(strDeep($key));
			$where['a.title']=array('like','%'.$key.'%');
		}
		$order=$this->getOrder(array('id'=>'a','isTop'=>'a','views'=>'a','comnum'=>'a','upnum'=>'a','updateTime'=>'a','createTime'=>'a'));
		
		$count=Db::name('logs')->alias('a')->where($where)->where(join(' and ',$wherestr))->count();
		$list=Db::name('logs')->alias('a')->join(array(
			array('category as b force index(PRIMARY)','a.cateId=b.id','left'),
			array('user as c force index(PRIMARY)','a.authorId=c.id','left'),
		))->where($where)->where(join(' and ',$wherestr))->field('a.id,a.title,a.authorId,a.cateId,a.excerpt,a.keywords,a.content,a.tages,a.isTop,a.views,a.comnum,a.upnum,a.updateTime,a.createTime,a.status,b.cate_name as cateName,c.nickname as author')->limit(($page-1)*$this->limit.','.$this->limit)->order($order)->select();
		foreach($list as $k=>$v){
			$list[$k]['url'] = Url::logs($v['id']);
			$list[$k]['cateUrl'] = Url::cate($v['cateId']);
			$list[$k]['cateLogNum'] = isset($this->cateData[$v['cateId']]) ? $this->cateData[$v['cateId']]['logNum'] : 0;
			$list[$k]['tagesData'] = $this->getTages($v['tages']);
			$list[$k]['images'] = $this->thumb($v['content']);
		}
		Hook::doHook('api_logs_list',array(&$list));
		$page=array('count'=>$count,'pageAll'=>ceil($count / $this->limit),'limit'=>$this->limit,'pageNow'=>$page);
		$this->response(array('list'=>$list,'pageBar'=>$page));
	}
	
	public function getData(){
		$id=(int)input('id');
		$password=input('password');
		$data=Db::name('logs')->where(array('id'=>$id))->find();
		if(empty($data) || $data['status'] != 0){
			$this->response('',404,'文章不存在或未发布！');
		}
		$category=Cache::read('category');
		$data['cateName']=isset($category[$data['cateId']]['cate_name']) ? $category[$data['cateId']]['cate_name'] : '未分类';
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
		if(!empty($data['password']) && !$this->checkPassword($password,$data['password'])){
			$data['isShow']=false;
			$data['content']='';
		}else{
			$data['isShow']=true;
			$data['content']=$this->pregReplaceImg($data['content'],(new \rp\App)->baseUrl);
		}
		Hook::doHook('api_logs_detail',array(&$data));
		unset($data['extend']);
		unset($data['password']);
		$this->response($data);
	}
	
	public function praise(){
		$id=(int)input('id');
		$data=Db::name('logs')->where(array('id'=>$id))->field('status,upnum')->find();
		if(empty($data) || $data['status'] != 0){
			$this->response('',404,'文章不存在或未发布！');
		}
		$lastTime=cookie('me_praise_'.$id);
		if(!empty($lastTime)){
			$this->response('',401,'你已点过赞了！');
		}
		$res2=Db::name('logs')->where(array('id'=>$id))->setInc('upnum');
		if($res2){
			cookie('me_praise_'.$id,$id,365*24*60*60);
			$this->response(array('num'=>$res['upnum'] + 1,'result'=>'点赞成功，感谢您的支持！'),200);
		}
		$this->response('',401,'点赞失败！');
	}
	
	public function post(){
		$this->chechAuth(true);
		$param=input('post.');
		$default=array(
			'id'=>0,
			'title'=>'',
			'content'=>'',
			'excerpt'=>'',
			'keywords'=>'',
			'cateId'=>'',
			'authorId'=>'',
			'specialId'=>'',
			'alias'=>'',
			'password'=>'',
			'template'=>'',
			'createTime'=>'',
			'isTop'=>'',
			'isRemark'=>'',
			'extend'=>'',
			'tagesName'=>'',
			'status'=>0,
			'type'=>3,
		);
		$param=array_merge($default,$param);
		$logid=intval($param['id']) ? intval($param['id']) : 0;
		if(self::$user['role'] != 'admin'){
			$param['authorId']=self::$user['id'];
		}
		if(!empty($logid) && self::$user['role'] != 'admin'){
			$data=Db::name('logs')->where(array('id'=>$logid))->field('authorId')->find();
			(empty($data) || $data['authorId'] != self::$user['id']) && $this->response('',401,'无权限操作！');
		}
		$data=array();
		$data['title']=strip_tags($param['title']);
		$data['content']=clear_html($param['content'],array('script'));
		if(empty($data['title'])){
			$this->response('',401,'标题不能为空！');
		}
		if(empty($data['content'])){
			$this->response('',401,'正文不能为空！');
		}
		$data['excerpt']=!empty(strip_tags($param['excerpt'])) ? strip_tags($param['excerpt']) : getContentByLength($param['content']);
		$data['keywords']=str_replace('，',',',strip_tags($param['keywords']));
		$data['cateId']=intval($param['cateId']);
		$data['authorId']=intval($param['authorId']);
		$data['specialId']=intval($param['specialId']);
		$data['alias']=strip_tags($param['alias']);
		$data['password']=strip_tags($param['password']);
		$data['template']=strip_tags($param['template']);
		$data['createTime']=!empty($param['createTime']) ? date('Y-m-d H:i:s',strtotime($param['createTime'])) : date('Y-m-d H:i:s');
		$data['updateTime']=date('Y-m-d H:i:s');
		$data['isTop']=!empty($param['isTop']) ? intval($param['isTop']) : 0;
		$data['isRemark']=!empty($param['isRemark']) ? intval($param['isRemark']) : 0;
		$data['extend']=$this->extendPost($param);
		$data['status']=intval($param['type']) == 3 ? intval($param['status']) : intval($param['type']);
		$this->checkAlias($data['alias']);
		$this->checkTemplate($data['template']);
		$oldData=[];
		if(!empty($logid)){
			$oldData=Db::name('logs')->where(array('id'=>$logid))->find();
		}
		if($param['type'] != 2){
			$data['tages']=$this->replaceTages($param['tagesName'], $oldData);
		}
		$checkAlias=array();
		if(!empty($data['alias'])){
			$checkAlias=Db::name('logs')->where(array('alias'=>$data['alias']))->field('id')->find();
		}else{
			unset($data['alias']);
		}
		if(!empty($logid)){
			if(!empty($checkAlias) && $checkAlias['id'] != $logid){
				$this->response('',401,'别名重复，请更换别名！');
			}
			$res=Db::name('logs')->where(array('id'=>$logid))->update($data);
		}else{
			if(!empty($checkAlias)){
				$this->response('',401,'别名重复，请更换别名！');
			}
			$logid=Db::name('logs')->insert($data);
		}
		if(!empty($data['specialId'])){
			Db::name('special')->where(array('id'=>$data['specialId']))->update(array('updateTime'=>date('Y-m-d H:i:s')));
		}
		if($param['type'] != 2){
			$this->updateCache(5);
		}
		Hook::doHook('api_logs_save',array($logid));
		$this->response($logid,200,'操作成功！');
	}
	
	public function dele(){
		$this->chechAuth(true);
		$ids=(string)input('post.ids');
		$ids=arrayIdFilter($ids);
		if(empty($ids)){
			$this->response('',401,'无效参数！');
		}
		if(self::$user['role'] != 'admin'){
			$idsSelect=Db::name('logs')->where(array('authorId'=>self::$user['id'],'id'=>array('in',$ids)))->field('id')->select();
			$ids=array_column($idsSelect,'id');
			$ids=join(',',$ids);
		}
		$tages=Db::name('logs')->where(array('id'=>array('in',$ids)))->field('tages')->select();
		$tagesNum=[];
		$tages=array_map(function($v){return explode(',', $v['tages']);}, $tages);
		$tages=array_filter(array_reduce($tages, 'array_merge', array()));
		foreach($tages as $v){
			if(isset($tagesNum[$v])){
				$tagesNum[$v]++;
			}else{
				$tagesNum[$v]=1;
			}
		}
		unset($tages);
		Db::transaction();
		try{
			foreach($tagesNum as $k=>$v){
				Db::name('tages')->where(['id'=>$k])->setDec('logNum', $v);
			}
			$res=Db::name('logs')->where(array('id'=>array('in',$ids)))->dele();//删除文章
			$res=Db::name('attachment')->where(array('logId'=>array('in',$ids)))->dele();//删除附件
			$res=Db::name('comment')->where(array('logId'=>array('in',$ids)))->dele();//删除评论
			Db::commit();
		}catch(\Exception $e){
			Db::rollback();
			$this->response($ids,-1,'删除失败');
		}
		$this->updateCache(5);
		Hook::doHook('api_logs_dele',array($ids));
		$this->response($ids,200,'操作成功！');
	}
	
	private function getTages($tags){
		$tagData=array();
		$tagArr=explode(',',$tags);
		foreach($tagArr as $v){
			if(isset($this->tagesData[$v])){
				$tagData[]=array(
					'id'=>$v,
					'name'=>$this->tagesData[$v]['tagName'],
					'url'=>Url::tag($v),
				);
			}
		}
		return $tagData;
	}
	
	private function replaceTages($tages, $tagesOld=[]){
		$tages=str_replace(array(';','，','、'), ',', $tages);
		$tages=RemoveSpaces(strip_tags($tages));
		$tagesArr=explode(',', $tages);
		$tagesArr=array_unique(array_filter($tagesArr));
		if(empty($tagesArr)) return '';
		$tagesArr=array_slice($tagesArr, 0, 10);//最多10个标签
		$data=array();
		$tagesAll=Cache::read('tages');
		$tagesAll=array_column($tagesAll,NULL,'tagName');
		foreach($tagesArr as $value){
			if(isset($tagesAll[$value])){
				$data[]=$tagesAll[$value]['id'];
			}else{
				$data[]=Db::name('tages')->insert(array('tagName'=>$value));
			}
		}
		if(!empty($tagesOld)){
			$tagesOld=array_filter(explode(',', $tagesOld['tages']));
		}
		$deleTages=array_diff($tagesOld, $data);
		$addTages=array_diff($data, $tagesOld);
		if(!empty($deleTages)){
			Db::name('tages')->where(array('id'=>array('in', join(',',$deleTages))))->setDec('logNum');
		}
		if(!empty($addTages)){
			Db::name('tages')->where(array('id'=>array('in', join(',',$addTages))))->setInc('logNum');
		}
		return join(',',$data);
	}
	
	private function updateCache($level){
		if(!isset($this->config['isPostUpCache']) || $this->config['isPostUpCache'] != 1) return;
		Cache::update('category');
		if($level < 5) return;
		Cache::update('tages');
		Cache::update('special');
		Cache::update('total');
		Cache::update('logRecord');
	}
}