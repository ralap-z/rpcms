<?php
namespace rp\admin;
use rp\View;
use rp\Db;
use rp\Cache;
use rp\Hook;
use rp\Config;

class Temp extends Base{
	
	public function __construct(){
		parent::__construct();
	}
	
	public function index(){
		$tempIndex=TMPPATH . '/index';
		$tempDir=getDirsInDir($tempIndex);
		$temp=array();
		foreach($tempDir as $k=>$v){
			$data=$this->getAddonsData($tempIndex .'/'.$v, 'temp');
			$temp[$v]=$data;
		}
		$tempDefault=Db::name('config')->where('cname = "template"')->field('cvalue')->find();
		View::assign('list',$temp);
		View::assign('tempDefault',$tempDefault['cvalue']);
		View::assign('wapTemp',Config::get('webConfig.wap_template'));
		return View::display('/temp_index');
	}
	
	public function upTemp(){
		$value=!empty(input('value')) ?  strip_tags(input('value')) : '';
		if(empty($value)){
			return json(array('code'=>-1,'msg'=>'ID数据错误'));
		}
		if(!is_string($value) || !preg_match("/^[\w\-\_]+$/", $value)){
			return json(array('code'=>-1,'msg'=>'ID数据非法'));
		}
		$tempDir=TMPPATH . '/index/'.$value;
		if(!is_dir($tempDir)){
			return json(array('code'=>-1,'msg'=>'模板不存在'));
		}
		$check=$this->checkAddoneRequest($tempDir, 'temp');
		if(!empty($check)){
			return json(array('code'=>-2,'msg'=>$check));
		}
		if(Db::name('config')->where('cname = "template"')->update(array('cvalue'=>$value))){
			$default=TMPPATH . '/index/'.$value . '/default.php';
			$defaultArr=array();
			if(file_exists($default)){
				$defaultArr=include_once $default;
			}
			if(is_array($defaultArr) && !empty($defaultArr) && !$res=Db::name('config')->where('cname = "temp_'.$value.'"')->find()){
				$res=Db::name('config')->insert(array('cname' => 'temp_'.$value,'cvalue'=>json_encode($defaultArr)));
			}
			Cache::update('template');
			$this->App->resetHook();
			$settingFile=TMPPATH . '/index/'.$value.'/setting.php';
			if(is_file($settingFile)){
				View::assign('settingFile',$settingFile);
				View::update('/temp_setting');
			}
			$cashFiles=CMSPATH .'/data/temp/plugin';
			if(is_file($cashFiles)){
				deleteFile($cashFiles);
			}
			return json(array('code'=>200,'msg'=>'模板切换成功'));
		}
		return json(array('code'=>-1,'msg'=>'模板切换失败，请稍后重试'));
	}
	
	public function dele(){
		$value=!empty(input('value')) ?  strip_tags(input('value')) : '';
		if(empty($value)){
			return json(array('code'=>-1,'msg'=>'ID数据错误'));
		}
		if(!is_string($value) || !preg_match("/^[\w\-\_]+$/", $value)){
			return json(array('code'=>-1,'msg'=>'ID数据非法'));
		}
		$tempDefault=Db::name('config')->where('cname = "template"')->field('cvalue')->find();
		if($value == $tempDefault['cvalue']){
			return json(array('code'=>-1,'msg'=>'该模板正在使用，不能删除'));
		}
		Db::name('config')->where('cname = "temp_'.$value.'"')->dele();
		$tempDir=TMPPATH . '/index/'.$value;
		deleteFile($tempDir);
		return json(array('code'=>200,'msg'=>'模板删除成功'));
	}
	
	public function setting(){
		$temp=!empty(input('temp')) ?  strip_tags(input('temp')) : '';
		if(empty($temp)){
			rpMsg('ID数据错误');
		}
		if(!is_string($temp) || !preg_match("/^[\w\-\_]+$/", $temp)){
			rpMsg('ID数据非法');
		}
		$tempDefault=Db::name('config')->where('cname = "template"')->field('cvalue')->find();
		$wapTemp=Config::get('webConfig.wap_template');
		if($tempDefault['cvalue'] != $temp && $temp != $wapTemp){
			rpMsg('模板未启用，请先启用后再设置');
		}
		$sendpost=intval(input('sendpost')) == 1 ? 1 : 0;
		$cfg=Db::name('config')->where('cname = "temp_'.$temp.'"')->field('cvalue')->find();
		if($sendpost == 1){
			$data=input('post.');
			unset($data['sendpost']);
			$default=TMPPATH . '/index/'.$temp . '/default.php';
			$defaultArr=array();
			if(file_exists($default)){
				$defaultArr=include_once $default;
			}
			$data=array_merge($defaultArr,$data);
			if($cfg){
				$res=Db::name('config')->where('cname = "temp_'.$temp.'"')->update(array('cvalue'=>json_encode($data)));
			}else{
				$res=Db::name('config')->insert(array('cname' => 'temp_'.$temp,'cvalue'=>json_encode($data)));
			}
			Cache::update('template');
			Cache::update('waptemplate');
			$referer=!empty($this->App->server('REQUEST_URI')) ? $this->App->server('REQUEST_URI') : $this->App->server('HTTP_REFERER');
			redirect($referer);
		}else{
			$tempFile=TMPPATH . '/index/'.$temp;
			$settingFile=$tempFile.'/setting.php';
			$tempFile=str_replace(CMSPATH, '', $tempFile);
			View::assign('config',json_decode($cfg['cvalue'],true));
			View::assign('tempFile',$tempFile);
			View::assign('settingFile',$settingFile);
			return View::display('/temp_setting');
		}
	}
	
}
