<?php
namespace rp\admin;

use rp\View;
use rp\Db;
use rp\Cache;
use rp\Hook;

class Pages extends Base{
	
	public function __construct(){
		parent::__construct();
	}
	
	public function index(){
		
		$page=intval(input('page')) ? intval(input('page')) : 1;
		$limit=10;
		
		$count=Db::name('pages')->alias('a')->field('a.id')->count();
		$pages = ceil($count / $limit);
        if($page >= $pages && $pages > 0){
            $page = $pages;
        }
		$res=Db::name('pages')->alias('a')->join('user b','a.authorId=b.id','left')->field('a.id,a.title,a.comnum,a.authorId,a.template,a.createTime,a.status,b.nickname')->limit(($page-1)*$limit.','.$limit)->select();
		$pageHtml=pageInation($count,$limit,$page);
		View::assign('list',$res);
		View::assign('pageHtml',$pageHtml);
		return View::display('/pages_index');
	}
	
	public function add(){
		$pageData=array(
			'isRemark'=>1,
			'extend'=>false,
		);
		View::assign('pageData',$pageData);
		View::assign('authorHtml',me_createAuthorOption());
		return View::display('/pages_add');
	}
	
	public function update(){
		$id=intval(input('id')) ? intval(input('id')) : 0;
		if(empty($id)){
			redirect(url('pages/add'));
		}
		$pageData=Db::name('pages')->where('id='.$id)->find();
		$pageData['extend']=json_decode($pageData['extend'],true);
		View::assign('pageId',$id);
		View::assign('pageData',$pageData);
		View::assign('authorHtml',me_createAuthorOption($pageData['authorId']));
		return View::display('/pages_add');
	}
	
	public function dopost(){
		$param=input('post.');
		$data=array();
		$data['title']=strip_tags($param['title']);
		$data['content']=clear_html($param['content'],array('script'));
		if(empty($data['title'])){
			return json(array('code'=>-1, 'msg'=>'标题不能为空'));
		}
		if(empty($data['content'])){
			return json(array('code'=>-1, 'msg'=>'正文不能为空'));
		}
		$pageId=intval($param['pageId']);
		$data['seo_key']=strip_tags($param['seo_key']);
		$data['seo_desc']=strip_tags($param['seo_desc']);
		$data['authorId']=intval($param['authorId']);
		$data['alias']=strip_tags($param['alias']);
		$data['password']=strip_tags($param['password']);
		$data['template']=strip_tags($param['template']);
		$data['createTime']=!empty($param['createTime']) ? date('Y-m-d H:i:s',strtotime($param['createTime'])) : date('Y-m-d H:i:s');
		$data['isRemark']=!empty($param['isRemark']) ? intval($param['isRemark']) : 0;
		$data['extend']=$this->extendPost($param);
		$data['status']=0;
		$this->checkAlias($data['alias']);
		$this->checkTemplate($data['template']);
		$pagesAlias=array_column(Cache::read('pages'),'alias','id');
		if(!empty($pageId)){
			$key=array_search($data['alias'],$pagesAlias);
			if(!empty($data['alias']) && ($key && $key != $pageId)){
				return json(array('code'=>-1, 'msg'=>'别名重复'));
			}
			if(Db::name('nav')->where(array('types'=>3,'typeId'=>$pageId))->find()){
				$res=Db::name('nav')->where(array('types'=>3,'typeId'=>$pageId))->update(array('navname'=>$data['title']));
				Cache::update('nav');
			}
			$res=Db::name('pages')->where('id='.$pageId)->update($data);
		}else{
			if(!empty($data['alias']) && array_search($data['alias'],$pagesAlias)){
				return json(array('code'=>-1, 'msg'=>'别名重复'));
			}
			$pageId=Db::name('pages')->insert($data);
		}
		Cache::update('pages');
		Cache::update('total');
		Hook::doHook('admin_pages_save',array($pageId));
		return json(array('code'=>200,'msg'=>(empty($pageId) ? '添加成功' : '修改成功'),'data'=>$pageId));
	}
	
	public function dele(){
		$ids=input('ids') ? input('ids') : '';
		$idsArr=explode(',',$ids);
		foreach($idsArr as $k=>$v){
			if(!intval($v)) unset($idsArr[$k]);
		}
		if(empty($idsArr)){
			return json(array('code'=>-1,'msg'=>'提交单页为空'));
		}
		$idsArr=join(',',$idsArr);
		$res=Db::name('pages')->where(array('id'=>array('in',$idsArr)))->dele();
		Cache::update('pages');
		Cache::update('total');
		Hook::doHook('admin_pages_dele',array($idsArr));
		return json(array('code'=>200,'msg'=>'删除成功'));
	}
	
	public function upload(){
		$file=isset($_FILES['files']) ? $_FILES['files'] : '';
		$pageId=intval(input('pageId')) ? intval(input('pageId')) : '';
		if(empty($pageId)){
			return json(array('code'=>-1,'msg'=>'数据错误，ID为空'));
		}
		$res=uploadFiles($file,0,$pageId);
		if($res['code'] == 200){
			return json(array('code'=>200, 'msg'=>'success'));
		}else{
			return json(array('code'=>-1, 'msg'=>$res['msg']));
		}
	}
	
}
