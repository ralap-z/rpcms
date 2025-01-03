<?php
namespace rp\admin;
use rp\View;
use rp\Db;
use rp\Cache;
use rp\Config;

class User extends Base{
	
	private $level=array(
		'admin'=>'管理员',
		'member'=>'会员',
	);
	
	public function __construct(){
		parent::__construct();
		View::assign('level',$this->level);
	}
	
	public function index(){
		$status=input('status') != '' ? intval(input('status')) : 9;
		$role=!empty(input('role')) ? strip_tags(input('role')) : '';
		$page=intval(input('page')) ? intval(input('page')) : 1;
		$limit=10;
		$where=array();
		$search=array();
		if(!empty($role)){
			$where['a.role']=$role;
			$search[]="role=".$role;
		}
		if($status != 9){
			$where['a.status']=$status;
			$search[]="status=".$status;
		}
		$count=Db::name('user')->alias('a')->where($where)->count();
		$pages = ceil($count / $limit);
        if($page >= $pages && $pages > 0){
            $page = $pages;
        }
		$res=Db::name('user')->alias('a')->where($where)->field('a.*')->limit(($page-1)*$limit.','.$limit)->order('a.id','desc')->select();
		$user=Cache::read('user');
		foreach($res as $k=>&$v){
			$v['logNum']=isset($user[$v['id']]) ? $user[$v['id']]['logNum'] : 0;
			$v['pageNum']=isset($user[$v['id']]) ? $user[$v['id']]['pageNum'] : 0;
			$v['commentNum']=isset($user[$v['id']]) ? $user[$v['id']]['commentNum'] : 0;
		}
		$pageHtml=pageInation($count,$limit,$page,'',join('&',$search));
		View::assign('list',$res);
		View::assign('s_status',$status);
		View::assign('s_role',$role);
		View::assign('pageHtml',$pageHtml);
		return View::display('/user_index');
	}
	
	public function search(){
		$key=strip_tags(input('key'));
		$where=[];
		if(!empty($key)){
			$where['username|nickname']=['like', $key.'%'];
		}
		$res=Db::name('user')->where($where)->field('id,if(nickname = "",username, nickname) as title')->order('id','asc')->limit('0,10')->select();
		return json(['code'=>200, 'msg'=>'success', 'data'=>$res]);
	}
	
	public function getinfo(){
		$id=intval(input('id')) ? intval(input('id')) : 0;
		if(empty($id)){
			return json(array('code'=>-1,'msg'=>'ID数据错误'));
		}
		$res=Db::name('user')->where('id='.$id)->find();
		return json(array('code'=>200,'msg'=>'success','data'=>$res));
	}
	
	public function oper(){
		$type=input('type') ? input('type') : '';
		$value=!empty(input('value')) ? strip_tags(input('value')) : '';
		$ids=input('ids') ? input('ids') : '';
		if(!method_exists($this,'me_'.$type)){
			return json(array('code'=>-1,'msg'=>'无效操作'));
		}
		$idsArr=explode(',',$ids);
		$idsArr=arrayIdFilter($idsArr);
		foreach($idsArr as $k=>$v){
			if($v == 1) unset($idsArr[$k]);
		}
		if(empty($idsArr)){
			return json(array('code'=>-1,'msg'=>'提交用户为空'));
		}
		return call_user_func(array($this, 'me_' . $type),join(',',$idsArr),$value);
	}
	
	public function upStatus(){
		$id=intval(input('id')) ? intval(input('id')) : 0;
		$status=intval(input('status')) ? intval(input('status')) : 0;
		if(empty($id)){
			return json(array('code'=>-1,'msg'=>'ID数据错误'));
		}
		if($id == 1){
			return json(array('code'=>-1,'msg'=>'管理员ID=1，不可修改状态'));
		}
		return call_user_func(array($this, 'me_status'),$id,$status);
	}
	
	public function doAdd(){
		$data=array();
		$data['username']=!empty(input('username')) ? strip_tags(input('username')) : '';
		$data['password']=!empty(input('password')) ? strip_tags(input('password')) : '';
		$data['nickname']=!empty(input('nickname')) ? strip_tags(input('nickname')) : $data['username'];
		$data['role']=!empty(input('role')) ? strip_tags(input('role')) : 'member';
		$data['status']=intval(input('status')) ? intval(input('status')) : 0;
		$data['isCheck']=input('isCheck') == 0 ? 0 : 1;
		if(empty($data['username'])){
			return json(array('code'=>-1,'msg'=>'用户名不能为空'));
		}
		if(empty($data['password'])){
			return json(array('code'=>-1,'msg'=>'密码不能为空'));
		}
		if(strlen($data['password']) < 6){
			return json(array('code'=>-1,'msg'=>'密码至少6位'));
		}
		$checkUsername=Db::name('user')->where(array('username'=>$data['username']))->find();
		if(!empty($checkUsername)){
			return json(array('code'=>-1,'msg'=>'用户名已存在'));
		}
		$checkNickname=Db::name('user')->where(array('nickname'=>$data['nickname']))->find();
		if(!empty($checkNickname)){
			return json(array('code'=>-1,'msg'=>'昵称重复，请更改'));
		}
		$data['password']=psw($data['password']);
		$res=Db::name('user')->insert($data);
		Cache::update('user');
		return json(array('code'=>200,'msg'=>'添加成功'));
	}
	
	public function doUpdate(){
		$id=intval(input('id')) ? intval(input('id')) : 0;
		$data=array();
		$data['username']=!empty(input('username')) ? strip_tags(input('username')) : '';
		$data['nickname']=!empty(input('nickname')) ? strip_tags(input('nickname')) : $data['username'];
		$data['role']=!empty(input('role')) ? strip_tags(input('role')) : 'member';
		$data['status']=intval(input('status')) ? intval(input('status')) : 0;
		$data['isCheck']=input('isCheck') == 0 ? 0 : 1;
		$password=!empty(input('password')) ? strip_tags(input('password')) : '';
		if(empty($id)){
			return json(array('code'=>-1,'msg'=>'数据错误，ID IS NULL'));
		}
		if(empty($data['username'])){
			return json(array('code'=>-1,'msg'=>'用户名不能为空'));
		}
		if(!empty($password)){
			if(strlen($password) < 6){
				return json(array('code'=>-1,'msg'=>'密码至少6位'));
			}
			$data['password']=psw($password);
		}
		$user=Db::name('user')->where('id='.$id)->find();
		if(empty($user)){
			return json(array('code'=>-1,'msg'=>'用户不存在'));
		}
		if($data['username'] != $user['username'] && $checkUsername=Db::name('user')->where(array('username'=>$data['username']))->find()){
			return json(array('code'=>-1,'msg'=>'用户名已存在'));
		}
		if($data['nickname'] != $user['nickname'] && $checkNickname=Db::name('user')->where(array('nickname'=>$data['nickname']))->find()){
			return json(array('code'=>-1,'msg'=>'昵称重复，请更改'));
		}
		$res=Db::name('user')->where('id='.$id)->update($data);
		Cache::update('user');
		return json(array('code'=>200,'msg'=>'修改成功'));
	}
	
	public function dele(){
		$id=intval(input('id')) ? intval(input('id')) : 0;
		if(empty($id)){
			return json(array('code'=>-1,'msg'=>'ID数据错误'));
		}
		if(Db::name('user')->where("id=".$id)->find()){
			$res=Db::name('user')->where('id='.$id)->dele();
			$res2=Db::name('logs')->where('authorId='.$id)->update(array('authorId'=>$this->user['id']));//更改文章到当前管理名下
			$res2=Db::name('pages')->where('authorId='.$id)->update(array('authorId'=>$this->user['id']));//更改单页到当前管理名下
			$res2=Db::name('attachment')->where('authorId='.$id)->update(array('authorId'=>$this->user['id']));//更改附件到当前管理名下
			$res2=Db::name('comment')->where('authorId='.$id)->update(array('authorId'=>$this->user['id']));//更改评论到当前管理名下
			Cache::update();
			return json(array('code'=>200,'msg'=>'删除成功'));
		}
		return json(array('code'=>-1,'msg'=>'删除失败，用户不存在'));
	}
	
	
	/*设置级别*/
	private function me_level($ids,$value=''){
		$levelKey=array_keys($this->level);
		if(!in_array($value,$levelKey)){
			return json(array('code'=>-1,'msg'=>'提交数据非法'));
		}
		$res=Db::name('user')->where(array('id'=>array('in',$ids)))->update(array('role'=>$value));
		Cache::update('user');
		return json(array('code'=>200,'msg'=>'设置级别成功'));
	}
	/*设置状态*/
	private function me_status($ids,$value=''){
		$value=intval($value);
		$value=$value == 0 ? 0 : -1;
		$res=Db::name('user')->where(array('id'=>array('in',$ids)))->update(array('status'=>$value));
		Cache::update('user');
		return json(array('code'=>200,'msg'=>'设置状态成功'));
	}

}
