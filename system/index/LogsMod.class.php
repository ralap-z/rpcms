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
	private $tagesData;
	private $cateData;
	
	
	public function __construct(){
		$this->limit=!empty(Config::get('webConfig.pagesize')) ? Config::get('webConfig.pagesize') : 10;
		$this->tagesData=Cache::read('tages');
		$this->cateData=Cache::read('category');
	}
	
	public function cate($ids){
		if(!is_array($ids)){
			$ids=array($ids);
		}
		$ids=arrayIdFilter($ids);
		$this->whereArr['a.cateId']=array('in',$ids);
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
		$this->whereArr['a.title|a.content']=array('like','%'.$title.'%');
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
		$count=Db::name('logs')->alias('a')->join(array(
			array('category b','a.cateId=b.id','left'),
			array('user c','a.authorId=c.id','left'),
		))->where($this->whereArr)->where($this->whereStr)->field('a.id')->count();
		$pages = ceil($count / $this->limit);
        if($this->page >= $pages && $pages > 0){
            $this->page = $pages;
        }
		$list=Db::name('logs')->alias('a')->join(array(
			array('category b','a.cateId=b.id','left'),
			array('user c','a.authorId=c.id','left'),
		))->where($this->whereArr)->where($this->whereStr)->field('a.id,a.title,a.authorId,a.cateId,a.excerpt,a.keywords,a.content,a.tages,a.isTop,a.views,a.comnum,a.upnum,a.upateTime,a.createTime,a.extend,a.status,b.cate_name as cateName,c.nickname as author')->limit(($this->page-1)*$this->limit.','.$this->limit)->order($this->order)->select();
		foreach($list as $k=>$v){
			$list[$k]['extend'] =json_decode($v['extend'],true);
			$list[$k]['url'] = Url::logs($v['id']);
			$list[$k]['cateUrl'] = Url::cate($v['cateId']);
			$list[$k]['cateLogNum'] = isset($this->cateData[$v['cateId']]) ? $this->cateData[$v['cateId']]['logNum'] : 0;
			$list[$k]['tagesData'] = $this->getTages($v['tages']);
		}
		Hook::doHook('index_logs_list',$list);
		return array('count'=>$count,'limit'=>$this->limit,'page'=>$this->page,'list'=>$list);
	}
	
	public function neighbor($logId){
		$where1=$this->whereArr;
		$where2=$this->whereArr;
		$where1['a.id']=array('<',$logId);
		$order1=array('a.id'=>'DESC');
		$where2['a.id']=array('>',$logId);
		$order2=array('a.id'=>'ASC');
		$prev=Db::name('logs')->alias('a')->where($where1)->where($this->whereStr)->field('a.id,a.title,a.authorId,a.cateId,a.excerpt,a.keywords,a.content,a.tages,a.views,a.comnum,a.upnum,a.upateTime,a.createTime,a.extend')->order($order1)->find();
		$next=Db::name('logs')->alias('a')->where($where2)->where($this->whereStr)->field('a.id,a.title,a.authorId,a.cateId,a.excerpt,a.keywords,a.content,a.tages,a.views,a.comnum,a.upnum,a.upateTime,a.createTime,a.extend')->order($order2)->find();
		if(!empty($prev)){
			$prev['url']=Url::logs($prev['id']);
			$prev['extend'] =json_decode($prev['extend'],true);
			$prev['cateUrl'] = Url::cate($prev['cateId']);
			$prev['cateLogNum'] = isset($this->cateData[$prev['cateId']]) ? $this->cateData[$prev['cateId']]['logNum'] : 0;
			$prev['tagesData'] = $this->getTages($prev['tages']);
		}
		if(!empty($next)){
			$next['url']=Url::logs($next['id']);
			$next['extend'] =json_decode($next['extend'],true);
			$next['cateUrl'] = Url::cate($next['cateId']);
			$next['cateLogNum'] = isset($this->cateData[$next['cateId']]) ? $this->cateData[$next['cateId']]['logNum'] : 0;
			$next['tagesData'] = $this->getTages($next['tages']);
		}
		Hook::doHook('index_logs_detail',$prev);
		Hook::doHook('index_logs_detail',$next);
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
		if($type == 'tages'){
			$this->whereArr['a.tages']=array('in',arrayIdFilter($logData['tages']));
		}else{
			$this->whereArr['a.cateId']=intval($logData['cateId']);
		}
		$this->whereArr['a.id']=array('<>',intval($logData['id']));
		
		$list=Db::name('logs')->alias('a')->join(array(
			array('category b','a.cateId=b.id','left'),
			array('user c','a.authorId=c.id','left'),
		))->where($this->whereArr)->where($this->whereStr)->field('a.id,a.title,a.authorId,a.cateId,a.excerpt,a.keywords,a.content,a.tages,a.isTop,a.views,a.comnum,a.upnum,a.upateTime,a.createTime,a.extend,a.status,b.cate_name as cateName,c.nickname as author')->limit('0,'.$limit)->order($this->order)->select();
		foreach($list as $k=>$v){
			$list[$k]['extend'] =json_decode($v['extend'],true);
			$list[$k]['url'] = Url::logs($v['id']);
			$list[$k]['cateUrl'] = Url::cate($v['cateId']);
			$list[$k]['cateLogNum'] = isset($this->cateData[$v['cateId']]) ? $this->cateData[$v['cateId']]['logNum'] : 0;
			$list[$k]['tagesData'] = $this->getTages($v['tages']);
		}
		Hook::doHook('index_logs_list',$list);
		return $list;
	}
}