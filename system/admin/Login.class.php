<?php
namespace rp\admin;
use rp\View;
use rp\Db;

class Login{
	
	public function __construct(){
		
	}
	
	public function index(){
		
		return View::display('/login');
	}
	
	public function dologin(){
		$username=input('username');
		$password=input('password');
		if(empty($username) || empty($password)){
			return json(array('code'=>-1,'msg'=>'请输入全部数据'));
		}
		$user=Db::name('user')->where(array('username'=>$username,'role'=>'admin'))->find();
		if(empty($user) || $user['password'] != psw($password)){
			return json(array('code'=>-1,'msg'=>'用户名或密码错误'));
		}
		if($user['status'] != 0){
			return json(array('code'=>-1,'msg'=>'该用户已被禁用'));
		}
		$data=array(
			'uid'=>$user['id'],
			'username'=>$user['username'],
		);
		session('MEADMIN',$data);
		return json(array('code'=>200,'msg'=>'success'));
	}
	
	public function out(){
		session('MEADMIN',null);
		redirect(url('login/index'));
	}
}
