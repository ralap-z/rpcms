<?php
namespace rp\admin;
use rp\View;
use rp\Db;
use rp\Cache;

class Special extends Base{
	
	public function __construct(){
		parent::__construct();
	}
	
	public function index(){
		View::assign('special',Cache::read('special'));
		return View::display('/special_index');
	}
	
	public function getinfo(){
		$id=intval(input('id')) ? intval(input('id')) : 0;
		if(empty($id)){
			return json(array('code'=>-1,'msg'=>'ID数据错误'));
		}
		$res=Db::name('special')->where('id='.$id)->find();
		return json(array('code'=>200,'msg'=>'success','data'=>$res));
	}
	
	public function doAdd(){
		$headimg=isset($_FILES['headimg']) ? $_FILES['headimg'] : '';
		$data=array();
		$data['title']=!empty(input('title')) ? strip_tags(input('title')) : '';
		$data['subTitle']=!empty(input('subTitle')) ? strip_tags(input('subTitle')) : '';
		$data['alias']=!empty(input('alias')) ? strip_tags(input('alias')) : '';
		$data['seo_desc']=!empty(input('seo_desc')) ? strip_tags(input('seo_desc')) : '';
		$data['temp_list']=!empty(input('temp_list')) ? strip_tags(input('temp_list')) : '';
		$data['createTime']=date('Y-m-d H:i:s');
		if(!empty($headimg)){
			$res=uploadFiles($headimg);
			if($res['code'] == 200){
				$data['headimg']=$res['data'];
			}
		}
		if(empty($data['title'])){
			return json(array('code'=>-1,'msg'=>'名称不能为空'));
		}
		$this->checkAlias($data['alias']);
		$specialAlias=array_column(Cache::read('special'),'alias','id');
		if(!empty($data['alias']) && array_search($data['alias'],$specialAlias)){
			return json(array('code'=>-1, 'msg'=>'别名重复'));
		}
		$this->checkTemplate($data['temp_list'],'列表');
		$res=Db::name('special')->insert($data);
		Cache::update('special');
		return json(array('code'=>200,'msg'=>'添加成功'));
	}
	
	public function dele(){
		$id=intval(input('id')) ? intval(input('id')) : 0;
		if(empty($id)){
			return json(array('code'=>-1,'msg'=>'ID数据错误'));
		}
		$res=Db::name('special')->where('id='.$id)->dele();
		$res=Db::name('logs')->where('specialId='.$id)->update(array('specialId'=>0));
		Cache::update('special');
		return json(array('code'=>200,'msg'=>'删除成功'));
	}
	
	public function doUpdate(){
		$id=intval(input('id')) ? intval(input('id')) : 0;
		$headimg=isset($_FILES['headimg']) ? $_FILES['headimg'] : '';
		$data=array();
		$data['title']=!empty(input('title')) ? strip_tags(input('title')) : '';
		$data['subTitle']=!empty(input('subTitle')) ? strip_tags(input('subTitle')) : '';
		$data['alias']=!empty(input('alias')) ? strip_tags(input('alias')) : '';
		$data['seo_desc']=!empty(input('seo_desc')) ? strip_tags(input('seo_desc')) : '';
		$data['temp_list']=!empty(input('temp_list')) ? strip_tags(input('temp_list')) : '';
		if(!empty($headimg)){
			$res=uploadFiles($headimg);
			if($res['code'] == 200){
				$data['headimg']=$res['data'];
			}
		}
		if(empty($id)){
			return json(array('code'=>-1,'msg'=>'数据错误，ID IS NULL'));
		}
		$special=Db::name('special')->where('id='.$id)->find();
		if(empty($special)){
			return json(array('code'=>-1,'msg'=>'该专题不存在'));
		}
		if(empty($data['title'])){
			return json(array('code'=>-1,'msg'=>'名称不能为空'));
		}
		$this->checkAlias($data['alias']);
		$specialAlias=array_column(Cache::read('special'),'alias','id');
		$key=array_search($data['alias'],$specialAlias);
		if(!empty($data['alias']) && ($key && $key != $id)){
			return json(array('code'=>-1, 'msg'=>'别名重复'));
		}
		$this->checkTemplate($data['temp_list'],'列表');
		$res=Db::name('special')->where('id='.$id)->update($data);
		Cache::update('special');
		return json(array('code'=>200,'msg'=>'修改成功'));
	}
}
