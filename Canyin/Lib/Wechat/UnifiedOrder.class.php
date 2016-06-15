<?php

namespace Lib\Wechat;

class UnifiedOrder extends Wechatpay {
  
    protected $config=array();
    function __construct($config) {  
        //设置接口链接
        $this->url = "https://api.mch.weixin.qq.com/pay/unifiedorder";
        //设置curl超时时间
        $this->curl_timeout = 30;      
        $this->config=$config;
    }

    /*
     * 本地验证签名
     */

    function checkSign($data) {
        $tmpData = $data;
        unset($tmpData['sign']);
        $sign = $this->getSign($tmpData); //本地签名
        if ($data['sign'] == $sign) {
            return TRUE;
        }
        return FALSE;
    }

    /**
     * 获取prepay_id
     */
    function getPrepayId() {
        $this->postXml();
        $this->result = $this->xmlToArray($this->response);
        $prepay_id = $this->result["prepay_id"];
        return $prepay_id;
    }

}
