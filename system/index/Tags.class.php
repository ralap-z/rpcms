<?php
namespace rp\index;

use rp\Cache;
use rp\index\LogsMod;

class Tags extends base{
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
		$tages=Cache::read('tages');
		$tages2=array_column($tages,NULL,'alias');
		$tages3=array_column($tages,NULL,'tagName');
		if(is_numeric($data[0])){
			$tagId=intval($data[0]);
		}elseif(isset($tages2[$data[0]])){
			$tagId=$tages2[$data[0]]['id'];
		}elseif(isset($tages3[$data[0]])){
			$tagId=$tages3[$data[0]]['id'];
		}else{
			$tagId='';
		}
		if(empty($tagId) || !isset($tages[$tagId])){
			rpMsg('当前标签不存在！');
		}
		$LogsMod=new LogsMod();
		$logData=$LogsMod->page($page)->order($this->getLogOrder(array('a.isTop'=>'desc')))->tages($tagId)->select();
		$logData['count']=$tages[$tagId]['logNum'];
		$title=$tages[$tagId]['tagName'];
		$pageHtml=pageInationHome($logData['count'],$logData['limit'],$logData['page'],'tag',$tagId);
		$template=!empty($tages[$tagId]['template']) ? $tages[$tagId]['template'] : 'list';
		$this->setKeywords($title);
		if(!empty($tages[$tagId]['seo_desc'])){
			$this->setDescription($tages[$tagId]['seo_desc']);
		}else{
			$this->setDescription('关于“'.$title.'”标签的所有文章信息');
		}
		$this->assign('title',$title.'-'.$this->webConfig['webName']);
		$this->assign('listId',$tagId);
		$this->assign('listType','tages');
		$this->assign('logList',$logData['list']);
		$this->assign('pageHtml',$pageHtml);
		return $this->display('/'.$template);
	}
}
