<?php
namespace rp\index;

use rp\Db;
use rp\Url;
use rp\Cache;
use rp\Hook;
use rp\index\Comment;

class Pages extends base{
	private $params;
	public function __construct($params){
		parent::__construct();
		$this->params=$params;
	}
	
	public function index(){
		if(!isset($this->params['id']) || empty($this->params['id'])){
			redirect($this->App->baseUrl);
		}
		$page=isset($this->params['page']) ? intval($this->params['page']) : 1;
		$pages=Cache::read('pages');
		if(is_numeric($this->params['id'])){
			$pageId=intval($this->params['id']);
		}else{
			$pages2=array_column($pages,NULL,'alias');
			$pageId=isset($pages2[$this->params['id']]) ? $pages2[$this->params['id']]['id'] : '';
		}
		if(empty($pageId) || !isset($pages[$pageId])){
			rpMsg('当前页面不存在！');
		}
		$data=$pages[$pageId];
		unset($pages);
		$title=$data['title'];
		$GLOBALS['title']=$title;
		$this->assign('title',$title.'-'.$this->webConfig['webName']);
		$this->assign('listId',$pageId);
		if(!empty($data['password'])){
			$postpwd=input('post.pagepwd');
			$cookiepwd=cookie('rpcms_pagepsw_'.$pageId);
			$this->checkPassword($postpwd,$cookiepwd,$data['password'],'pagepsw_'.$pageId);
		}
		$user=Db::name('user')->where(array('id'=>$data['authorId']))->field('nickname')->find();
		$content=Db::name('pages')->field('content')->where('id='.$pageId)->find();
		$data['content']=$content['content'];
		$data['author']=$user['nickname'];
		$data['authorUrl']=Url::other('author',$data['authorId']);
		$data['extend'] =json_decode($data['extend'],true);
		Hook::doHook('index_logs_detail',array(&$data));
		$template=!empty($data['template']) ? $data['template'] : 'page';
		$CommentData=(new Comment())->getListByPages($pageId);
		Hook::doHook('index_comment',array(&$CommentData));
		$this->setKeywords($data['seo_key']);
		$this->setDescription($data['seo_desc']);
		$this->assign('listType','page');
		$this->assign('data',$data);
		$this->assign('CommentData',$CommentData);
		return $this->display('/'.$template);
	}
}
