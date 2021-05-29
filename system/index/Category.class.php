<?php
namespace rp\index;

use rp\Cache;
use rp\index\LogsMod;

class Category extends base{
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
		$category=Cache::read('category');
		if(is_numeric($data[0])){
			$cateId=intval($data[0]);
		}else{
			$category2=array_column($category,NULL,'alias');
			$cateId=isset($category2[$data[0]]) ? $category2[$data[0]]['id'] : '';
		}
		if(empty($cateId) || !isset($category[$cateId])){
			rpMsg('当前栏目不存在！');
		}
		$children=isset($category[$cateId]) ? $category[$cateId]['children'] : array();
		$children[]=intval($cateId);
		$LogsMod=new LogsMod();
		$logData=$LogsMod->page($page)->order($this->getLogOrder(array('a.isTop'=>'desc')))->cate($children)->select();
		$title=$category[$cateId]['cate_name'];
		$pageHtml=pageInationHome($logData['count'],$logData['limit'],$logData['page'],'cate',$cateId);
		$template=!empty($category[$cateId]['temp_list']) ? $category[$cateId]['temp_list'] : 'list';
		$this->setKeywords($category[$cateId]['seo_key']);
		$this->setDescription($category[$cateId]['seo_desc']);
		$this->assign('title',$title.'-'.$this->webConfig['webName']);
		$this->assign('listId',$cateId);
		$this->assign('listType','cate');
		$this->assign('cateName',$title);
		$this->assign('logList',$logData['list']);
		$this->assign('pageHtml',$pageHtml);
		return $this->display('/'.$template);
	}
}
