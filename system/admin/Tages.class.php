<?php
namespace rp\admin;
use rp\View;
use rp\Db;
use rp\Cache;

class Tages extends Base{
	
	public function __construct(){
		parent::__construct();
	}
	
	public function index(){
		$page=intval(input('page')) ? intval(input('page')) : 1;
		$limit=10;
		$tages=Cache::read('tages');
		$count=count($tages);
		$pages = ceil($count / $limit);
        if($page >= $pages && $pages > 0){
            $page = $pages;
        }
		$res=array_slice($tages, ($page-1)*$limit, $limit);
		$pageHtml=pageInation($count,$limit,$page);
		View::assign('list',$res);
		View::assign('pageHtml',$pageHtml);
		View::assign('tempFileHtml',$this->getTempFile());
		return View::display('/tages_index');
	}
	
	public function getinfo(){
		$id=intval(input('id')) ? intval(input('id')) : 0;
		if(empty($id)){
			return json(array('code'=>-1,'msg'=>'ID数据错误'));
		}
		$res=Db::name('tages')->where('id='.$id)->find();
		return json(array('code'=>200,'msg'=>'success','data'=>$res));
	}
	
	public function doAdd(){
		$data=array();
		$data['tagName']=!empty(input('tagName')) ? strip_tags(input('tagName')) : '';
		$data['alias']=!empty(input('alias')) ? strip_tags(input('alias')) : '';
		$data['seo_title']=!empty(input('seo_title')) ? mb_substr(strip_tags(input('seo_title')), 0, 30) : '';
		$data['seo_desc']=!empty(input('seo_desc')) ? strip_tags(input('seo_desc')) : '';
		$data['template']=!empty(input('template')) ? strip_tags(input('template')) : '';
		if(empty($data['tagName'])){
			return json(array('code'=>-1,'msg'=>'名称不能为空'));
		}
		$tages=Cache::read('tages');
		$tagesAlias2=array_column($tages,'tagName','id');
		if(array_search($data['tagName'],$tagesAlias2)){
			return json(array('code'=>-1, 'msg'=>'该标签已存在'));
		}
		$this->checkAlias($data['alias']);
		$tagesAlias=array_column($tages,'alias','id');
		if(!empty($data['alias']) && array_search($data['alias'],$tagesAlias)){
			return json(array('code'=>-1, 'msg'=>'别名重复'));
		}
		if(!empty($data['alias']) && array_search($data['alias'],$tagesAlias2)){
			return json(array('code'=>-1, 'msg'=>'标签中存在该别名，请更换'));
		}
		$this->checkTemplate($data['template']);
		$res=Db::name('tages')->insert($data);
		Cache::update('tages');
		return json(array('code'=>200,'msg'=>'添加成功'));
	}
	
	public function doUpdate(){
		$id=intval(input('id')) ? intval(input('id')) : 0;
		$data=array();
		$data['tagName']=!empty(input('tagName')) ? strip_tags(input('tagName')) : '';
		$data['alias']=!empty(input('alias')) ? strip_tags(input('alias')) : '';
		$data['seo_title']=!empty(input('seo_title')) ? mb_substr(strip_tags(input('seo_title')), 0, 30) : '';
		$data['seo_desc']=!empty(input('seo_desc')) ? strip_tags(input('seo_desc')) : '';
		$data['template']=!empty(input('template')) ? strip_tags(input('template')) : '';
		if(empty($id)){
			return json(array('code'=>-1,'msg'=>'数据错误，ID IS NULL'));
		}
		if(empty($data['tagName'])){
			return json(array('code'=>-1,'msg'=>'名称不能为空'));
		}
		$tages=Db::name('tages')->where('id='.$id)->find();
		if(empty($tages)){
			return json(array('code'=>-1,'msg'=>'该标签不存在'));
		}
		$tages=Cache::read('tages');
		$tagesAlias2=array_column($tages,'tagName','id');
		$key=array_search($data['tagName'],$tagesAlias2);
		if($key && $key != $id){
			return json(array('code'=>-1, 'msg'=>'该标签已存在'));
		}
		$this->checkAlias($data['alias']);
		$tagesAlias=array_column($tages,'alias','id');
		$key=array_search($data['alias'],$tagesAlias);
		if(!empty($data['alias']) && ($key && $key != $id)){
			return json(array('code'=>-1, 'msg'=>'别名重复'));
		}
		$key=array_search($data['alias'],$tagesAlias2);
		if(!empty($data['alias']) && ($key && $key != $id)){
			return json(array('code'=>-1, 'msg'=>'标签中存在该别名，请更换'));
		}
		$this->checkTemplate($data['template']);
		$res=Db::name('tages')->where('id='.$id)->update($data);
		Cache::update('tages');
		return json(array('code'=>200,'msg'=>'修改成功'));
	}
	
	public function dele(){
		$id=intval(input('id')) ? intval(input('id')) : 0;
		if(empty($id)){
			return json(array('code'=>-1,'msg'=>'ID数据错误'));
		}
		$res=Db::name('tages')->where('id='.$id)->dele();
		if($res){
			$options = \rp\Config::get('db');
			$logTagesSQL='UPDATE '.$options['prefix'].'logs set tages=TRIM(BOTH "," FROM replace(concat(",",tages,","), ",'.$id.'", "")) where find_in_set('.$id.',tages)';
			Db::instance()->query($logTagesSQL);
		}
		Cache::update('tages');
		return json(array('code'=>200,'msg'=>'删除成功'));
	}
	
	public function getall(){
		$tages=Cache::read('tages');
		$html='';
		foreach($tages as $k=>$v){
			$html.='<a href="javascript:;" data-text="'.$v['tagName'].'" data-id="'.$v['id'].'">'.$v['tagName'].'</a>';
		}
		return json(array('code'=>200, 'msg'=>'success', 'data'=>$html));
	}
	
}
