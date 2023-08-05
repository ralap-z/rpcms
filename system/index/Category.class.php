<?php
namespace rp\index;

use rp\Cache;
use rp\index\LogsMod;

class Category extends base{
	
	private $params;
	private static $category;
	
	public function __construct($params){
		parent::__construct();
		$this->params=$params;
		self::$category=Cache::read('category');
	}
	
	public function index(){
		if(!isset($this->params['id']) || empty($this->params['id'])){
			redirect($this->App->baseUrl);
		}
		$page=isset($this->params['page']) ? intval($this->params['page']) : 1;
		if($this->isNumberId($this->params['id'])){
			$cateId=intval($this->params['id']);
		}else{
			$category2=array_column(self::$category,NULL,'alias');
			$cateId=isset($category2[$this->params['id']]) ? $category2[$this->params['id']]['id'] : '';
		}
		if(empty($cateId) || !isset(self::$category[$cateId])){
			rpMsg('当前栏目不存在！');
		}
		$cateData=self::$category[$cateId];
		$children=$cateData['children'];
		$children[]=intval($cateId);
		$LogsMod=new LogsMod();
		$logData=$LogsMod->page($page)->order($this->getLogOrder(array('a.isTop'=>'desc')))->cate($children)->select();
		foreach($children as $ck=>$cv){
			$logData['count']+=self::$category[$cv]['logNum'];
		}
		$title=!empty($cateData['seo_title']) ? $cateData['seo_title'] : $cateData['cate_name'];
		$pageHtml=pageInationHome($logData['count'],$logData['limit'],$logData['page'],'cate',$cateId);
		$template=$this->getTemp($cateData);
		$this->setKeywords($cateData['seo_key']);
		$this->setDescription($cateData['seo_desc']);
		$this->assign('title',$title.'-'.$this->webConfig['webName']);
		$this->assign('listId',$cateId);
		$this->assign('listType','cate');
		$this->assign('cateName',$cateData['cate_name']);
		$this->assign('logList',$logData['list']);
		$this->assign('pageHtml',$pageHtml);
		return $this->display('/'.$template);
	}
	
	private function getTemp($cateData){
		if(!empty($cateData['temp_list'])){
			return $cateData['temp_list'];
		}
		if(!empty($cateData['topId'])){
			return $this->getTemp(self::$category[$cateData['topId']]);
		}
		return 'list';
	}
}
