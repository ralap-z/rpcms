<?php
namespace rp\index;

use rp\Cache;
use rp\index\LogsMod;

class Special extends base{
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
		$special=Cache::read('special');
		if(is_numeric($this->params['id'])){
			$specialId=intval($this->params['id']);
		}else{
			$special2=array_column($special,NULL,'alias');
			$specialId=isset($special2[$this->params['id']]) ? $special2[$this->params['id']]['id'] : '';
		}
		if(empty($specialId) || !isset($special[$specialId])){
			rpMsg('当前专题不存在！');
		}
		$specialData=$special[$specialId];
		unset($special);
		$LogsMod=new LogsMod();
		$logData=$LogsMod->page($page)->where(array('a.specialId'=>$specialId))->order($this->getLogOrder(array('a.isTop'=>'desc')))->select();
		$logData['count']=$specialData['logNum'];
		$title=!empty($specialData['seo_title']) ? $specialData['seo_title'] : $specialData['title'].'专题';
		$pageHtml=pageInationHome($logData['count'],$logData['limit'],$logData['page'],'special',$specialId);
		$template=!empty($specialData['temp_list']) ? $specialData['temp_list'] : 'special';
		$this->setDescription($specialData['seo_desc']);
		$this->assign('title',$title.'-'.$this->webConfig['webName']);
		$this->assign('listId',$specialId);
		$this->assign('listType','special');
		$this->assign('special',$specialData);
		$this->assign('logList',$logData['list']);
		$this->assign('pageHtml',$pageHtml);
		return $this->display('/'.$template);
	}
}
