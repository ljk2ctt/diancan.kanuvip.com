<?php
define('WEIXIN_TOKEN', 'kanu');
/**
 * 微信接入验证
 * 在入口进行验证而不是放到框架里验证，主要是解决验证URL超时的问题
 */
if (!empty($_GET ['echostr']) && !empty($_GET ["signature"]) && !empty($_GET ["nonce"])) {
    $signature = $_GET ["signature"];
    $timestamp = $_GET ["timestamp"];
    $nonce = $_GET ["nonce"];

    $tmpArr = array(
        WEIXIN_TOKEN,
        $timestamp,
        $nonce
    );
    sort($tmpArr, SORT_STRING);
    $tmpStr = sha1(implode($tmpArr));

    if ($tmpStr == $signature) {
        echo $_GET ["echostr"];
    }
    exit;
}
define('THINK_PATH', '../ThinkPHP/');
define('APP_NAME', 'Canyin');
define('APP_PATH', './Canyin/');
define('APP_DEBUG', true);
define('DOC_ROOT', dirname(__FILE__));
require THINK_PATH . 'ThinkPHP.php';
