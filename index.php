<?php
// +----------------------------------------------------------------------
// | RPCMS
// +----------------------------------------------------------------------
// | Copyright (c) 2019 http://www.rpcms.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( https://www.rpcms.cn/html/license.html )
// +----------------------------------------------------------------------
// | Author: ralap <www.rpcms.cn>
// +----------------------------------------------------------------------
error_reporting(0);
date_default_timezone_set('PRC');
defined('CMSPATH') or define('CMSPATH', dirname(__FILE__));
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
$App=new \rp\App();
$App->run();
