<?php

namespace Mobile\Controller;

class KnpayController extends CommonController {
    public function _initialize(){
//       redirect(U('Wechatpaytest/index',I()));
       layout(false);
   }
 	
    public function index() {
        $order_sn = I('order_sn');	
        if(empty($order_sn))
        {
            $this->error('非法操作');
        }
        $map['order_sn']=$order_sn;
        $Pay    =   D('Pay');        
        $info = $Pay->lock(true)->where($map)->find(); //订单详情 
        $Pay->where($map)->setField('order_time',NOW_TIME);
        if(1!=$info['status'])
        {
            $this->error('该订单不是待付款状态');
        }
        if(3!=$info['pay_mode'])
        {
            $this->error('该订单不是该支付方式');
        }        
        if(IS_AJAX)
        {
            $table_id=$info['table_id'];
            $store_id   = M('Table')->getFieldById($table_id,'sid');
            $shop_id    = M('Store')->getFieldById($store_id,'shop_id');
            $card_id    = M('Card')->getFieldByShipId($shop_id,'card_id');
            $Kanu   =   new \Lib\Kanu($shop_id);
            if(false===$Kanu->useCard($card_id,  session('wm_id'),$info['true_pay']))
            {
                $this->error($Kanu->getError());
            }
            //修改订单状态
            $Pay->startTrans();
            if(false===$Pay->where($map)->setField(array('status'=>2,'syy_id'=>-1)))
            {
                $Pay->rollback();
                $this->error($Pay->getError());
            }                                
            $Pay->commit(); 
            $this->success();
            return;
        }
        $this->assign('info',$info);
        $this->display('index'); //渲染支付页面
    }
}
