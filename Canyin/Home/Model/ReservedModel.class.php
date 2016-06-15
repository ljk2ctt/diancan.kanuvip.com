<?php
namespace Home\Model;
use Think\Model;

class ReservedModel  extends Model{
    protected function _after_find(&$result, $options) {
        $res=curl_get_contents(C('KANU_API').'getMemberInfoByWmId.html?wm_id='.session('wm_id'));
        $json   = json_decode($res, true);
        if(is_null($json))
        {
            $this->error='卡努api错误';
            return false;
        }
        $result['minfo']=$json;
    }
    protected function _after_select(&$resultSet, $options) {
        foreach($resultSet as &$result)
        {
            if(false===$this->_after_find($result, $options))
            {
                return false;
            }
        }
    }
}
