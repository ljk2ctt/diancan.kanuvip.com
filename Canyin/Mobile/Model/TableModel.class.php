<?php
namespace Mobile\Model;
use Think\Model;

class TableModel  extends Model{
    protected function _after_find(&$result, $options) {
        if(!empty($result))
        {
            $sinfo  =   D('Store')->find($result['sid']);
            $result['sinfo']=$sinfo;
            $result['cname']=M('TableCate')->getFieldById($result['cid'],'name');
            if($result['status']==1)
            {
                //如果是已开台 查询是不是预定
                $reserved=M('Reserved')->where(array('table_id'=>$result['id'],'status'=>2))->find();
                if($reserved)
                {
                    $result['status']=4;
                }
                
            }
        }
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
