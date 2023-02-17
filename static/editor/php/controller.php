<?php
//header('Access-Control-Allow-Origin: http://www.baidu.com'); //设置http://www.baidu.com允许跨域访问
//header('Access-Control-Allow-Headers: X-Requested-With,X_Requested_With'); //设置允许的跨域header
date_default_timezone_set("Asia/Chongqing");
error_reporting(E_ERROR);
//error_reporting(E_ALL);
session_start();
header("Content-Type: text/html; charset=utf-8");

defined('CMSPATH') or define('CMSPATH', realpath(dirname(__FILE__) .'/../../../'));
defined('LIBPATH') or define('LIBPATH', CMSPATH . '/system');
defined('PLUGINPATH') or define('PLUGINPATH', CMSPATH . '/plugin');
defined('SETTINGPATH') or define('SETTINGPATH', CMSPATH . '/setting');
defined('TMPPATH') or define('TMPPATH', CMSPATH . '/templates');
defined('UPLOADPATH') or define('UPLOADPATH',  'uploads');
defined('RPCMS_VERSION') or define('RPCMS_VERSION',  @file_get_contents(CMSPATH . '/data/defend/sersion.txt'));
include_once LIBPATH . '/Common.fun.php';
spl_autoload_register("autoLoadClass");
doStrslashes();
\rp\Config::set(include_once SETTINGPATH.'/config/default.php');
\rp\Config::set('webConfig',\rp\Cache::read('option'));
$App=new \rp\App();
$App->runHook();
		
if(!isLogin() && !session('MEUSER')){
	return json(array('code'=>-1, 'msg'=>'请先登录'));
}
$host=input('server.HTTP_HOST');
$httpReferer=str_replace(['http://','https://'], '', input('server.HTTP_REFERER'));
if(stripos($httpReferer, $host) !== 0){
	die();
}

function getUEConfig(){
	$meConfig=\rp\Config::get('webConfig');
	$uploadPath='/'.(!empty(\rp\Config::get('app_default_path')) ? \rp\Config::get('app_default_path').'/' : '').UPLOADPATH;
	$maxSize=(isset($meConfig['fileSize']) ? $meConfig['fileSize'] : get_upload_max_filesize_byte()) * 1024 * 1024;
	$uploadAllow = explode('|', '.'.str_replace(array('|',','), '|.', $meConfig['fileTypes']));
	$data=array(
		"imageActionName"=>"uploadimage", /* 执行上传图片的action名称 */
		"imageFieldName"=>"upfile", /* 提交的图片表单名称 */
		"imageMaxSize"=>$maxSize, /* 上传大小限制，单位B */
		"imageAllowFiles"=>$uploadAllow, /* 上传图片格式显示 */
		"imageCompressEnable"=>false, /* 是否压缩图片,默认是true */
		"imageCompressBorder"=>1600, /* 图片压缩最长边限制 */
		"imageInsertAlign"=>"none", /* 插入的图片浮动方式 */
		"imageUrlPrefix"=>"", /* 图片访问路径前缀 */
		"imagePathFormat"=>$uploadPath."/{yyyy}{mm}/{time}{rand:6}", /* 上传保存路径,可以自定义保存路径和文件名格式 */

		/* 涂鸦图片上传配置项 */
		"scrawlActionName"=>"uploadscrawl", /* 执行上传涂鸦的action名称 */
		"scrawlFieldName"=>"upfile", /* 提交的图片表单名称 */
		"scrawlPathFormat"=>$uploadPath."/{yyyy}{mm}/{time}{rand:6}", /* 上传保存路径,可以自定义保存路径和文件名格式 */
		"scrawlMaxSize"=>$maxSize, /* 上传大小限制，单位B */
		"scrawlUrlPrefix"=>"", /* 图片访问路径前缀 */
		"scrawlInsertAlign"=>"none",

		/* 截图工具上传 */
		"snapscreenActionName"=>"uploadimage", /* 执行上传截图的action名称 */
		"snapscreenPathFormat"=>$uploadPath."/{yyyy}{mm}/{time}{rand:6}", /* 上传保存路径,可以自定义保存路径和文件名格式 */
		"snapscreenUrlPrefix"=>"", /* 图片访问路径前缀 */
		"snapscreenInsertAlign"=>"none", /* 插入的图片浮动方式 */

		/* 抓取远程图片配置 */
		"catcherLocalDomain"=>["127.0.0.1", "localhost", "img.baidu.com"],
		"catcherActionName"=>"catchimage", /* 执行抓取远程图片的action名称 */
		"catcherFieldName"=>"source", /* 提交的图片列表表单名称 */
		"catcherPathFormat"=>$uploadPath."/{yyyy}{mm}/{time}{rand:6}", /* 上传保存路径,可以自定义保存路径和文件名格式 */
		"catcherUrlPrefix"=>"", /* 图片访问路径前缀 */
		"catcherMaxSize"=>$maxSize, /* 上传大小限制，单位B */
		"catcherAllowFiles"=>$uploadAllow, /* 抓取图片格式显示 */

		/* 上传视频配置 */
		"videoActionName"=>"uploadvideo", /* 执行上传视频的action名称 */
		"videoFieldName"=>"upfile", /* 提交的视频表单名称 */
		"videoPathFormat"=>$uploadPath."/{yyyy}{mm}/{time}{rand:6}", /* 上传保存路径,可以自定义保存路径和文件名格式 */
		"videoUrlPrefix"=>"", /* 视频访问路径前缀 */
		"videoMaxSize"=>$maxSize, /* 上传大小限制，单位B，默认100MB */
		"videoAllowFiles"=>$uploadAllow, /* 上传视频格式显示 */

		/* 上传文件配置 */
		"fileActionName"=>"uploadfile", /* controller里,执行上传视频的action名称 */
		"fileFieldName"=>"upfile", /* 提交的文件表单名称 */
		"filePathFormat"=>$uploadPath."/{yyyy}{mm}/{time}{rand:6}", /* 上传保存路径,可以自定义保存路径和文件名格式 */
		"fileUrlPrefix"=>"", /* 文件访问路径前缀 */
		"fileMaxSize"=>$maxSize, /* 上传大小限制，单位B，默认50MB */
		"fileAllowFiles"=>$uploadAllow, /* 上传文件格式显示 */

		/* 列出指定目录下的图片 */
		"imageManagerActionName"=>"listimage", /* 执行图片管理的action名称 */
		"imageManagerListPath"=>$uploadPath."/", /* 指定要列出图片的目录 */
		"imageManagerListSize"=>20, /* 每次列出文件数量 */
		"imageManagerUrlPrefix"=>"", /* 图片访问路径前缀 */
		"imageManagerInsertAlign"=>"none", /* 插入的图片浮动方式 */
		"imageManagerAllowFiles"=>[".png", ".jpg", ".jpeg", ".gif", ".bmp"], /* 列出的文件类型 */

		/* 列出指定目录下的文件 */
		"fileManagerActionName"=>"listfile", /* 执行文件管理的action名称 */
		"fileManagerListPath"=>$uploadPath."/", /* 指定要列出文件的目录 */
		"fileManagerUrlPrefix"=>"", /* 文件访问路径前缀 */
		"fileManagerListSize"=>20, /* 每次列出文件数量 */
		"fileManagerAllowFiles"=>[
			".png", ".jpg", ".jpeg", ".gif", ".bmp",
			".flv", ".swf", ".mkv", ".avi", ".rm", ".rmvb", ".mpeg", ".mpg",
			".ogg", ".ogv", ".mov", ".wmv", ".mp4", ".webm", ".mp3", ".wav", ".mid",
			".rar", ".zip", ".tar", ".gz", ".7z", ".bz2", ".cab", ".iso",
			".doc", ".docx", ".xls", ".xlsx", ".ppt", ".pptx", ".pdf", ".txt", ".md", ".xml"
		] /* 列出的文件类型 */
	);
	return $data;
}
function get_upload_max_filesize_byte($dec=0){
    $max_size=ini_get('upload_max_filesize');
    preg_match('/(^[0-9\.]+)(\w+)/',$max_size,$info);
    $size=$info[1];
    $suffix=strtoupper($info[2]);
    $a = array_flip(array("B", "KB", "MB", "GB", "TB", "PB"));
    $b = array_flip(array("B", "K", "M", "G", "T", "P"));
    $pos = $a[$suffix] && $a[$suffix] !== 0 ? $a[$suffix] : $b[$suffix];
    return round($size * pow(1024,$pos),$dec);
}

$CONFIG=getUEConfig();

$action = $_GET['action'];

switch ($action) {
    case 'config':
        $result =  json_encode($CONFIG);
        break;

    /* 上传图片 */
    case 'uploadimage':
    /* 上传涂鸦 */
    case 'uploadscrawl':
    /* 上传视频 */
    case 'uploadvideo':
    /* 上传文件 */
    case 'uploadfile':
        $result = include("action_upload.php");
        break;

    /* 列出图片 */
    case 'listimage':
        $result = include("action_list.php");
        break;
    /* 列出文件 */
    case 'listfile':
        $result = include("action_list.php");
        break;

    /* 抓取远程文件 */
    case 'catchimage':
        $result = include("action_crawler.php");
        break;

    default:
        $result = json_encode(array(
            'state'=> '请求地址出错'
        ));
        break;
}

/* 输出结果 */
if (isset($_GET["callback"])) {
    if (preg_match("/^[\w_]+$/", $_GET["callback"])) {
        echo htmlspecialchars($_GET["callback"]) . '(' . $result . ')';
    } else {
        echo json_encode(array(
            'state'=> 'callback参数不合法'
        ));
    }
} else {
    echo $result;
}