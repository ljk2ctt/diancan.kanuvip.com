<?php
namespace Common\Model;
use Think\Model;

class WeixinpayModel  extends Model{
    protected function _after_find(&$result, $options) {
        if(!empty($result))
        {
            if(!realpath('.'.$result['cert_pem']) || !realpath('.'.$result['key_pem']))
            {
                //下载证书到本地
                $cert_pem   = curl_get_contents(C('KANU_DOMAIN').substr($result['cert_pem'],1));
                $key_pem    = curl_get_contents(C('KANU_DOMAIN').substr($result['key_pem'],1));
                $cert_dir='.'.dirname($result['cert_pem']);                
                $key_dir='.'.dirname($result['cert_pem']);                
                if(!file_exists($cert_dir)){mkdir($cert_dir,0777,true);}
                if(!file_exists($key_dir)){mkdir($key_dir,0777,true);}
                file_put_contents('.'.$result['cert_pem'], $cert_pem);
                file_put_contents('.'.$result['key_pem'], $key_pem);
            }
            $result['cert_pem']=realpath('.'.$result['cert_pem']);
            $result['key_pem']=realpath('.'.$result['key_pem']);
        }
    }
    protected function _after_select(&$resultSet, $options) {
        foreach ($resultSet as &$result) {
            $this->_after_find($result, $options);
        }
    }
}
