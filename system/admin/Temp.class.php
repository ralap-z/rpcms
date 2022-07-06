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
			$data=$this->getTempData($tempIndex .'/'.$v);
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
			return array('code'=>-1,'msg'=>'模板不存在');
		}
		if(Db::name('config')->where('cname = "template"')->update(array('cvalue'=>$value))){
			$default=TMPPATH . '/index/'.$value . '/default.php';
			$defaultArr=array();
			if(file_exists($default)){
				$defaultArr=include_once $default;
			}
			if(is_array($defaultArr) && !empty($defaultArr) && !$res=Db::name('config')->where('cname = "temp_'.$value.'"')->find()){
				$res=Db::name('config')->insert(array('cname' => 'temp_'.$value,'cvalue'=>addslashes(json_encode($defaultArr))));
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
				$res=Db::name('config')->where('cname = "temp_'.$temp.'"')->update(array('cvalue'=>addslashes(json_encode($data))));
			}else{
				$res=Db::name('config')->insert(array('cname' => 'temp_'.$temp,'cvalue'=>addslashes(json_encode($data))));
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
	
	private function getTempData($tempFile){
		$authorFile=$tempFile.'/author.json';
		$tempDir=str_replace(CMSPATH, $this->App->appPath, $tempFile);
		if(file_exists($authorFile) && is_readable($authorFile)){
			$authorData=array();
			$str=@file_get_contents($authorFile);
			preg_match("/name:(.*)/i", $str, $pluginName);
			preg_match("/version:(.*)/i", $str, $pluginVersion);
			preg_match("/date:(.*)/i", $str, $pluginDate);
			preg_match("/url:(.*)/i", $str, $pluginUrl);
			preg_match("/description:(.*)/i", $str, $pluginDescription);
			preg_match("/author:(.*)/i", $str, $pluginAuthor);
			preg_match("/authorEmail:(.*)/i", $str, $pluginAuthorEmail);
			preg_match("/authorUrl:(.*)/i", $str, $pluginAuthorUrl);
			$authorData['name']=isset($pluginName[1]) ? strip_tags(str_replace(array('\'',','),'',trim($pluginName[1]))) : '';
			$authorData['version']=isset($pluginVersion[1]) ? strip_tags(str_replace(array('\'',','),'',trim($pluginVersion[1]))) : '';
			$authorData['date']=isset($pluginDate[1]) ? strip_tags(str_replace(array('\'',','),'',trim($pluginDate[1]))) : '';
			$authorData['url']=isset($pluginUrl[1]) ? strip_tags(str_replace(array('\'',','),'',trim($pluginUrl[1]))) : '';
			$authorData['description']=isset($pluginDescription[1]) ? strip_tags(str_replace(array('\'',','),'',trim($pluginDescription[1]))) : '';
			$authorData['author']=isset($pluginAuthor[1]) ? strip_tags(str_replace(array('\'',','),'',trim($pluginAuthor[1]))) : '';
			$authorData['authorEmail']=isset($pluginAuthorEmail[1]) ? strip_tags(str_replace(array('\'',','),'',trim($pluginAuthorEmail[1]))) : '';
			$authorData['authorUrl']=isset($pluginAuthorUrl[1]) ? strip_tags(str_replace(array('\'',','),'',trim($pluginAuthorUrl[1]))) : '';
			$authorData['preview']=file_exists($tempFile.'/preview.jpg') ? $tempDir.'/preview.jpg' : '/static/images/temp_preview.jpg';
			$authorData['setting']=file_exists($tempFile.'/setting.php') ? true : false;
			return $authorData;
		}
		return false;
	}
	
}
