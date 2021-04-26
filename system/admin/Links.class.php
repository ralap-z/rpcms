<?php
namespace rp\admin;
use rp\View;
use rp\Db;
use rp\Cache;

class Links extends Base{
	
	public function __construct(){
		parent::__construct();
	}
	
	public function index(){
		$page=intval(input('page')) ? intval(input('page')) : 1;
		$limit=10;
		$count=Db::name('links')->count();
		$pages = ceil($count / $limit);
        if($page >= $pages && $pages > 0){
            $page = $pages;
        }
		$res=Db::name('links')->limit(($page-1)*$limit.','.$limit)->order('sort','asc')->select();
		$pageHtml=pageInation($count,$limit,$page);
		View::assign('list',$res);
		View::assign('pageHtml',$pageHtml);
		return View::display('/links_index');
	}
	
	
	public function dopost(){
		$param=input('post.');
		$data=array();
		$data['sitename']=strip_tags($param['sitename']);
		$data['siteurl']=strip_tags($param['siteurl']);
		if(empty($data['sitename'])){
			return json(array('code'=>-1, 'msg'=>'名称不能为空'));
		}
		if(empty($data['siteurl'])){
			return json(array('code'=>-1, 'msg'=>'URL地址不能为空'));
		}
		if(!checkForm('url',$data['siteurl'])){
			return json(array('code'=>-1,'msg'=>'URL地址格式错误(http(s)://等前缀)'));
		}
		$linkId=isset($param['id']) ? intval($param['id']) : '';
		$data['sitedesc']=strip_tags($param['sitedesc']);
		$data['sort']=intval($param['sort']);
		$data['status']=intval($param['status']) == -1 ? -1 : 0;
		if(!empty($linkId)){
			$res=Db::name('links')->where('id='.$linkId)->update($data);
		}else{
			$res=Db::name('links')->insert($data);
		}
		Cache::update('links');
		return json(array('code'=>200,'msg'=>(empty($linkId) ? '添加成功' : '修改成功')));
	}
	
	public function upSort(){
		$data=input('data');
		if(!empty($data)){
			foreach($data as $v){
				$id=intval($v['id']);
				$value=intval($v['value']);
				if(!empty($id)){
					Db::name('links')->where('id='.$id)->update(array('sort'=>$value));
				}
			}
			Cache::update('links');
			return json(array('code'=>200,'msg'=>'修改排序成功'));
		}
		return json(array('code'=>200,'msg'=>'数据不能为空'));
	}
	
	public function upStatus(){
		$id=intval(input('id')) ? intval(input('id')) : 0;
		$status=intval(input('status'));
		if(empty($id)){
			return json(array('code'=>-1,'msg'=>'ID数据错误'));
		}
		$res=Db::name('links')->where('id ='.$id)->update(array('status'=>$status));
		Cache::update('links');
		return json(array('code'=>200,'msg'=>'设置状态成功'));
	}
	
	public function getinfo(){
		$id=intval(input('id')) ? intval(input('id')) : 0;
		if(empty($id)){
			return json(array('code'=>-1,'msg'=>'ID数据错误'));
		}
		$res=Db::name('links')->where('id='.$id)->find();
		return json(array('code'=>200,'msg'=>'success','data'=>$res));
	}
	
	public function dele(){
		$id=intval(input('id')) ? intval(input('id')) : 0;
		if(empty($id)){
			return json(array('code'=>-1,'msg'=>'ID数据错误'));
		}
		$res=Db::name('links')->where('id='.$id)->dele();
		Cache::update('links');
		return json(array('code'=>200,'msg'=>'删除成功'));
	}
	
}
