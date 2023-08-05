<?php
namespace rp\admin;
use rp\View;
use rp\Db;
use rp\Cache;

class Category extends Base{
	
	public function __construct(){
		parent::__construct();
	}
	
	public function index(){
		View::assign('category',Cache::read('category'));
		View::assign('tempFileHtml',$this->getTempFile());
		return View::display('/category_index');
	}
	
	public function getinfo(){
		$id=intval(input('id')) ? intval(input('id')) : 0;
		if(empty($id)){
			return json(array('code'=>-1,'msg'=>'ID数据错误'));
		}
		$res=Db::name('category')->where('id='.$id)->find();
		return json(array('code'=>200,'msg'=>'success','data'=>$res));
	}
	
	public function doAdd(){
		$data=array();
		$data['sort']=intval(input('sort')) ? intval(input('sort')) : 0;
		$data['cate_name']=!empty(input('cate_name')) ? strip_tags(input('cate_name')) : '';
		$data['alias']=!empty(input('alias')) ? strip_tags(input('alias')) : '';
		$data['topId']=intval(input('topId')) ? intval(input('topId')) : 0;
		$data['seo_title']=!empty(input('seo_title')) ? mb_substr(strip_tags(input('seo_title')), 0, 30) : '';
		$data['seo_key']=!empty(input('seo_key')) ? strip_tags(input('seo_key')) : '';
		$data['seo_desc']=!empty(input('seo_desc')) ? strip_tags(input('seo_desc')) : '';
		$data['temp_list']=!empty(input('temp_list')) ? strip_tags(input('temp_list')) : '';
		$data['temp_logs']=!empty(input('temp_logs')) ? strip_tags(input('temp_logs')) : '';
		$data['is_submit']=input('is_submit') == 1 ? 1 : 0;
		if(empty($data['cate_name'])){
			return json(array('code'=>-1,'msg'=>'名称不能为空'));
		}
		$this->checkAlias($data['alias']);
		$categoryAlias=array_column(Cache::read('category'),'alias','id');
		if(!empty($data['alias']) && array_search($data['alias'],$categoryAlias)){
			return json(array('code'=>-1, 'msg'=>'别名重复'));
		}
		$this->checkTemplate($data['temp_list'],'列表');
		$this->checkTemplate($data['temp_logs'],'内容');
		$res=Db::name('category')->insert($data);
		Cache::update('category');
		return json(array('code'=>200,'msg'=>'添加成功'));
	}
	
	public function upSort(){
		$data=input('data');
		if(!empty($data)){
			foreach($data as $v){
				$id=intval($v['id']);
				$value=intval($v['value']);
				if(!empty($id)){
					Db::name('category')->where('id='.$id)->update(array('sort'=>$value));
				}
			}
			Cache::update('category');
			return json(array('code'=>200,'msg'=>'修改排序成功'));
		}
		return json(array('code'=>200,'msg'=>'数据不能为空'));
	}
	
	public function dele(){
		$id=intval(input('id')) ? intval(input('id')) : 0;
		if(empty($id)){
			return json(array('code'=>-1,'msg'=>'ID数据错误'));
		}
		$res=Db::name('category')->where('id='.$id)->dele();//删除分类
		$res=Db::name('nav')->where('types = 2 and typeId ='.$id)->dele();//删除导航中的该分类
		$res=Db::name('logs')->where('cateId='.$id)->update(array('cateId'=>0));//将该分类下的文章分类设置为0
		Cache::update('category');
		Cache::update('nav');
		return json(array('code'=>200,'msg'=>'删除成功'));
	}
	
	public function doUpdate(){
		$id=intval(input('id')) ? intval(input('id')) : 0;
		$data=array();
		$data['sort']=intval(input('sort')) ? intval(input('sort')) : 0;
		$data['cate_name']=!empty(input('cate_name')) ? strip_tags(input('cate_name')) : '';
		$data['alias']=!empty(input('alias')) ? strip_tags(input('alias')) : '';
		$data['seo_title']=!empty(input('seo_title')) ? mb_substr(strip_tags(input('seo_title')), 0, 30) : '';
		$data['seo_key']=!empty(input('seo_key')) ? strip_tags(input('seo_key')) : '';
		$data['seo_desc']=!empty(input('seo_desc')) ? strip_tags(input('seo_desc')) : '';
		$data['temp_list']=!empty(input('temp_list')) ? strip_tags(input('temp_list')) : '';
		$data['temp_logs']=!empty(input('temp_logs')) ? strip_tags(input('temp_logs')) : '';
		$data['is_submit']=input('is_submit') == 1 ? 1 : 0;
		if(empty($id)){
			return json(array('code'=>-1,'msg'=>'数据错误，ID IS NULL'));
		}
		if(empty($data['cate_name'])){
			return json(array('code'=>-1,'msg'=>'名称不能为空'));
		}
		$category=Db::name('category')->where('id='.$id)->find();
		if(empty($category)){
			return json(array('code'=>-1,'msg'=>'该导航不存在'));
		}
		$this->checkAlias($data['alias']);
		$categoryAlias=array_column(Cache::read('category'),'alias','id');
		$key=array_search($data['alias'],$categoryAlias);
		if(!empty($data['alias']) && ($key && $key != $id)){
			return json(array('code'=>-1, 'msg'=>'别名重复'));
		}
		$this->checkTemplate($data['temp_list'],'列表');
		$this->checkTemplate($data['temp_logs'],'内容');
		$topid=intval(input('topId')) ? intval(input('topId')) : 0;
		if($id != $topid){
			$data['topId']=intval(input('topId')) ? intval(input('topId')) : 0;
		}
		$res=Db::name('category')->where('id='.$id)->update($data);
		if(Db::name('nav')->where(array('types'=>2,'typeId'=>$id))->find()){
			$res=Db::name('nav')->where(array('types'=>2,'typeId'=>$id))->update(array('navname'=>$data['cate_name']));
			Cache::update('nav');
		}
		Cache::update('category');
		return json(array('code'=>200,'msg'=>'修改成功'));
	}
}
