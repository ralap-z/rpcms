<?php
namespace rp\index;

use rp\Db;
use rp\Config;
use rp\Cache;
use rp\Url;
use rp\Hook;

class LogsMod{
	private $whereArr=array('a.status'=>0);
	private $whereStr='';
	private $order=array('a.id'=>'desc');
	private $page=1;
	private $limit;
	private $pageMax;
	private $tagesData;
	private $cateData;
	private $fieldFirst='a.id';
	
	public function __construct(){
		$this->limit=!empty(Config::get('webConfig.pagesize')) ? Config::get('webConfig.pagesize') : 10;
		$this->pageMax=Config::get('webConfig.pageMax');
		$this->tagesData=Cache::read('tages');
		$this->cateData=Cache::read('category');
		$this->order=(new \rp\index\Base())->getLogOrder();
	}
	
	public function cate($ids){
		if(!is_array($ids)){
			$ids=array($ids);
		}
		$ids=arrayIdFilter($ids);
		$this->whereArr['a.cateId']=array('in',join(',',$ids));
		return $this;
	}
	
	public function cateAilas($name){
		$name=strip_tags(strDeep($name));
		$this->whereArr['b.alias']=$name;
		return $this;
	}
	
	public function author($id){
		$id=intval($id);
		$this->whereArr['a.authorId']=$id;
		return $this;
	}
	
	public function title($title){
		$title=strip_tags(strDeep($title));
		$this->whereArr['a.title']=array('like','%'.$title.'%');
		return $this;
	}
	
	public function search($title){
		$title=strip_tags(strDeep($title));
		$this->whereStr='MATCH(title, content) Against(\''.addslashes($title).'\' IN BOOLEAN MODE)';
		$this->fieldFirst.=',(match(title) Against(\''.addslashes($title).'\' IN BOOLEAN MODE)*2 + match(content) Against(\''.addslashes($title).'\' IN BOOLEAN MODE)) as score';
		$this->order='score';
		return $this;
	}
	
	public function tages($id){
		$this->whereArr['a.tages']=array('find_in_set',$id);
		return $this;
	}
	
	public function order($order){
		$this->order=$order;
		return $this;
	}
	
	public function page($page){
		if(!empty($this->pageMax)){
			$page=min($page,$this->pageMax);
		}
		$this->page=$page;
		return $this;
	}
	
	public function where($where){
		if(is_array($where)){
			$this->whereArr=array_merge($this->whereArr,$where);
		}
		return $this;
	}
	
	public function whereStr($str){
		$this->whereStr=$str;
		return $this;
	}
	
	public function limit($limit){
		$this->limit=$limit;
		return $this;
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
	
	public function select(){
		$sonSql=Db::name('logs')->alias('a')->where($this->whereArr)->where($this->whereStr)->field($this->fieldFirst)->limit(($this->page-1)*$this->limit.','.$this->limit)->order($this->order)->getSql()->select();	
		$list=$this->getData($sonSql);
		Hook::doHook('index_logs_list',array(&$list));
		return array('count'=>0,'limit'=>$this->limit,'page'=>$this->page,'list'=>$list);
	}
	
	public function getData($ids){
		if(empty($ids)) return [];
		$join=array(
			array('category as b force index(PRIMARY)','a.cateId=b.id','left'),
			array('user as c force index(PRIMARY)','a.authorId=c.id','left'),
		);
		$where=[];
		if(is_array($ids)){
			$where['a.id']=['in',join(',',$ids)];
		}else{
			$join[]=array('('.$ids.') as l','a.id=l.id','inner');
		}
		$data=Db::name('logs')->alias('a')->join($join)->where($where)->field('a.id,a.title,a.authorId,a.cateId,a.excerpt,a.keywords,a.content,a.tages,a.isTop,a.views,a.comnum,a.upnum,a.updateTime,a.createTime,a.extend,a.status,b.cate_name as cateName,c.nickname as author,c.email as authorEmail')->select();
		foreach($data as $k=>$v){
			$data[$k]['extend'] =json_decode($v['extend'],true);
			$data[$k]['url'] = Url::logs($v['id']);
			$data[$k]['cateUrl'] = Url::cate($v['cateId']);
			$data[$k]['cateLogNum'] = isset($this->cateData[$v['cateId']]) ? $this->cateData[$v['cateId']]['logNum'] : 0;
			$data[$k]['tagesData'] = $this->getTages($v['tages']);
		}
		return $data;
	}
	
	public function getCount(){
		return Db::name('logs')->alias('a')->where($this->whereArr)->where($this->whereStr)->count();
	}
	
	public function neighbor($logId){
		$where1=$where2=array('a.status'=>0);
		$where1['a.id']=array('<',$logId);
		$where2['a.id']=array('>',$logId);
		$order1=array('a.id'=>'DESC');
		$order2=array('a.id'=>'ASC');
		$prev=Db::name('logs')->alias('a')->where($where1)->field('a.id,a.title,a.authorId,a.cateId,a.excerpt,a.keywords,a.content,a.tages,a.views,a.comnum,a.upnum,a.updateTime,a.createTime,a.extend')->limit(1)->order($order1)->find();
		$next=Db::name('logs')->alias('a')->where($where2)->field('a.id,a.title,a.authorId,a.cateId,a.excerpt,a.keywords,a.content,a.tages,a.views,a.comnum,a.upnum,a.updateTime,a.createTime,a.extend')->limit(1)->order($order2)->find();
		if(!empty($prev)){
			$prev['url']=Url::logs($prev['id']);
			$prev['extend'] =json_decode($prev['extend'],true);
			$prev['cateUrl'] = Url::cate($prev['cateId']);
			$prev['cateLogNum'] = isset($this->cateData[$prev['cateId']]) ? $this->cateData[$prev['cateId']]['logNum'] : 0;
			$prev['tagesData'] = $this->getTages($prev['tages']);
		}else{
			$prev=array();
		}
		if(!empty($next)){
			$next['url']=Url::logs($next['id']);
			$next['extend'] =json_decode($next['extend'],true);
			$next['cateUrl'] = Url::cate($next['cateId']);
			$next['cateLogNum'] = isset($this->cateData[$next['cateId']]) ? $this->cateData[$next['cateId']]['logNum'] : 0;
			$next['tagesData'] = $this->getTages($next['tages']);
		}else{
			$next=array();
		}
		Hook::doHook('index_logs_detail',array(&$prev));
		Hook::doHook('index_logs_detail',array(&$next));
		return array('prev'=>$prev,'next'=>$next);
	}
	
	/*
	*相关文章
	*@param $logData 文章数据，包括tags/cateId/id
	*@param $type 相关类型，tages标签 cate分类
	*@param $limit 获取数量
	*/
	public function related($logData=array(),$type='cate',$limit=10){
		$this->whereArr=array('a.status'=>0);
		$this->whereStr='';
		$this->limit=$limit;
		if($type == 'tages' && !empty($logData['tages'])){
			$this->whereArr['a.tages']=array('exp', '(^|,)('.join('|',arrayIdFilter(array_column($logData['tages'],'id'))).')(,|$)');
		}else{
			$this->whereArr['a.cateId']=intval($logData['cateId']);
		}
		$this->whereArr['a.id']=array('<>',intval($logData['id']));
		$data=$this->select();
		return $data['list'];
	}
	
	public function __get($name=null){
		return isset($this->$name) ? $this->$name : '';
	}
}