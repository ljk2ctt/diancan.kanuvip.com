<?php

namespace Lib;

/**
 * Kanu api
 *
 * @author Administrator
 */
class Kanu {
    
    const API_URL    =   'http://www.kanuvip.com/Home/Cardapi/';
    const getAccessToken=   'getAccessToken.html?';
    const getShopCardLists='getShopCardLists.html?';  
    const getMemberCardLists='getMemberCardLists.html?';
    const useCard='useCard.html?';
    const intoCard='intoCard.html?';
    const receiveCard='receiveCard.html?';

    private $error='';
    private $access_token='';
    private $shop_id=0;

    public function __construct($shop_id) {
        $this->shop_id = $shop_id;
    }
    public function getCardLists($cid=0)
    {
        if(empty($this->access_token))
        {
            $this->getAccessToken();
        }
        $data['access_token']=  $this->access_token;
        $data['cid']=  $cid;
        $result = $this->http_post(self::API_URL . self::getShopCardLists, $data);
        if ($result) {
            $json = json_decode($result, true);
            if (!$json || $json['status']==0) {
                $this->error = $json['info'];
                return false;
            }
            return $this->addkeytoarray($json['info']);
        }
    }
    public function getMemberCardLists($wm_id,$key='id')
    {
        if(empty($this->access_token))
        {
            $this->getAccessToken();
        }
        $data['access_token']=  $this->access_token;
        $data['wm_id']=  $wm_id;
        $result = $this->http_post(self::API_URL . self::getMemberCardLists, $data);
        if ($result) {
            $json = json_decode($result, true);
            if (!$json || $json['status']==0) {
                $this->error = $json['info'];
                return false;
            }
            return $this->addkeytoarray($json['info'],$key);
        }
    }
    /**
     * 自动领取卡片
     * card_id wx_id
     */
    public function receiveCard($card_id,$wm_id)
    {
        if(empty($this->access_token))
        {
            $this->getAccessToken();
        }
        $data['access_token']=  $this->access_token;
        $data['card_id']    =   $card_id;
        $data['wm_id']      =   $wm_id;
        $result = $this->http_post(self::API_URL . self::receiveCard, $data);
        if ($result) {
            $json = json_decode($result, true);
            if (!$json || $json['status']==0) {
                $this->error = $json['info'];
                return false;
            }
            return $this->addkeytoarray($json['info']);
        }
    }
    /**
     * 卡片充值
     */
    public function intoCard($card_id,$wm_id,$value)
    {
        if(empty($this->access_token))
        {
            $this->getAccessToken();
        }
        $data['access_token']=  $this->access_token;
        $data['card_id']    =   $card_id;
        $data['wm_id']      =   $wm_id;
        $data['value']      =   $value;
        $result = $this->http_post(self::API_URL . self::intoCard, $data);
        if ($result) {
            $json = json_decode($result, true);
            if (!$json || $json['status']==0) {
                $this->error = $json['info'];
                return false;
            }
            return $json['info'];
        }
    }
    /**
     * 
     * 使用卡片
     */
    public function useCard($card_id,$wm_id,$value)
    {
        if(empty($this->access_token))
        {
            $this->getAccessToken();
        }
        $data['access_token']=  $this->access_token;
        $data['card_id']    =   $card_id;
        $data['wm_id']      =   $wm_id;
        $data['value']      =   $value;
        $result = $this->http_post(self::API_URL . self::useCard, $data);
        if ($result) {
            $json = json_decode($result, true);
            if (!$json || $json['status']==0) {
                $this->error = $json['info'];
                return false;
            }
            return $json['info'];
        }
    }
    private function getAccessToken()
    {
        if(!empty($this->access_token))
        {
            return;
        }
        $data['shop_id']    = $this->shop_id;
        $result = $this->http_post(self::API_URL . self::getAccessToken , $data);
        if ($result) {
            $json = json_decode($result, true);
            if (!$json || $json['status']==0) {
                $this->error = $json['info'];
                return false;
            }
            $this->access_token=$json['info'];
            return $this->access_token;
        }
    }
    public function getError()
    {
        return $this->error;
    }
    /**

     * POST 请求
     * @param string $url
     * @param array $param
     * @param boolean $post_file 是否文件上传
     * @return string content
     */

    private function http_post($url, $param, $post_file = false) {
        $oCurl = curl_init();
        if (is_string($param) || $post_file) {
            $strPOST = $param;
        } else {
            $aPOST = array();
            foreach ($param as $key => $val) {
                $aPOST[] = $key . "=" . urlencode($val);
            }
            $strPOST = join("&", $aPOST);
        }
        curl_setopt($oCurl, CURLOPT_URL, $url);
        curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($oCurl, CURLOPT_POST, true);
        curl_setopt($oCurl, CURLOPT_POSTFIELDS, $strPOST);
        $sContent = curl_exec($oCurl);
        $aStatus = curl_getinfo($oCurl);
        curl_close($oCurl);
        if (intval($aStatus["http_code"]) == 200) {
            return $sContent;
        } else {
            return false;
        }
    }
    static function json_encode($arr) {

        if (count($arr) == 0)

            return "[]";

        $parts = array();

        $is_list = false;

        //Find out if the given array is a numerical array

        $keys = array_keys($arr);

        $max_length = count($arr) - 1;

        if (($keys [0] === 0) && ($keys [$max_length] === $max_length )) { //See if the first key is 0 and last key is length - 1

            $is_list = true;

            for ($i = 0; $i < count($keys); $i ++) { //See if each key correspondes to its position

                if ($i != $keys [$i]) { //A key fails at position check.

                    $is_list = false; //It is an associative array.

                    break;

                }

            }

        }

        foreach ($arr as $key => $value) {

            if (is_array($value)) { //Custom handling for arrays

                if ($is_list)

                    $parts [] = self::json_encode($value); /* :RECURSION: */

                else

                    $parts [] = '"' . $key . '":' . self::json_encode($value); /* :RECURSION: */

            } else {

                $str = '';

                if (!$is_list)

                    $str = '"' . $key . '":';

                //Custom handling for multiple data types

                if (!is_string($value) && is_numeric($value) && $value < 2000000000)

                    $str .= $value; //Numbers

                elseif ($value === false)

                    $str .= 'false'; //The booleans

                elseif ($value === true)

                    $str .= 'true';

                else

                    $str .= '"' . addslashes($value) . '"'; //All other things

                    

// :TODO: Is there any more datatype we should be in the lookout for? (Object?)

                $parts [] = $str;

            }

        }

        $json = implode(',', $parts);

        if ($is_list)

            return '[' . $json . ']'; //Return numerical JSON

        return '{' . $json . '}'; //Return associative JSON

    }
    /**
     * array(0=>array('id'=>1,name='hello'))
     * =>
     * array(1=>array('id'=>1,name='hello'))
     * 将二维数组的id放入key
     * @param type $data
     */
    private function addkeytoarray($data,$key='id')
    {
        $ndata=array();
        foreach ($data as $d)
        {
            $ndata[$d[$key]]=$d;
        }
        return $ndata;
    }
}
