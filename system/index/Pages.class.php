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
		$title=$pages[$pageId]['title'];
		$GLOBALS['title']=$title;
		$this->assign('title',$title.'-'.$this->webConfig['webName']);
		$this->assign('listId',$pageId);
		if(!empty($pages[$pageId]['password'])){
			$postpwd=input('post.pagepwd');
			$cookiepwd=cookie('rpcms_pagepsw_'.$pageId);
			$this->checkPassword($postpwd,$cookiepwd,$pages[$pageId]['password'],'pagepsw_'.$pageId);
		}
		$data=$pages[$pageId];
		$user=Cache::read('user');
		$content=Db::name('pages')->field('content')->where('id='.$pageId)->find();
		$data['content']=$content['content'];
		$data['author']=$user[$data['authorId']]['nickname'];
		$data['authorUrl']=Url::other('author',$data['authorId']);
		$data['extend'] =json_decode($data['extend'],true);
		Hook::doHook('index_logs_detail',array(&$data));
		$template=!empty($pages[$pageId]['template']) ? $pages[$pageId]['template'] : 'page';
		$CommentData=(new Comment())->getListByPages($pageId);
		Hook::doHook('index_comment',array(&$CommentData));
		$this->setKeywords($pages[$pageId]['seo_key']);
		$this->setDescription($pages[$pageId]['seo_desc']);
		$this->assign('listType','page');
		$this->assign('data',$data);
		$this->assign('CommentData',$CommentData);
		return $this->display('/'.$template);
	}
}
