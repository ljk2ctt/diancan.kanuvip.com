<?php


function getencrypt($pwd, $encrypt_key) {//加密

    if (empty($pwd) or empty($encrypt_key)) {

        return false;

    }

    return md5(md5($pwd) . $encrypt_key);

}



function date2($time, $f = 'Y-m-d H:i:s') {

    if (empty($time))

        return '';

    return date($f, $time);

}
function curl_get_contents($url) {

    $curlHandle = curl_init();
    curl_setopt($curlHandle, CURLOPT_URL, $url);
    curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curlHandle, CURLOPT_TIMEOUT, 5);
    curl_setopt($curlHandle, CURLOPT_FOLLOWLOCATION, 1);
    $result = curl_exec($curlHandle);
    curl_close($curlHandle);
    return $result;

}
function curl_post_contents($url, $param, $post_file = false) {
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
function daytoend($time) {

    if (date('His', $time) > 0) {

        return $time;

    }

    return $time + 86399;

}
function checkgtnowtime($datetime)
{
    $datetime= strtotime($datetime);
    return $datetime>NOW_TIME;
}
/**

 * 检测是否微信浏览器

 * @return boolean

 */

function checkwxbrowser() {

    if (strpos(I('server.HTTP_USER_AGENT'), 'MicroMessenger') !== false) {

        return true;

    }

    return false;

}