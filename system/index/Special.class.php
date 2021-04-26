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
		if(!isset($this->params[1]) || empty($this->params[1])){
			redirect($this->App->baseUrl);
		}
		$data=explode('_',$this->params[1]);
		$page=isset($data[1]) ? intval($data[1]) : 1;
		$special=Cache::read('special');
		if(is_numeric($data[0])){
			$specialId=intval($data[0]);
		}else{
			$special2=array_column($special,NULL,'alias');
			$specialId=isset($special2[$data[0]]) ? $special2[$data[0]]['id'] : '';
		}
		if(empty($specialId) || !isset($special[$specialId])){
			rpMsg('当前专题不存在！');
		}
		$LogsMod=new LogsMod();
		$logData=$LogsMod->page($page)->where(array('a.specialId'=>$specialId))->order(array('a.isTop'=>'desc','a.upateTime'=>'desc','a.id'=>'desc'))->select();
		$title=$special[$specialId]['title'];
		$pageHtml=pageInationHome($logData['count'],$logData['limit'],$logData['page'],'special',$specialId);
		$template=!empty($special[$specialId]['temp_list']) ? $special[$specialId]['temp_list'] : 'special';
		$this->setDescription($special[$specialId]['seo_desc']);
		$this->assign('title',$title.'专题-'.$this->webConfig['webName']);
		$this->assign('listId',$specialId);
		$this->assign('listType','special');
		$this->assign('special',$special[$specialId]);
		$this->assign('logList',$logData['list']);
		$this->assign('pageHtml',$pageHtml);
		return $this->display('/'.$template);
	}
}
