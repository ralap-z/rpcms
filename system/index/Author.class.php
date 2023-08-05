<?php
namespace rp\index;

use rp\Cache;
use rp\index\LogsMod;

class Author extends base{
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
		$user=Cache::read('user');
		if($this->isNumberId($this->params['id'])){
			$userId=intval($this->params['id']);
		}else{
			$user2=array_column($user,NULL,'nickname');
			$userId=isset($user2[$this->params['id']]) ? $user2[$this->params['id']]['id'] : '';
		}
		if(empty($userId) || !isset($user[$userId])){
			rpMsg('当前作者不存在！');
		}
		$LogsMod=new LogsMod();
		$logData=$LogsMod->page($page)->order($this->getLogOrder(array('a.isTop'=>'desc')))->author($userId)->select();
		$logData['count']=$user[$userId]['logNum'];
		$title=$user[$userId]['nickname'];
		$pageHtml=pageInationHome($logData['count'],$logData['limit'],$logData['page'],'author',$userId);
		$this->setKeywords();
		$this->setDescription('关于作者'.$title.'的一些文章整理归档');
		$this->assign('title',$title.'-'.$this->webConfig['webName']);
		$this->assign('listId',$userId);
		$this->assign('listType','author');
		$this->assign('logList',$logData['list']);
		$this->assign('pageHtml',$pageHtml);
		return $this->display('/list');
	}
}
