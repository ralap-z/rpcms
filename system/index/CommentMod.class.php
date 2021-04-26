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
		$count=Db::name('comment')->alias('a')->where($this->whereArr)->field('a.id')->count();
		/*
		$pages = ceil($count / $this->limit);
        if($this->page >= $pages && $pages > 0){
            $this->page = $pages;
        }
		*/
		$list=Db::name('comment')->alias('a')->where($this->whereArr)->field('a.*')->order($this->order)->select();
		$list=array_column($list,NULL,'id');
		$commentTopAll=array();
		foreach($list as $k=>$v){
			$list[$k]['nickname'] = strip_tags($v['nickname']);
			$list[$k]['email'] = htmlspecialchars($v['email']);
			$list[$k]['home'] = htmlspecialchars($v['home']);
			$list[$k]['content'] = strip_tags($v['content']);
			!isset($list[$k]['children']) && $list[$k]['children']=array();
			$v['topId'] == 0 && $commentTopAll[]=$v['id'];
			$v['topId'] != 0 && isset($list[$v['topId']]) && $list[$v['topId']]['children'][]=$v['id'];
		}
		if(Config::get('webConfig.commentSort') == 'old'){
			$list=array_reverse($list,true);
			$commentTopAll=array_reverse($commentTopAll);
		}
		
		$commentTop = array_slice($commentTopAll, ($this->page - 1) * $this->limit, $this->limit);
		return array('count'=>count($commentTopAll),'limit'=>$this->limit,'page'=>$this->page,'list'=>array('lists'=>$list,'top'=>$commentTop));
	}
	
	
}