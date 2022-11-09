<?php
namespace rp\admin;

use rp\Db;

class Upgrade extends Base{
	
	private $server='https://www.rpcms.cn';
	private $updateLockFile;
	
	public function __construct(){
		parent::__construct();
		$this->updateLockFile=CMSPATH.'/data/upgrade.lock';
	}
	
	public function check(){
		$url=$this->server.'/upgrade/index/check';
		$param=array(
			'version'=>RPCMS_VERSION,
			'site'=>$this->App->baseUrl,
			'key'=>$this->getKey(),
		);
		$resArr=@json_decode(http_post($url,$param),true);
		if($resArr['code'] != 200){
			return json($resArr);
		}
		$upgradefile=array();
		foreach($resArr['data'] as $k=>$v){
			$path=CMSPATH.$v['name'];
			if(!file_exists($path) || @md5_file($path) != $v['md5']){
				$res1=preg_match('/([\w\.]+)-update\.sql/i', $v['name'], $matches1);
				$res2=preg_match('/([\w\.]+)-start\.php/i', $v['name'], $matches2);
				$res3=preg_match('/([\w\.]+)-ending\.php/i', $v['name'], $matches3);
				if(($res1 && $matches1[1] <= RPCMS_VERSION) || ($res2 && $matches2[1] <= RPCMS_VERSION) || ($res3 && $matches3[1] <= RPCMS_VERSION)){
					continue;
				}
				$v['type']= file_exists($path) ? '<span style="color:red">更新</span>' : '新增';
				$upgradefile[]=$v;
			}
		}
		if(!empty($upgradefile)){
			return json(array('code'=>200,'msg'=>'有文件需要更新','data'=>$upgradefile));
		}else{
			return json(array('code'=>-1,'msg'=>'您的系统无任何文件需要更新'));
		}
	}
	
	public function start(){
		@file_put_contents($this->updateLockFile, time());
	}
	
	public function ending(){
		$dir=CMSPATH.'/data/update';
		if(is_dir($dir)){
			$files=glob($dir.'/*-ending.php');
			foreach($files as $file){
				include $file;
			}
			deleteFile($dir);
		}
		@unlink($this->updateLockFile);
	}
	
	public function files(){
		$file=input('post.file');
		if(empty($file)){
			return json(array('code'=>-1,'msg'=>'更新文件不能为空'));
		}
		$url=$this->server.'/upgrade/index/getFile';
		$param=array(
			'file'=>$file,
			'site'=>$this->App->baseUrl,
			'key'=>$this->getKey(),
		);
		$resArr=@json_decode(http_post($url,$param),true);
		if($resArr['code'] != 200){
			$resArr['msg']=$file.$resArr['msg'];
			return json($resArr);
		}
		if(preg_match('/([\w\.]+)-update\.sql/i', $file, $matches)){
			if($matches[1] >= RPCMS_VERSION){
				if($this->executeSql(base64_decode($resArr['data']))){
					return json(array('code'=>200,'msg'=>$file.'更新成功'));
				}
				return json(array('code'=>-1,'msg'=>$file.'更新失败'));
			}else{
				return json(array('code'=>-1,'msg'=>'当前版本大于更新版本，'.$file.'此数据库文件不需要更新'));
			}
		}else{
			$isStartRun=false;
			if(preg_match('/([\w\.]+)-start\.php/i', $file, $matches)){
				if($matches[1] < RPCMS_VERSION){
					return json(array('code'=>-1,'msg'=>'当前版本大于更新版本，'.$file.'不需要更新'));
				}
				$file='/data'.$file;
				$isStartRun=true;
			}
			if(preg_match('/([\w\.]+)-ending\.php/i', $file, $matches)){
				if($matches[1] < RPCMS_VERSION){
					return json(array('code'=>-1,'msg'=>'当前版本大于更新版本，'.$file.'不需要更新'));
				}
				$file='/data'.$file;
			}
			$filePath=CMSPATH.'/'.$file;
			$fileDir=dirname($filePath);
			if(!file_exists($fileDir) && !mkdir($fileDir, 0777, true)){
				return json(array('code'=>-1, 'msg'=>'写入权限不足，无法正常升级'));
			}
			if(file_put_contents($filePath,base64_decode($resArr['data']))){
				if($isStartRun){
					include $filePath;
				}
				return json(array('code'=>200,'msg'=>$file.'更新成功'));
			}else{
				return json(array('code'=>-1,'msg'=>$file.'更新失败'));
			}
		}
	}
	
	private function executeSql($sql){
		$sql = explode(';', $sql);
		$options = \rp\Config::get('db');
		$db=Db::instance();
		$db::transaction();
		try{
			foreach ($sql as $v){
				if(!empty(trim($v))){
					$db->query(str_replace('%pre%',$options['prefix'],$v));
				}
			}
			$db::commit();
			return true;
		}catch(\Exception $e){
			$db::rollback();
			return false;
		}
	}
	
}
