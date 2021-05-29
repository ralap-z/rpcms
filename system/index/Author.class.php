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
		if(!isset($this->params[1]) || empty($this->params[1])){
			redirect($this->App->baseUrl);
		}
		$data=explode('_',$this->params[1]);
		$page=isset($data[1]) ? intval($data[1]) : 1;
		$user=Cache::read('user');
		if(is_numeric($data[0])){
			$userId=intval($data[0]);
		}else{
			$user2=array_column($user,NULL,'nickname');
			$userId=isset($user2[$data[0]]) ? $user2[$data[0]]['id'] : '';
		}
		if(empty($userId) || !isset($user[$userId])){
			rpMsg('当前作者不存在！');
		}
		$LogsMod=new LogsMod();
		$logData=$LogsMod->page($page)->order($this->getLogOrder(array('a.isTop'=>'desc')))->author($userId)->select();
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
