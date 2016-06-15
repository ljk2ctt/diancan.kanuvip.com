<?php
namespace Mobile\Controller;
class OrderController extends CommonController {
    public function index() {
        $status = I('status');
        if(!empty($status))
        {
            if($status==1)
            {
                $map['status']=1;
            }
            else
            {
                $map['status']=array('gt',1);
            }
            $this->assign('status',$status);
        }
        $Pay  = D('Pay');
        $map['wm_id']   = session('wm_id');
        $lists=$Pay->where($map)->order('addtime desc')->select();
        $this->assign('lists',$lists);
        $this->display();
    }
    
    public function pay()
    {
        $order_sn   =   I('order_sn');
        $Pay    =   D('Pay');
        $pay_mode   =   $Pay->where(array('order_sn'=>$order_sn))->getField('pay_mode');
        if($pay_mode==2)
        {
            redirect(U('Wxpay/index',array('order_sn'=>$order_sn)));
            return;
        }
        elseif($pay_mode==3)
        {
            redirect(U('Knpay/index',array('order_sn'=>$order_sn)));
            return;
        }
    }
    //用户暂不能取消
//    public function cancel()
//    {
//        $order_sn   = I('order_sn');
//        if(empty($order_sn))
//        {
//            $this->error('参数错误');
//        }
//        $Pay    = D('Pay');
//        $map['order_sn']=$order_sn;
//        if(false===$info=$Pay->where($map)->find())
//        {
//            $this->error($Pay->getError());
//        }
//        //已失效订单不能取消
//        if($info['status']>2)
//        {
//            $this->error('该订单状态不能取消');
//        }
//        $Pay->startTrans();
//        if(false===$Pay->where($info)->setField('status',3))
//        {
//            $Pay->rollback();
//            $this->error($Pay->getError());
//        }
//        $Pay->commit();
//    }

}
