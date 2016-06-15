<?php
namespace Mobile\Model;
use Think\Model;

class ReservedModel  extends Model{
    protected $_validate    =   array(
        array('num','require','请填写预约人数！'), 
        array('reserved_time','require','请填写预约时间！'), 
        array('reserved_time','checkgtnowtime','预约时间过期！',self::MODEL_BOTH,'function'), 
        array('sid','require','请填写预约时间！'), 
    );
    protected $_auto    =   array(
        array('wm_id','session',self::MODEL_INSERT,'function','wm_id') ,
        array('reserved_time','strtotime',self::MODEL_INSERT,'function') ,
        array('addtime',NOW_TIME) ,
    );
    protected function _after_insert($data, $options) {
        //预约成功 模版推送
        curl_get_contents(C('KANU_API').'setTempMessage.html?wm_id='.$data['wm_id'].'&temp_id=OPENTM207221291&args[]=您好，您的用餐预约已成功&args[]='.date('Y-m-d H:i',$data['reserved_time']).'&args[]='.$data['num'].'&args[]=感谢支持');
    }
}
