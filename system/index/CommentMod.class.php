<?php
namespace rp\index;

use rp\Db;
use rp\Config;
use rp\Url;

class CommentMod{
	private $whereArr=array('a.status'=>0);
	private $order=array('a.id'=>'desc');
	private $page=1;
	private $limit;
	
	
	public function __construct(){
		$this->limit=!empty(Config::get('webConfig.commentPage')) ? Config::get('webConfig.commentPage') : 10;
	}
	
	public function logs($id){
		$id=intval($id);
		$this->whereArr['a.logId']=$id;
		return $this;
	}
	
	public function html($id){
		$id=intval($id);
		$this->whereArr['a.pageId']=$id;
		return $this;
	}
	
	public function author($id){
		$id=intval($id);
		$this->whereArr['a.authorId']=$id;
		return $this;
	}
	
	public function user($id){
		$id=intval($id);
		$this->whereArr['a.userId']=$id;
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
	
	public function limit($limit){
		$this->limit=$limit;
		return $this;
	}
	
	public function select(){
		$count=Db::name('comment')->alias('a')->where($this->whereArr)->where(array('a.topId'=>0))->field('a.id')->count();
		$commentTop=Db::name('comment')->alias('a')->where($this->whereArr)->where(array('a.topId'=>0))->field('a.id')->limit(($this->page-1)*$this->limit.','.$this->limit)->order($this->order)->select();
		$commentTop=array_column($commentTop,'id');
		$list=array();
		if(!empty($commentTop)){
			$commentSon=$this->getSon($commentTop);
			$list=Db::name('comment')->alias('a')->where($this->whereArr)->where(array('a.id'=>array('in',join(',',array_merge($commentTop,$commentSon)))))->field('a.*')->order($this->order)->select();
			$list=array_column($list,NULL,'id');
			foreach($list as $k=>$v){
				$list[$k]['nickname'] = strip_tags($v['nickname']);
				$list[$k]['email'] = htmlspecialchars($v['email']);
				$list[$k]['home'] = htmlspecialchars($v['home']);
				$list[$k]['content'] = htmlspecialchars($v['content']);
				!isset($list[$k]['children']) && $list[$k]['children']=array();
				$v['topId'] != 0 && isset($list[$v['topId']]) && $list[$v['topId']]['children'][]=$v['id'];
			}
		}
		return array('count'=>$count,'limit'=>$this->limit,'page'=>$this->page,'list'=>array('lists'=>$list,'top'=>$commentTop));
	}
	
	private function getSon($ids,$sonList=array()){
		$son=Db::name('comment')->alias('a')->where($this->whereArr)->where(array('a.topId'=>array('in',join(',',$ids))))->field('a.id')->select();
		if(!empty($son)){
			$son=array_column($son,'id');
			$sonList=array_merge($sonList,$son);
			return $this->getSon($son,$sonList);
		}
		return $sonList;
	}
}