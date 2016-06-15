<?php
namespace Common\Model;
use Think\Model;

class GoodsModel  extends Model{
    protected function _after_find(&$result, $options) {
        if(!empty($result))
        {
            $cinfo  =   D('GoodsCate')->find($result['cid']);
            $result['cinfo']=$cinfo;
        }
    }
    protected function _after_select(&$resultSet, $options) {
        foreach ($resultSet as &$result) {
            $this->_after_find($result, $options);
        }
    }
}
