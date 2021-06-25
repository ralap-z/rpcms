<?php
namespace rp\admin;
use rp\View;
use rp\Db;
use rp\Hook;
use rp\Cache;

class Base{
	protected $user;
	protected $me_alias=array('index','post','search','author','category','html','tag','date','plugin','page','special');
	protected $me_tmpName=array('common','setting','password','index','list','page','search','detail','default','hook','special');
	protected $App;
	protected $isAjax=false;
	
	public function __construct(){
		global $App;
		$this->isAjax=$App->isAjax();
		if(!isLogin()){
			if($this->isAjax){
				return json(array('code'=>-1, 'msg'=>'请先登录'));
			}else{
				redirect(url('login/index'));
			}
		}
		$this->App=$App;
		$session=session('MEADMIN');
		$this->user=Db::name('user')->where('id='.$session['uid'])->find();
		$leftMenu=Hook::getHook('admin_left_menu');
		$this->getCommentExamNum();
		View::assign('user',$this->user);
		View::assign('hasLeftMenu',!empty($leftMenu));
	}
	
	private function getCommentExamNum(){
		$num=Db::name('comment')->where('status=1')->count();
		View::assign('commentExamNum',$num);
	}
	
	protected function checkAlias($alias=''){
		if(!empty($alias)){
			if(!preg_match('/^(?!\d+$)[A-Za-z0-9\-]+$/u',$alias)){
				return json(array('code'=>-1, 'msg'=>'别名错误，应由字母、数字、短横线组成'));
			}
			if(in_array($alias,$this->me_alias)){
				return json(array('code'=>-1, 'msg'=>'别名重复，请更换别名'));
			}
		}
	}
	
	protected function checkTemplate($template='', $msg=''){
		if(!empty($template)){
			if(!preg_match('/^[A-Za-z0-9_\-]+$/u',$template)){
				return json(array('code'=>-1, 'msg'=>$msg.'模板名称错误，应由字母、数字、下划线、短横线组成'));
			}
			if(in_array($template,$this->me_tmpName)){
				return json(array('code'=>-1, 'msg'=>'该名称是系统保留名称，请更换'.$msg.'模板名称'));
			}
		}
	}
	
	protected function extendPost($post=array()){
		$extend=array();
		foreach($post as $key => $value){
			if(substr($key, 0, 7) == 'extend_'){
				$name = substr($key, 7);
				$extend[$name] = $value;
			}
		}
		return !empty($extend) ? addslashes(json_encode($extend)) : '';
	}
	
	protected function getKey(){
		$option=Cache::read('option');
		return isset($option['key']) ? $option['key'] : '';
	}
}
