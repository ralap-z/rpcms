<?php
namespace rp\api;

use rp\Cache;
use rp\Url;
use rp\Db;
use rp\Hook;

class Special extends Base{
	
	public function __construct(){
		parent::__construct();
	}
	
	public function getList(){
		$data=Cache::read('special');
		foreach($data as $k=>&$v){
			$v['url']=Url::special($v['id']);
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
		$this->response($data);
	}
	
	public function getData(){
		$id=(int)input('id');
		$name=(string)input('name');
		$alias=(string)input('alias');
		$special=Cache::read('special');
		$key='';
		if(!empty($id)){
			$key=$id;
		}elseif(!empty($name)){
			$special=array_column($special, NULL, 'tagName');
			$key=$name;
		}elseif(!empty($alias)){
			$special=array_column($special, NULL, 'alias');
			$key=$alias;
		}
		$specialData='';
		if(isset($special[$key])){
			$specialData=$special[$key];
			$specialData['url']=Url::special($specialData['id']);
		}
		$this->response($specialData);
	}
	
	public function post(){
		$this->chechAuth(true);
		if(self::$user['role'] != 'admin'){
			$this->response('',401,'无权限操作！');
		}
		$headimg=isset($_FILES['headimg']) ? $_FILES['headimg'] : '';
		$data=array();
		$data['title']=!empty(input('post.title')) ? strip_tags(input('post.title')) : '';
		$data['subTitle']=!empty(input('post.subTitle')) ? strip_tags(input('post.subTitle')) : '';
		$data['alias']=!empty(input('post.alias')) ? strip_tags(input('post.alias')) : '';
		$data['seo_desc']=!empty(input('post.seo_desc')) ? strip_tags(input('post.seo_desc')) : '';
		$data['temp_list']=!empty(input('post.temp_list')) ? strip_tags(input('post.temp_list')) : '';
		$data['createTime']=date('Y-m-d H:i:s');
		$specialId=intval(input('post.id')) ? intval(input('post.id')) : 0;
		if(!empty($headimg)){
			$res=uploadFiles($headimg);
			if($res['code'] == 200){
				$data['headimg']=$res['data'];
			}
		}
		if(empty($data['title'])){
			$this->response('',401,'名称不能为空！');
		}
		$special=Cache::read('special');
		if(!empty($specialId) && !isset($special[$specialId])){
			$this->response('',401,'数据不存在！');
		}
		$specialTitle=array_column($special,'title','id');
		$key1=array_search($data['title'],$specialTitle);
		if(!empty($key1) && (empty($specialId) || $specialId != $key1)){
			$this->response('',401,'该专题已存在！');
		}
		$this->checkAlias($data['alias']);
		$specialAlias=array_column($special,'alias','id');
		$key2=array_search($data['alias'],$specialAlias);
		if(!empty($data['alias']) && $key2 && (empty($specialId) || $key2 != $specialId)){
			$this->response('',401,'别名重复！');
		}
		$this->checkTemplate($data['temp_list'],'列表');
		if(!empty($specialId)){
			unset($data['createTime']);
			$res=Db::name('special')->where('id='.$specialId)->update($data);
		}else{
			$specialId=Db::name('special')->insert($data);
		}
		Cache::update('special');
		Hook::doHook('api_special_save',array($specialId));
		$this->response($specialId,200,'操作成功！');
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
		$res=Db::name('special')->where('id='.$id)->dele();
		$res=Db::name('logs')->where('specialId='.$id)->update(array('specialId'=>0));
		Cache::update('special');
		Hook::doHook('api_special_dele',array($id));
		$this->response($id,200,'操作成功！');
	}
}