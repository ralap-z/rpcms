<?php
namespace rp\api;

use rp\Db;
use rp\Cache;
use rp\Url;
use rp\Hook;

class Tag extends Base{
	
	public function __construct(){
		parent::__construct();
	}
	
	public function getList(){
		$data=Cache::read('tages');
		foreach($data as $k=>&$v){
			$v['url']=Url::tag($v['id']);
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
	
	/*单个标签内容*/
	public function getData(){
		$id=(int)input('id');
		$name=(string)input('name');
		$alias=(string)input('alias');
		$tages=Cache::read('tages');
		$key='';
		if(!empty($id)){
			$key=$id;
		}elseif(!empty($name)){
			$tages=array_column($tages, NULL, 'tagName');
			$key=$name;
		}elseif(!empty($alias)){
			$tages=array_column($tages, NULL, 'alias');
			$key=$alias;
		}
		$tagData='';
		if(isset($tages[$key])){
			$tagData=$tages[$key];
			$tagData['url']=Url::tag($tagData['id']);
		}
		$this->response($tagData);
	}
	
	public function post(){
		$this->chechAuth(true);
		if(self::$user['role'] != 'admin'){
			$this->response('',401,'无权限操作！');
		}
		$data=array();
		$data['tagName']=!empty(input('post.tagName')) ? strip_tags(input('post.tagName')) : '';
		$data['alias']=!empty(input('post.alias')) ? strip_tags(input('post.alias')) : '';
		$data['seo_desc']=!empty(input('post.seo_desc')) ? strip_tags(input('post.seo_desc')) : '';
		$data['template']=!empty(input('post.template')) ? strip_tags(input('post.template')) : '';
		$tageId=intval(input('post.id')) ? intval(input('post.id')) : 0;
		if(empty($data['tagName'])){
			$this->response('',401,'名称不能为空！');
		}
		$tages=Cache::read('tages');
		if(!empty($tageId) && !isset($tages[$tageId])){
			$this->response('',401,'数据不存在！');
		}
		$tagesTagName=array_column($tages,'tagName','id');
		$key1=array_search($data['tagName'],$tagesTagName);
		if(!empty($key1) && (empty($tageId) || $tageId != $key1)){
			$this->response('',401,'该标签已存在！');
		}
		$this->checkAlias($data['alias']);
		$tagesAlias=array_column($tages,'alias','id');
		$key2=array_search($data['alias'],$tagesAlias);
		if(!empty($data['alias']) && $key2 && (empty($tageId) || $key2 != $tageId)){
			$this->response('',401,'别名重复！');
		}
		if(!empty($data['alias']) && array_search($data['alias'],$tagesTagName)){
			$this->response('',401,'标签中存在该别名，请更换');
		}
		$this->checkTemplate($data['template']);
		if(!empty($tageId)){
			$res=Db::name('tages')->where(array('id'=>$tageId))->update($data);
		}else{
			$tageId=Db::name('tages')->insert($data);
		}
		Cache::update('tages');
		Hook::doHook('api_tage_save',array($tageId));
		$this->response($tageId,200,'操作成功！');
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
		$res=Db::name('tages')->where(array('id'=>$id))->dele();
		if($res){
			$options = \rp\Config::get('db');
			$logTagesSQL='UPDATE '.$options['prefix'].'logs set tages=TRIM(BOTH "," FROM replace(concat(",",tages,","), ",'.$id.'", "")) where find_in_set('.$id.',tages)';
			Db::instance()->query($logTagesSQL);
		}
		Cache::update('tages');
		Hook::doHook('api_tage_dele',array($id));
		$this->response($id,200,'操作成功！');
	}
}