<?php
namespace rp\api;

use rp\Cache;
use rp\Url;
use rp\Db;
use rp\Hook;

class Page extends Base{
	
	public function __construct(){
		parent::__construct();
	}
	
	public function getData(){
		$id=(int)input('id');
		$pages=Cache::read('pages');
		if(!isset($pages[$id])){
			$this->response('',404,'页面不存在！');
		}
		$data=$pages[$id];
		$user=Cache::read('user');
		$content=Db::name('pages')->field('content')->where('id='.$id)->find();
		$data['content']=$content['content'];
		$data['author']=$user[$data['authorId']]['nickname'];
		$data['authorUrl']=Url::other('author',$data['authorId']);
		$data['extend'] =json_decode($data['extend'],true);
		Hook::doHook('api_page_detail',array(&$data));
		unset($data['extend']);
		unset($data['password']);
		$this->response($data);
	}
	
	public function post(){
		$this->chechAuth(true);
		$param=input('post.');
		$default=array(
			'id'=>0,
			'title'=>'',
			'content'=>'',
			'seo_key'=>'',
			'seo_desc'=>'',
			'authorId'=>'',
			'alias'=>'',
			'password'=>'',
			'template'=>'',
			'createTime'=>'',
			'isRemark'=>'',
			'extend'=>'',
			'status'=>0,
		);
		$param=array_merge($default,$param);
		$pageId=intval($param['id']) ? intval($param['id']) : 0;
		if(self::$user['role'] != 'admin'){
			$param['authorId']=self::$user['id'];
		}
		if(!empty($pageId) && self::$user['role'] != 'admin'){
			$data=Db::name('pages')->where('id='.$pageId)->field('authorId')->find();
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
		$data['seo_key']=strip_tags($param['seo_key']);
		$data['seo_desc']=strip_tags($param['seo_desc']);
		$data['authorId']=intval($param['authorId']);
		$data['alias']=strip_tags($param['alias']);
		$data['password']=strip_tags($param['password']);
		$data['template']=strip_tags($param['template']);
		$data['createTime']=!empty($param['createTime']) ? date('Y-m-d H:i:s',strtotime($param['createTime'])) : date('Y-m-d H:i:s');
		$data['isRemark']=!empty($param['isRemark']) ? intval($param['isRemark']) : 0;
		$data['extend']=$this->extendPost($param);
		$data['status']=0;
		$this->checkAlias($data['alias']);
		$this->checkTemplate($data['template']);
		$pagesAlias=array_column(Cache::read('pages'),'alias','id');
		if(!empty($pageId)){
			$key=array_search($data['alias'],$pagesAlias);
			if(!empty($data['alias']) && ($key && $key != $pageId)){
				$this->response('',401,'别名重复，请更换别名！');
			}
			if(Db::name('nav')->where(array('types'=>3,'typeId'=>$pageId))->find()){
				$res=Db::name('nav')->where(array('types'=>3,'typeId'=>$pageId))->update(array('navname'=>$data['title']));
				Cache::update('nav');
			}
			$res=Db::name('pages')->where('id='.$pageId)->update($data);
		}else{
			if(!empty($data['alias']) && array_search($data['alias'],$pagesAlias)){
				$this->response('',401,'别名重复，请更换别名！');
			}
			$pageId=Db::name('pages')->insert($data);
		}
		Cache::update('pages');
		Cache::update('total');
		Hook::doHook('api_pages_save',array($pageId));
		$this->response($pageId,200,'操作成功！');
	}
	
	public function dele(){
		$this->chechAuth(true);
		$ids=input('post.ids');
		$idsArr=explode(',',$ids);
		foreach($idsArr as $k=>$v){
			if(!intval($v)) unset($idsArr[$k]);
		}
		if(empty($idsArr)){
			$this->response('',401,'无效参数！');
		}
		if(self::$user['role'] != 'admin'){
			$idsSelect=Db::name('pages')->where(array('authorId'=>self::$user['id'],'id'=>array('in',join(',',$idsArr))))->field('id')->select();
			$idsArr=array_column($idsSelect,'id');
		}
		$ids=join(',',$idsArr);
		$res=Db::name('pages')->where(array('id'=>array('in',$ids)))->dele();
		Cache::update('pages');
		Cache::update('total');
		Hook::doHook('api_pages_dele',array($ids));
		$this->response($ids,200,'操作成功！');
	}
	
}