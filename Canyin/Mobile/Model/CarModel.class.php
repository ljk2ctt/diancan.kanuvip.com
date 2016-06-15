<?php
namespace Mobile\Model;
use Think\Model;

class CarModel  extends Model{
    protected function _after_find(&$result, $options) {
        if(!empty($result))
        {
            $ginfo  =   D('Goods')->find($result['goods_id']);
            $result['ginfo']=$ginfo;
        }
    }
    protected function _after_select(&$resultSet, $options) {
        parent::_after_select($resultSet, $options);
        foreach ($resultSet as &$result) {
            $this->_after_find($result, $options);
        }
    }
}
