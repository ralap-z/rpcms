<?php
namespace rp\admin;
use rp\View;
use rp\Db;
use rp\Cache;
use rp\Hook;
use rp\Plugin as mePlugin;

class Plugin extends Base{
	
	public function __construct(){
		parent::__construct();
	}
	
	public function index(){
		$pluginDir=getDirsInDir(PLUGINPATH);
		$plugin=array();
		foreach($pluginDir as $k=>$v){
			$pluginFile=PLUGINPATH .'/'.$v;
			$indexFile=$pluginFile .'/Index.class.php';
			if(file_exists($indexFile) && is_readable($indexFile) && $data=$this->getPluginData($pluginFile)){
				$plugin[$v]=$data;
			}
		}
		$pluginUse=Db::name('plugin')->where('status=0')->field('ppath')->select();
		$pluginUse=array_column($pluginUse,'ppath');
		View::assign('list',$plugin);
		View::assign('pluginUse',$pluginUse);
		return View::display('/plugin_index');
	}
	
	public function setting(){
		$plugin=!empty(input('plugin')) ?  strip_tags(input('plugin')) : '';
		if(empty($plugin)){
			rpMsg('ID数据错误-'.$plugin);
		}
		if(!is_string($plugin) || !preg_match("/^[\w\-\_]+$/", $plugin)){
			rpMsg('ID数据非法');
		}
		$res=Db::name('plugin')->where("ppath='".$plugin."'")->find();
		if(empty($res)){
			rpMsg('插件未安装');
		}
		$sendpost=intval(input('sendpost')) == 1 ? 1 : 0;
		if($sendpost == 1){
			$data=input('post.');
			unset($data['sendpost']);
			unset($data['plugin']);
			$mePlugin=new mePlugin();
			$mePlugin->setConfig($plugin,$data);
			$referer=!empty($this->App->server('HTTP_REFERER')) ? $this->App->server('HTTP_REFERER') : url('plugin/index');
			redirect($referer);
		}else{
			View::assign('plugin',$plugin);
			return View::display('/plugin_setting');
		}
	}
	
	public function run(){
		$to=input('to');
		$toArr=explode('/',$to);
		if(!isset($toArr[0]) || empty($toArr[0])){
			rpMsg('插件数据错误');
		}
		$plugin=$toArr[0];
		if(!is_string($plugin) || !preg_match("/^[\w\-\_]+$/", $plugin)){
			rpMsg('插件数据非法');
		}
		$controller=(isset($toArr[1]) && !empty($toArr[1])) ?  strip_tags($toArr[1]) : 'index';
		$action=(isset($toArr[2]) && !empty($toArr[2])) ?  strip_tags($toArr[2]) : 'index';
		$res=Db::name('plugin')->where("ppath='".$plugin."'")->find();
		if(empty($res)){
			rpMsg('插件未安装');
		}
		$pluginName='plugin\\'.strtolower($plugin).'\\'.ucfirst($controller);
		$pluginClass=new $pluginName;
		$res='';
		if(method_exists($pluginClass,$action)){
			$args=input('request.');
			unset($args['plugin']);
			unset($args['controller']);
			unset($args['action']);
			View::assign('controller',$controller);
			View::assign('action',$action);
			$res=$pluginClass->$action($args);
		}
		return $res;
	}
	
	public function upStatus(){
		$id=!empty(input('id')) ?  strip_tags(input('id')) : '';
		$status=intval(input('status')) == 1 ? 1 : -1;
		if(empty($id)){
			return json(array('code'=>-1,'msg'=>'ID数据错误'));
		}
		if($status == 1){
			$res=$this->openPlugin($id);
		}else{
			$res=$this->closePlugin($id);
		}
		return json($res);
	}
	
	public function dele(){
		$id=!empty(input('id')) ?  strip_tags(input('id')) : '';
		if(empty($id)){
			return json(array('code'=>-1,'msg'=>'ID数据错误'));
		}
		if(!is_string($id) || !preg_match("/^[\w\-\_]+$/", $id)){
			return json(array('code'=>-1,'msg'=>'ID数据非法'));
		}
		$close=true;
		if(Db::name('plugin')->where("ppath='".$id."'")->find()){
			$res=$this->closePlugin($id);
			if($res['code'] != 200) $close=false;
		}
		if($close){
			$resId=Db::name('plugin')->where(array('ppath'=>$id))->dele();
			if(!deleteFile(PLUGINPATH .'/'. $id)){
				return json(array('code'=>-1,'msg'=>'删除插件文件失败，请检查是否拥有权限'));
			}
			return json(array('code'=>200,'msg'=>'插件删除成功'));
		}
		return json(array('code'=>-1,'msg'=>$res['msg']));
	}
	
	
	private function closePlugin($plugin){
		$pluginFile=PLUGINPATH .'/'.$plugin;
		$indexFile=$pluginFile .'/Index.class.php';
		if(!is_string($plugin) || !preg_match("/^[\w\-\_]+$/", $plugin) || !file_exists($indexFile)){
			return array('code'=>-1,'msg'=>'插件不存在');
		}
		if(!Db::name('plugin')->where("ppath='".$plugin."'")->find()){
			return array('code'=>-1,'msg'=>'插件已卸载');
		}
		$pluginName='plugin\\'.strtolower($plugin).'\\Index';
		$pluginClass=new $pluginName;
		if(method_exists($pluginClass,'uninstall')){
			$res=$pluginClass->uninstall();
		}
		if($resId=Db::name('plugin')->where(array('ppath'=>$plugin))->update(array('status'=>-1))){
			$this->App->resetHook();
			return array('code'=>200,'msg'=>'插件卸载成功');
		}
		return array('code'=>-1,'msg'=>'插件卸载失败，请稍后重试');
	}
	
	private function openPlugin($plugin){
		$pluginFile=PLUGINPATH .'/'.$plugin;
		$indexFile=$pluginFile .'/Index.class.php';
		if(!is_string($plugin) || !preg_match("/^[\w\-\_]+$/", $plugin) || !file_exists($indexFile)){
			return array('code'=>-1,'msg'=>'插件不存在');
		}
		$res=Db::name('plugin')->where("ppath='".$plugin."'")->find();
		if(!empty($res) && $res['status'] == 0){
			return array('code'=>-1,'msg'=>'插件已激活');
		}
		$pluginName='plugin\\'.$plugin.'\\Index';
		$pluginClass=new $pluginName;
		if(method_exists($pluginClass,'install')){
			$ires=$pluginClass->install();
		}
		if(method_exists($pluginClass,'addHook')){
			$hookArr=$pluginClass->addHook();
			if(!empty($hookArr) && is_array($hookArr)){
				foreach($hookArr as $k=>$v){
					Hook::addHook($k,$v);
				}
				Hook::saveHook();
			}
		}
		if(empty($res)){
			$resId=Db::name('plugin')->insert(array('ppath'=>$plugin,'status'=>0));
		}else{
			$resId=Db::name('plugin')->where(array('ppath'=>$plugin))->update(array('status'=>0));
		}
		if($resId){
			return array('code'=>200,'msg'=>'插件安装成功');
		}
		return array('code'=>-1,'msg'=>'插件安装失败，请稍后重试');
	}
	
	private function getPluginData($pluginFile){
		$authorFile=$pluginFile.'/author.json';
		$pluginDir=str_replace(CMSPATH, $this->App->appPath, $pluginFile);
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
			$authorData['icon']=file_exists($pluginFile.'/icon.png') ? $pluginDir.'/icon.png' : '/static/images/plugin_icon.jpg';
			$authorData['setting']=file_exists($pluginFile.'/Setting.class.php') ? true : false;
			return $authorData;
		}
		return false;
	}
}
