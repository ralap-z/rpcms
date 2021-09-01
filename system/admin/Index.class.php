<?php
namespace rp\admin;
use rp\View;
use rp\Db;
use rp\Cache;

class Index extends Base{
	
	public function __construct(){
		parent::__construct();
	}
	
	public function index(){
		$totalData=array(
			'logs'=>Db::name('logs')->where('status = 0')->count(),
			'page'=>Db::name('pages')->where('status = 0')->count(),
			'cate'=>Db::name('category')->count(),
			'tages'=>Db::name('tages')->count(),
			'comment'=>Db::name('comment')->where('status = 0')->count(),
		);
		View::assign('totalData',$totalData);
		return View::display('/index');
	}
	
	public function webset(){
		View::assign('option',Cache::read('option'));
		View::assign('tempList',getDirsInDir(TMPPATH . '/index'));
		return View::display('/webset_index');
	}
	
	public function webPost(){
		$data=input('post.');
		if(isset($data['fileTypes'])){
			$fileTypesArr=explode(',',$data['fileTypes']);
			$fileTypesArr=array_filter($fileTypesArr,function($v){
				$v=trim($v);
				return !in_array($v,array('php','asp','py','java'));
			});
			$fileTypesArr=array_map(function($v){return trim($v);},$fileTypesArr);
			$data['fileTypes']=join(',',$fileTypesArr);
		}
		if(Db::name('config')->where('cname="webconfig"')->find()){
			$res=Db::name('config')->where('cname="webconfig"')->update(array('cvalue'=>addslashes(json_encode($data))));
		}else{
			$res=Db::name('config')->insert(array('cname'=>'webconfig','cvalue'=>addslashes(json_encode($data))));
		}
		Cache::update('option');
		Cache::update('waptemplate');
		return json(array('code'=>200, 'msg'=>'修改配置成功'));
	}
	
	public function updatePsw(){
		$nickname=strip_tags(input('post.nickname'));
		$password=strip_tags(input('post.password'));
		$password2=strip_tags(input('post.password2'));
		if(empty($nickname)){
			return json(array('code'=>-1,'msg'=>'昵称不可为空'));
		}
		if(!empty($password) && $password != $password2){
			return json(array('code'=>-1,'msg'=>'两次密码输入不一致'));
		}
		$updata=array('nickname'=>$nickname);
		if(!empty($password)){
			$updata['password']=psw($password);
		}
		if($res=Db::name('user')->where('id='.$this->user['id'])->update($updata)){
			Cache::update('user');
			return json(array('code'=>200,'msg'=>'修改成功','data'=>!empty($password) ? 1 : 0));
		}
		return json(array('code'=>-1,'msg'=>'修改失败，请稍后重试'));
	}
	
	public function attrSelect(){
		$logid=intval(input('logid')) ? intval(input('logid')) : '';
		$pageId=intval(input('pageId')) ? intval(input('pageId')) : '';
		if(empty($logid) && empty($pageId)){
			return json(array('code'=>-1,'msg'=>'数据错误，ID为空'));
		}
		$where=!empty($logid) ? 'logId='.$logid : 'pageId='.$pageId;
		$attr=Db::name('attachment')->where($where)->select();
		foreach($attr as &$v){
			$v['downurl']=str_replace($this->App->baseUrl,'',url('/index/down')).'?token='.$v['token'];
		}
		return json(array('code'=>200, 'msg'=>'success', 'data'=>$attr));
	}
	
	public function attrDele(){
		$id=intval(input('id')) ? intval(input('id')) : '';
		$attrId=intval(input('attrId')) ? intval(input('attrId')) : '';
		$type=input('type') == 'pages' ? 'pages' : 'logs';
		if(empty($id) && empty($attrId)){
			return json(array('code'=>-1,'msg'=>'数据错误，ID数据为空'));
		}
		$where=$type == 'logs' ? 'logId='.$id : 'pageId='.$id;
		$attr=Db::name('attachment')->where('id='.$attrId.' and '.$where)->find();
		if(!empty($attr) && Db::name('attachment')->where('id='.$attrId.' and '.$where)->dele()){
			@unlink(CMSPATH . $attr['filepath']);
		}
		return json(array('code'=>200, 'msg'=>'success'));
	}
	
	public function cacheUpdate(){
		$type=input('type');
		switch($type){
			case 'all':
				Cache::update();
				$this->App->resetHook();
				break;
			case 'cms':
				Cache::update(array('option','total','links','template','waptemplate'));
				break;
			case 'user':
				Cache::update('user');
				break;
			case 'nav':
				Cache::update('nav');
				break;
			case 'logs':
				Cache::update('logRecord');
				break;
			case 'cate':
				Cache::update('category');
				break;
			case 'tages':
				Cache::update('tages');
				break;
			case 'pages':
				Cache::update('pages');
				break;
			case 'special':
				Cache::update('special');
				break;
			case 'hook':
				$this->App->resetHook();
				break;
			case 'temp':
				$cashFiles=CMSPATH .'/data/cache/index';
				if(file_exists($cashFiles)){
					deleteFile($cashFiles);
				}
				break;
			case 'plugin':
				$cashFiles=CMSPATH .'/data/cache/plugin';
				if(file_exists($cashFiles)){
					deleteFile($cashFiles);
				}
				break;
		}
		return json(array('code'=>200, 'msg'=>'更新成功'));
	}
	
	public function upload(){
		$files=isset($_FILES['files']) ? $_FILES['files'] : '';
		if(empty($files)){
			return json(array('code'=>-1, 'msg'=>'请选择文件'));
		}
		if(count($files) == count($files,true)){
			return json(uploadFiles($files));
		}else{
			$data=array(
				'successUrl'=>[],
				'successNum'=>0,
				'errorFile'=>[],
				'errorNum'=>0,
			);
			foreach($files['error'] as $k=>$v){
				$file=array(
					'name'=>$files['name'][$k],
					'type'=>$files['type'][$k],
					'tmp_name'=>$files['tmp_name'][$k],
					'error'=>$files['error'][$k],
					'size'=>$files['size'][$k],
				);
				$res=uploadFiles($file);
				if($res['code'] == 200){
					$data['successNum']++;
					$data['successUrl'][]=$res['data'];
				}else{
					$data['errorNum']++;
					$data['errorFile'][]=$file['name'].'，'.$res['msg'];
				}
			}
			return json(array('code'=>200, 'msg'=>'success', 'data'=>$data));
		}
	}
}
