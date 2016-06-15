<?php
define('WEIXIN_TOKEN', 'kanu');
define('THINK_PATH', '../ThinkPHP/');
define('APP_NAME', 'Canyin');
define('APP_PATH', './Canyin/');
define('APP_DEBUG', true);
define('DOC_ROOT', dirname(__FILE__));
define('BIND_MODULE', 'Mobile'); // 绑定Mobile模块到当前入口文件
define('BIND_CONTROLLER','Api'); // 绑定Api控制器到当前入口文件
!isset($_GET) || $_GET['ulogin']='';
$keys=array_keys($_GET);
$action=$keys[0];
define('BIND_ACTION',$action); // 绑定Api控制器到当前入口文件
require THINK_PATH . 'ThinkPHP.php';
