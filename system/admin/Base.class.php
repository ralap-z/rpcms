<?php
namespace rp\admin;
use rp\View;
use rp\Db;
use rp\Hook;
use rp\Cache;

class Base{
	protected $user;
	protected $me_alias=array('index','post','search','author','category','html','tag','date','plugin','page','special');
	protected $me_tmpName=array('common','setting','password','index','list','page','search','detail','default','hook','special','block');
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
		if($this->isAjax || $this->App->isPost()){
			if(!csrf_token_check() || empty($App::server('HTTP_REFERER'))){
				return json(array('code'=>-1, 'msg'=>'token验证失败'));
			}
			csrf_token_create();
		}else{
			csrf_token_create();
		}
		View::assign('user',$this->user);
		View::assign('hasLeftMenu',!empty($leftMenu));
	}
	
	private function getCommentExamNum(){
		$num=Db::name('comment')->where('status=1')->count();
		View::assign('commentExamNum',$num);
	}
	
	protected function checkAlias($alias=''){
		if(!empty($alias)){
			if(!preg_match('/^(?!\d+$)[A-Za-z0-9\-\_]+$/u',$alias)){
				return json(array('code'=>-1, 'msg'=>'别名错误，应由字母、数字、下划线、短横线组成'));
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
		return !empty($extend) ? json_encode($extend) : '';
	}
	
	protected function getKey(){
		$option=Cache::read('option');
		return isset($option['key']) ? $option['key'] : '';
	}
	
	protected function getAddonsData($path, $addonsType='temp'){
		$authorFile=$path.'/author.json';
		$pluginDir=str_replace(CMSPATH, $this->App->appPath, $path);
		$authorData=array(
			'name'=>'',
			'version'=>'',
			'date'=>'',
			'url'=>'',
			'description'=>'',
			'author'=>'',
			'authorEmail'=>'',
			'authorUrl'=>'',
			'preview'=>'/static/images/temp_preview.jpg',
			'icon'=>'/static/images/plugin_icon.jpg',
			'setting'=>false,
			'require'=>[],
		);
		if(!is_file($authorFile) || !is_readable($authorFile)){
			return $authorData;
		}
		$str=@file_get_contents($authorFile);
		$str='{'.rtrim(str_replace(["\r\n", "\n", '：'], ['', '', ':'], $str), ',').'}';
		$str=preg_replace("/([\{\}\,]+)\s?'?\s?(\w*?)\s?'?\s?:\s?/", '\\1"\\2":', $str);
		$str=preg_replace(["/'([^']*)'/", "/\s(?=\s)/", "/\t+/"], ['"$1"', '\\1', ''], $str);
		$str=json_decode($str, true);
		$authorData=array_merge($authorData, $str);
		$settingFile=$addonsType == 'temp' ? 'setting.php' : 'Setting.class.php';
		is_file($path.'/'.$settingFile) && $authorData['setting']=true;
		is_file($path.'/icon.png') && $authorData['icon']=$pluginDir.'/icon.png';
		is_file($path.'/preview.jpg') && $authorData['preview']=$pluginDir.'/preview.jpg';
		return $authorData;
	}
	protected function checkAddoneRequest($filePath, $addonsType='temp'){
		$data=$this->getAddonsData($filePath);
		if(empty($data['require'])){
			return;
		}
		$check=$message=[];
		foreach($data['require'] as $k=>$v){
			$pluginFile=PLUGINPATH .'/'.$k;
			$check[$k]=$this->getAddonsData($pluginFile);
			if(empty($check[$k]['version'])){
				$message[]='【'.$k.'】(v'.$v.')未安装';
				continue;
			}
			if(version_compare($check[$k]['version'], $v, '<')){
				$message[]='【'.$k.'/'.$check[$k]['name'].'】版本过低，要求 >= v'.$v;
				continue;
			}
			if(!pluginCheck($k)){
				$message[]='【'.$k.'/'.$check[$k]['name'].'】(v'.$check[$k]['version'].')未启用';
			}
		}
		unset($check);
		return implode('<br>', $message);
	}
	protected function getTempFile($defaultValue=''){
		$template=Cache::read('template');
		$tempPath=TMPPATH . '/index/'.$template['name'];
		$data=glob($tempPath.'/*.php');
		$data=array_map(function($v){
			return pathinfo($v, PATHINFO_FILENAME);
		}, $data);
		$meTmpName=array_merge($this->me_tmpName, ['header', 'footer', '404', 'Hook.class']);
		$data=array_filter($data, function($v)use($meTmpName){
			return !in_array($v, $meTmpName);
		});
		$data=array_combine($data, $data);
		foreach($data as $k=>&$v){
			$handle=@fopen($tempPath.'/'.$v.'.php', "r");
			preg_match('/\/\*(.+?)\*\//', fgets($handle), $matches);
			$v=$matches[1] ?? $v;
			fclose($handle);
		}
		$html='<option value="">选择模板</option>';
		foreach($data as $key=>$value){
			$html.='<option value="'.$key.'" '.($key == $defaultValue ? "selected" : "").'>'.$value.'</option>';
		}
		return $html;
	}
}
