<?php
namespace rp\admin;
use rp\View;
use rp\Db;
use rp\Config;

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
		if(empty($user)){
			return json(array('code'=>-1,'msg'=>'用户名或密码错误'));
		}
		$time=input('server.REQUEST_TIME');
		$errorMax=Config::get('webConfig.adminLoginErrMax') ?? 5;
		$errorTime=Config::get('webConfig.adminLoginErrTime') ?? 30;
		if($user['failureNum'] >= $errorMax && $time - strtotime($user['updateTime']) < $errorTime*60){
			return json(array('code'=>-1,'msg'=>'账户锁定，请'.$errorTime.'分钟后登录'));
        }
		if($user['password'] != psw($password)){
			if(!empty($errorMax)){
				Db::name('user')->where(array('id'=>$user['id']))->update([
					'failureNum'=>$user['failureNum']+1,
					'updateTime'=>date('Y-m-d H:i:s', $time),
				]);
			}
			return json(array('code'=>-1,'msg'=>'用户名或密码错误'));
		}
		if($user['status'] != 0){
			return json(array('code'=>-1,'msg'=>'该用户已被禁用'));
		}
		$sessionToken=md5($user['username'].$time);
		Db::name('user')->where(array('id'=>$user['id']))->update([
			'failureNum'=>0,
			'sessionToken'=>$sessionToken,
		]);
		$data=array(
			'uid'=>$user['id'],
			'username'=>$user['username'],
			'sessionToken'=>$sessionToken
		);
		session('MEADMIN',$data);
		return json(array('code'=>200,'msg'=>'success'));
	}
	
	public function out(){
		session('MEADMIN',null);
		redirect(url('login/index'));
	}
}
