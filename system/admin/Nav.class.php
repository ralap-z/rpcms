<?php
namespace rp\admin;
use rp\View;
use rp\Db;
use rp\Cache;

class Nav extends Base{
	
	private $types=array(
		1=>'系统',
		2=>'分类',
		3=>'单页',
		4=>'自定',
	);
	
	public function __construct(){
		parent::__construct();
	}
	
	public function index(){
		$diyTop=Db::name('nav')->where('status = 0 and topId = 0')->field('id,navname')->select();
		View::assign('types',$this->types);
		View::assign('list',Cache::read('nav'));
		View::assign('pages',Cache::read('pages'));
		View::assign('cateCheckbox',me_createCateCheckbox());
		View::assign('diyTop',$diyTop);
		return View::display('/nav_index');
	}
	
	public function update(){
		$id=intval(input('id')) ? intval(input('id')) : 0;
		$info=Db::name('nav')->where('id='.$id)->find();
		if(empty($info)){
			redirect(url('nav/index'));
		}
		$diyTop=Db::name('nav')->where('status = 0 and topId = 0')->field('id,navname')->order('id', 'asc')->select();
		View::assign('id',$id);
		View::assign('info',$info);
		View::assign('diyTop',$diyTop);
		return View::display('/nav_update');
	}
	
	public function addDiy(){
		$data=array();
		$data['sort']=intval(input('sort')) ? intval(input('sort')) : 0;
		$data['navname']=!empty(input('navname')) ? strip_tags(input('navname')) : '';
		$data['url']=!empty(input('url')) ? input('url') : '';
		$data['topId']=intval(input('topId')) ? intval(input('topId')) : 0;
		$data['newtab']=intval(input('newtab')) ? intval(input('newtab')) : 0;
		if(empty($data['navname'])){
			return json(array('code'=>-1,'msg'=>'名称不能为空'));
		}
		if(empty($data['url'])){
			return json(array('code'=>-1,'msg'=>'地址不能为空'));
		}
		if(!checkForm('url',$data['url'])){
			return json(array('code'=>-1,'msg'=>'地址格式错误(http(s)://等前缀)'));
		}
		$data['types']=4;
		return $this->addpost($data);
	}
	
	public function addCate(){
		$ids=input('ids');
		$topId=intval(input('topId')) ? intval(input('topId')) : 0;
		$ids = str_replace(array(';','，','、'), ',', $ids);
		$ids = RemoveSpaces(strip_tags($ids));
		$idsArr = explode(',', $ids);
		$idsArr = array_filter(array_unique($idsArr));
		if(empty($idsArr)){
			return json(array('code'=>-1,'msg'=>'所选分类为空'));
		}
		$data=array();
		$category=Cache::read('category');
		foreach($idsArr as $k=>$v){
			$data[]=array(
				'navname'=>$category[$v]['cate_name'],
				'topId'=>$topId,
				'types'=>2,
				'typeId'=>$v,
			);
		}
		return $this->addpost($data);
	}
	
	public function addPage(){
		$ids=input('ids');
		$topId=intval(input('topId')) ? intval(input('topId')) : 0;
		$ids = str_replace(array(';','，','、'), ',', $ids);
		$ids = RemoveSpaces(strip_tags($ids));
		$idsArr = explode(',', $ids);
		$idsArr = array_filter(array_unique($idsArr));
		if(empty($idsArr)){
			return json(array('code'=>-1,'msg'=>'所选单页为空'));
		}
		$data=array();
		$pages=Cache::read('pages');
		foreach($idsArr as $k=>$v){
			$data[]=array(
				'navname'=>$pages[$v]['title'],
				'topId'=>$topId,
				'types'=>3,
				'typeId'=>$v,
			);
		}
		return $this->addpost($data);
	}
	
	public function upStatus(){
		$id=intval(input('id')) ? intval(input('id')) : 0;
		$status=intval(input('status')) ? intval(input('status')) : 0;
		if(empty($id)){
			return json(array('code'=>-1,'msg'=>'ID数据错误'));
		}
		$res=Db::name('nav')->where('id='.$id)->update(array('status'=>$status));
		Cache::update('nav');
		return json(array('code'=>200,'msg'=>'修改状态成功'));
	}
	
	public function upSort(){
		$data=input('data');
		if(!empty($data)){
			foreach($data as $v){
				$id=intval($v['id']);
				$value=intval($v['value']);
				if(!empty($id)){
					Db::name('nav')->where('id='.$id)->update(array('sort'=>$value));
				}
			}
			Cache::update('nav');
			return json(array('code'=>200,'msg'=>'修改排序成功'));
		}
		return json(array('code'=>200,'msg'=>'数据不能为空'));
	}
	
	public function dele(){
		$id=intval(input('id')) ? intval(input('id')) : 0;
		if(empty($id)){
			return json(array('code'=>-1,'msg'=>'ID数据错误'));
		}
		$res=Db::name('nav')->where('id='.$id)->dele();
		Cache::update('nav');
		return json(array('code'=>200,'msg'=>'删除成功'));
	}
	
	public function doUpdate(){
		$id=intval(input('id')) ? intval(input('id')) : 0;
		$data=array();
		$data['navname']=!empty(input('navname')) ? strip_tags(input('navname')) : '';
		$data['newtab']=intval(input('newtab')) ? intval(input('newtab')) : 0;
		if(empty($id)){
			return json(array('code'=>-1,'msg'=>'数据错误，ID IS NULL'));
		}
		if(empty($data['navname'])){
			return json(array('code'=>-1,'msg'=>'名称不能为空'));
		}
		$nav=Db::name('nav')->where('id='.$id)->find();
		if(empty($nav)){
			return json(array('code'=>-1,'msg'=>'该导航不存在'));
		}
		if($nav['types'] == 4){
			$data['url']=!empty(input('url')) ? input('url') : '';
			if(empty($data['url'])){
				return json(array('code'=>-1,'msg'=>'地址不能为空'));
			}
			if(!checkForm('url',$data['url'])){
				return json(array('code'=>-1,'msg'=>'地址格式错误(http(s)://等前缀)'));
			}
		}
		$topid=intval(input('topId')) ? intval(input('topId')) : 0;
		if($id != $topid){
			$data['topId']=intval(input('topId')) ? intval(input('topId')) : 0;
		}
		$res=Db::name('nav')->where('id='.$id)->update($data);
		Cache::update('nav');
		return json(array('code'=>200,'msg'=>'修改成功'));
	}
	
	private function addpost($data=array()){
		if(!empty($data)){
			$res=Db::name('nav')->insert($data);
		}
		Cache::update('nav');
		return json(array('code'=>200,'msg'=>'添加成功'));
	}

}
