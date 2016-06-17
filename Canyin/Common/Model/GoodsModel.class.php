<?php
namespace Common\Model;
use Think\Model;

class GoodsModel  extends Model{
    protected function _after_find(&$result, $options) {
        if(!empty($result))
        {
            $cinfo  =   D('GoodsCate')->find($result['cid']);
            $result['cinfo']=$cinfo;
            //查询出在时间范围内正在促销的特色菜id数组 
            $Tsc    =   M('Tsc');
            $tmap['promote_price']=array('gt',0);
            $tmap['promote_time_start']=array('lt',strtotime('1970-01-01'.date('H:i:s')));
            $tmap['promote_date_start']=array('elt',strtotime(date('Y-m-d')));
            $tmap['promote_time_end']=array('gt',strtotime('1970-01-01'.date('H:i:s')));
            $tmap['promote_date_end']=array('egt',strtotime(date('Y-m-d')));
            $promotes=$Tsc->where($tmap)->getField('goods_id,promote_price',true);
            if(!empty($promotes) && in_array($result['id'],  array_keys($promotes)))
            {
                $result['promote_price'] = $promotes[$result['id']];
            }
        }
    }
    protected function _after_select(&$resultSet, $options) {
        foreach ($resultSet as &$result) {
            $this->_after_find($result, $options);
        }
    }
}
