<?php
namespace rp\api;

use rp\Db;
use rp\Hook;
use rp\Cache;
use rp\Url;

class Member extends Base{
	
	public function __construct(){
		parent::__construct();
	}
	
	public function register(){
		$data=array();
		$data['username']=!empty(input('post.username')) ? strip_tags(input('post.username')) : '';
		$data['password']=!empty(input('post.password')) ? strip_tags(input('post.password')) : '';
		$data['nickname']=!empty(input('post.nickname')) ? strip_tags(input('post.nickname')) : $data['username'];
		$data['role']='member';
		$data['email']=input('post.email');
		$data['phone']=input('post.phone');
		$data['status']=0;
		$data['isCheck']=1;
		if(empty($data['username'])){
			$this->response('',401,'用户名不能为空');
		}
		if(empty($data['password'])){
			$this->response('',401,'密码不能为空');
		}
		if(strlen($data['password']) < 6){
			$this->response('',401,'密码至少6位');
		}
		if(!empty($data['email']) && !checkForm('email',$data['email'])){
			$this->response('',401,'邮箱格式不正确');
		}
		if(!empty($data['phone']) && !checkForm('phone',$data['phone'])){
			$this->response('',401,'手机号码格式不正确');
		}
		if(!empty($data['email']) && Db::name('user')->where("email='".$data['email']."'")->find()){
			$this->response('',401,'该邮箱已注册');
		}
		if(!empty($data['phone']) && Db::name('user')->where("phone='".$data['phone']."'")->find()){
			$this->response('',401,'该手机号已注册');
		}
		if(Db::name('user')->where(array('username'=>$data['username']))->find()){
			$this->response('',401,'用户名已存在');
		}
		if(Db::name('user')->where(array('nickname'=>$data['nickname']))->find()){
			$this->response('',401,'昵称重复，请更改');
		}
		$data['password']=psw($data['password']);
		$res=Db::name('user')->insert($data);
		Cache::update('user');
		Hook::doHook('api_member_register',array($res));
		$this->response($res,200,'注册成功！');
	}
	
	public function login(){
		$username=input('post.username','');
		$password=input('post.password','');
		if(empty($username) || empty($password)){
			$this->response('',401,'用户名或密码不能为空');
		}
		if(checkForm('email',$username)){
			$member=Db::name('user')->where(array('email'=>$username,'role'=>array('in',"'admin','member'")))->find();
		}else{
			$member=Db::name('user')->where(array('username'=>$username,'role'=>array('in',"'admin','member'")))->find();
		}
		if(empty($member) || $member['password'] != psw($password)){
			$this->response('',401,'用户名或密码错误');
		}
		if($member['status'] != 0){
			$this->response('',401,'该账户已被禁用');
		}
		$data=array(
			'id'=>$member['id'],
			'username'=>$member['username'],
		);
		session('MEUSER',$data);
		$token=$this->setToken($member);
		Hook::doHook('api_member_login',array($token));
		$this->response($token,200,'登录成功！');
	}
	
	public function out(){
		session('MEUSER',null);
		Hook::doHook('api_member_out',array(self::$user));
		$this->response('',200,'退出成功！');
	}
	
	public function getList(){
		$this->chechAuth(true);
		if(self::$user['role'] != 'admin'){
			$this->response('',401,'无权限操作！');
		}
		$status=input('status') != '' ? intval(input('status')) : 9;
		$role=!empty(input('role')) ? strip_tags(input('role')) : '';
		$page=intval(input('page')) ? intval(input('page')) : 1;
		$order=$this->getOrder(array('id'=>'a','logNum'=>'b','pageNum'=>'c','commentNum'=>'d'));
		$limit=10;
		$where=array();
		if(!empty($role)){
			$where['a.role']=$role;
		}
		if($status != 9){
			$where['a.status']=$status;
		}
		$count=Db::name('user')->alias('a')->where($where)->count();
		$pages = ceil($count / $limit);
		$options=\rp\Config::get('db');
		$prefix=$options["prefix"];
		$list=Db::name('user')->alias('a')->join(array(
			array('(select authorId,count(*) as logNum FROM '.$prefix.'logs where status =0 group by authorId) b','a.id=b.authorId','left'),
			array('(select authorId,count(*) as pageNum FROM '.$prefix.'pages where status =0 group by authorId) c','a.id=c.authorId','left'),
			array('(select authorId,count(*) as commentNum FROM '.$prefix.'comment where status =0 group by authorId) d','a.id=d.authorId','left'),
		))->where($where)->field('a.*,IFNULL(b.logNum,0) as logNum,IFNULL(c.pageNum,0) as pageNum,IFNULL(d.commentNum,0) as commentNum')->limit(($page-1)*$limit.','.$limit)->order($order)->select();
		foreach($list as $k=>&$v){
			unset($v['password']);
			$v['url'] = Url::other('author',$v['id']);
		}
		Hook::doHook('api_member_list',array($list));
		$page=array('count'=>$count,'pageAll'=>$pages,'limit'=>$limit,'pageNow'=>$page);
		$this->response(array('list'=>$list,'pageBar'=>$page));
	}
	
	public function post(){
		$this->chechAuth(true);
		$nickname=strip_tags(input('post.nickname'));
		$password=strip_tags(input('post.password'));
		$password2=strip_tags(input('post.password2'));
		if(empty($nickname)){
			$this->response('',401,'昵称不可为空');
		}
		if(!empty($password) && $password != $password2){
			$this->response('',401,'两次密码输入不一致');
		}
		$updata=array('nickname'=>$nickname);
		if(!empty($password)){
			$updata['password']=psw($password);
		}
		if($res=Db::name('user')->where(array('id'=>self::$user['id']))->update($updata)){
			Cache::update('user');
			Hook::doHook('api_member_post',array(self::$user));
			$this->response('',200,'修改成功');
		}
		$this->response('',401,'修改失败');
	}
	
	public function getData(){
		$this->chechAuth(true);
		$id=intval(input('id')) ? intval(input('id')) : 0;
		if(empty($id)){
			$this->response('',401,'数据错误');
		}
		$member=Db::name('user')->where(array('id'=>$id))->find();
		if(empty($member)){
			$this->response('',401,'获取数据失败');
		}
		if($member['id'] != self::$user['id'] && self::$user['role'] != 'admin'){
			$this->response('',401,'无权限操作！');
		}
		unset($member['password']);
		$this->response($member);
	}
	
	public function status(){
		$this->chechAuth(true);
		if(self::$user['role'] != 'admin'){
			$this->response('',401,'无权限操作！');
		}
		$id=intval(input('id')) ? intval(input('id')) : 0;
		$status=intval(input('status')) ? intval(input('status')) : 0;
		if(empty($id) || $id == 1){
			$this->response('',401,'数据错误');
		}
		$status=$status == 0 ? 0 : -1;
		$res=Db::name('user')->where(array('id'=>$id))->update(array('status'=>$status));
		Cache::update('user');
		Hook::doHook('api_member_status',array($id));
		$this->response($id,200,'操作成功！');
	}
	
	public function dele(){
		$this->chechAuth(true);
		if(self::$user['role'] != 'admin'){
			$this->response('',401,'无权限操作！');
		}
		$id=intval(input('id')) ? intval(input('id')) : 0;
		if(empty($id)){
			$this->response('',401,'数据错误');
		}
		if(Db::name('user')->where(array("id"=>$id))->find()){
			$res=Db::name('user')->where(array('id'=>$id))->dele();
			$res2=Db::name('logs')->where(array('authorId'=>$id))->update(array('authorId'=>self::$user['id']));//更改文章到当前管理名下
			$res2=Db::name('pages')->where(array('authorId'=>$id))->update(array('authorId'=>self::$user['id']));//更改单页到当前管理名下
			$res2=Db::name('attachment')->where(array('authorId'=>$id))->update(array('authorId'=>self::$user['id']));//更改附件到当前管理名下
			$res2=Db::name('comment')->where(array('authorId'=>$id))->update(array('authorId'=>self::$user['id']));//更改评论到当前管理名下
			Cache::update();
			Hook::doHook('api_member_dele',array($id));
			$this->response($id,200,'删除成功！');
		}
		$this->response('',401,'删除失败，用户不存在');
	}
	
}