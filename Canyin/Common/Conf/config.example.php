<?php
return array(
    //'配置项'=>'配置值'
    'TMPL_FILE_DEPR'=>'_',
    'URL_MODEL'=>2,
    'DB_TYPE'   => 'mysql', // 数据库类型
    'DB_HOST'   => 'DB_HOST', // 服务器地址
    'DB_NAME'   => 'DB_NAME', // 数据库名
    'DB_USER'   => 'DB_USER', // 用户名
    'DB_PWD'    => 'DB_PWD', // 密码
    'DB_PORT'   => 3306, // 端口
    'DB_PREFIX' => 'dc_', // 数据库表前缀 
    
    
//    'DB_FIELDS_CACHE'=>true,
    
//    'LOG_RECORD'=>true,
    
    'WEB_DOMAIN'=>'http://diancan.kanuvip.com/',
    'KANU_DOMAIN'=>'http://www.kanuvip.com/',
    'KANU_IMG'=>'http://www.kanuvip.com',
    'KANU_API'=>'http://www.kanuvip.com/Home/Kanuapi/',
    
    //验证码样式
    'VERIFY_CONFIG' => array('fontSize' => 20, 'length' => 4, 'useCurve' => false, 'useNoise' => false, 'fontttf' => '2.ttf', 'codeSet' => '0123456789'),
    
    'PAY_MODE'=>array(1=>'现金',2=>'微信支付',3=>'商户储值卡'),
    
    );