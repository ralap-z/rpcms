<?php
namespace rp\api;

use rp\Cache;
use rp\Url;
use rp\Db;
use rp\Hook;

class Cate extends Base{
	
	public function __construct(){
		parent::__construct();
	}
	
	public function getList(){
		$data=Cache::read('category');
		foreach($data as $k=>&$v){
			$v['url']=Url::cate($v['id']);
		}
		$order=$this->getOrder(array('logNum'=>''));
		$key=$value='';
		if(!empty($order)){
			$key=array_keys($order);
			$value=array_values($order);
			$value=$value[0] == 'desc' ? 'SORT_DESC' : 'SORT_ASC';
		}
		if(!empty($key) && !empty($value)){
			$data=arraySequence($data,$key[0],$value);
		}
		$this->response(array_values($data));
	}
	
	public function getData(){
		$id=(int)input('id');
		$name=(string)input('name');
		$alias=(string)input('alias');
		$category=Cache::read('category');
		$key='';
		if(!empty($id)){
			$key=$id;
		}elseif(!empty($name)){
			$category=array_column($category, NULL, 'cate_name');
			$key=$name;
		}elseif(!empty($alias)){
			$category=array_column($category, NULL, 'alias');
			$key=$alias;
		}
		$cateData='';
		if(isset($category[$key])){
			$cateData=$category[$key];
			$cateData['url']=Url::cate($cateData['id']);
		}
		$this->response($cateData);
	}
	
	public function post(){
		$this->chechAuth(true);
		if(self::$user['role'] != 'admin'){
			$this->response('',401,'无权限操作！');
		}
		$param=input('post.');
		$default=array(
			'id'=>0,
			'sort'=>0,
			'cate_name'=>'',
			'alias'=>'',
			'topId'=>0,
			'seo_key'=>'',
			'seo_desc'=>'',
			'temp_list'=>'',
			'temp_logs'=>'',
			'is_submit'=>0,
		);
		$param=array_merge($default,$param);
		$cateId=intval($param['id']) ? intval($param['id']) : 0;
		$data=array();
		$data['sort']=intval($param['sort']);
		$data['cate_name']=strip_tags($param['cate_name']);
		$data['alias']=strip_tags($param['alias']);
		$data['topId']=intval($param['topId']);
		$data['seo_key']=strip_tags($param['seo_key']);
		$data['seo_desc']=strip_tags($param['seo_desc']);
		$data['temp_list']=strip_tags($param['temp_list']);
		$data['temp_logs']=strip_tags($param['temp_logs']);
		$data['is_submit']=intval($param['is_submit']);
		if(empty($data['cate_name'])){
			$this->response('',401,'名称不能为空！');
		}
		$category=Cache::read('category');
		if(!empty($cateId) && !isset($category[$cateId])){
			$this->response('',401,'数据不存在！');
		}
		$this->checkAlias($data['alias']);
		$categoryAlias=array_column($category,'alias','id');
		$key=array_search($data['alias'],$categoryAlias);
		if(!empty($data['alias']) && $key && (empty($cateId) || ($key != $cateId))){
			$this->response('',401,'别名重复！');
		}
		$this->checkTemplate($data['temp_list'],'列表');
		$this->checkTemplate($data['temp_logs'],'内容');
		if(!empty($cateId) && $cateId == $data['topId']){
			$data['topId']=0;
		}
		if(!empty($cateId)){
			$res=Db::name('category')->where(array('id'=>$cateId))->update($data);
			if(Db::name('nav')->where(array('types'=>2,'typeId'=>$cateId))->find()){
				$res=Db::name('nav')->where(array('types'=>2,'typeId'=>$cateId))->update(array('navname'=>$data['cate_name']));
				Cache::update('nav');
			}
		}else{
			$cateId=Db::name('category')->insert($data);
		}
		Cache::update('category');
		Hook::doHook('api_cate_save',array($cateId));
		$this->response($cateId,200,'操作成功！');
	}
	
	public function dele(){
		$this->chechAuth(true);
		if(self::$user['role'] != 'admin'){
			$this->response('',401,'无权限操作！');
		}
		$id=intval(input('id')) ? intval(input('id')) : 0;
		if(empty($id)){
			$this->response('',401,'无效参数！');
		}
		$res=Db::name('category')->where(array('id'=>$id))->dele();//删除分类
		$res=Db::name('nav')->where(array('types'=>2,'typeId'=>$id))->dele();//删除导航中的该分类
		$res=Db::name('logs')->where(array('cateId'=>$id))->update(array('cateId'=>0));//将该分类下的文章分类设置为0
		Cache::update('category');
		Cache::update('nav');
		Hook::doHook('api_cate_dele',array($id));
		$this->response($id,200,'操作成功！');
	}
}