<?php
namespace Common\Model;
use Think\Model;

class PayModel  extends Model{
    protected function _after_find(&$result, $options) {
        if(!empty($result) && isset($result['order_sn']))
        {
            $oinfo  =   D('Order')->where(array('order_sn'=>$result['order_sn']))->select();
            $result['oinfo']=$oinfo;
            if($result['syy_id'])
            {
                if($result['syy_id']=='-1')
                {
                    $result['syyinfo']=array('nickname'=>'商户管理员');     
                }
                else
                {
                    $syyinfo=   D('User')->where(array('id'=>$result['syy_id']))->find();
                    $result['syyinfo']=$syyinfo;                
                }
            }
//            if(isset($options['goodsnames']))
//            {
//                foreach($oinfo as $order)
//                {
//                    $result['goods_names'][]=$order['ginfo']['name'];
//                }
//            }
        }
    }
    protected function _after_select(&$resultSet, $options) {
        foreach ($resultSet as &$result) {
            $this->_after_find($result, $options);
        }
    }
    protected function _before_update(&$data, $options) {
        if(isset($data['status']) && $data['status']=='2')
        {
            //更新支付时间
            $data['pay_time']   =   NOW_TIME;
        }
    }
    protected function _after_update($data, $options) {
        if(isset($data['status']) && $data['status']=='2')
        {
            $data=$this->find($data['id']);
            //餐桌设置已支付              
            $where=$options['where'];
            unset($where['status']);
            unset($where['pay_time']);
            $table_id   =   $this->where($where)->getField('table_id');
            M('Table')->where(array('id'=>$table_id))->setField('status',3);
            //打印收银小票
            //查询门店下是否存在菜品打印机
            $tinfo    =   M('Table')->find($data['table_id']);
            $tinfo['cname']=M('TableCate')->getFieldById($tinfo['cid'],'name');
            $Printer    =   M('Printer');
            $map['sid'] =   $tinfo['sid'];
            $map['type']=   1;
            $printers   =   $Printer->where($map)->select();
            foreach($printers as $printer)
            {
                $orderInfo[$printer['id']]='<CB>消费清单</CB><BR>';
                $orderInfo[$printer['id']] .= '名称          单价  数量  金额  <BR>';
                $orderInfo[$printer['id']] .= '--------------------------------<BR>';//32字节
                $orders =   D('Order')->where(array('order_sn'=>$data['order_sn']))->select();  
                $sprintf='';
                foreach ($orders as $order)
                {
                    
                    $sprintf .= sprintf('%-14s%-6s  %-4s%-6s<BR>',  iconv('utf-8', 'gbk', $order['ginfo']['name']),$order['price'],$order['num'],$order['price']*$order['num']);                        
//                        $orderInfo .= '<QR>http://www.dzist.com</QR>'; //把二维码字符串用标签套上即可自动生成二维码                        
                                        
                }
                $orderInfo[$printer['id']] .= iconv('gbk','utf-8',$sprintf);
                $orderInfo[$printer['id']] .= '--------------------------------<BR>';
                $orderInfo[$printer['id']] .= '合计：'.$data['total'].'<BR>';
                if($data['total']>$data['true_pay'])
                {                    
                    $orderInfo[$printer['id']] .= '实付：'.$data['true_pay'].'<BR>';
                    $orderInfo[$printer['id']] .= '优惠：'.($data['total']-$data['true_pay']).'<BR>';
                }
                $orderInfo[$printer['id']] .= '桌号：'.$tinfo['cname'].$tinfo['name'].'<BR>';
                $orderInfo[$printer['id']] .= '下单时间：'.date('Y-m-d H:i:s',$data['addtime']).'<BR>';
                $orderInfo[$printer['id']] .= '付款时间：'.date('Y-m-d H:i:s',NOW_TIME).'<BR>';
                file_put_contents($printer['printer_sn'].'.txt', str_replace('<BR>', "\n", $orderInfo[$printer['id']]));
//                $Feie  = new \Lib\Feie\Feie($printer['printer_sn'], $printer['key']);
//                if(false===$order_index=$Feie->doprint($orderInfo[$printer['id']]))
//                {
//
//                }                
//                unset($Feie);
            }
        }
        if(isset($data['status']) && $data['status']=='5')
        {
            //修改为完成 自动积分            
            $where=$options['where'];
            unset($where['status']);
            $payinfo    = $this->where($where)->find();  
            $shop_id=   M('Store')->where('id='.session('store_id'))->getField('shop_id');
            $map['shop_id']=$shop_id;
            $map['type']=2;
            $myjifencard_id = M('Card')->where($map)->getField('card_id');
            if(!empty($myjifencard_id))
            {                              
                $wm_id  =   $payinfo['wm_id'];
                $Kanu   =   new \Lib\Kanu($shop_id);
                if(false===$res=$Kanu->intoCard($myjifencard_id, $wm_id,$payinfo['true_pay']*100))//1元100分
                {
                    $this->error=$Kanu->getError();
                    return false;
                }                
            }
        }
    }
    protected function _after_insert($data, $options) {   
        $tinfo    =   M('Table')->find($data['table_id']);
        $tinfo['cname']=M('TableCate')->getFieldById($tinfo['cid'],'name');
        //查询门店下是否存在菜品打印机
        $Printer    =   M('Printer');
        $map['sid'] =   $tinfo['sid'];
        $map['type']=   2;
        $printers   =   $Printer->where($map)->select();
        if(!empty($printers))
        {            
            foreach($printers as $printer)
            {
                $orderInfo[$printer['id']]='<CB>配菜单</CB><BR>';
                $orderInfo[$printer['id']] .= '名称　　　　　          数量       <BR>';
                $orderInfo[$printer['id']] .= '--------------------------------<BR>';//32字节
                $orders =   D('Order')->where(array('order_sn'=>$data['order_sn']))->select();   
                foreach ($orders as $order)
                {
                    $cid=  $order['ginfo']['cid'];
                    if(in_array($cid,  explode(',', $printer['cids'])))
                    {
                        $namelen=  strlen($order['ginfo']['name'])/3*2;
                        $bulen=25-$namelen;
                        $orderInfo[$printer['id']] .= sprintf('%s%'.$bulen.'s%s<BR>',$order['ginfo']['name'],' ',$order['num']);                        
//                        $orderInfo .= '<QR>http://www.dzist.com</QR>'; //把二维码字符串用标签套上即可自动生成二维码                        
                    }                    
                }
                $orderInfo[$printer['id']] .= '--------------------------------<BR>';
                $orderInfo[$printer['id']] .= '桌号：'.$tinfo['cname'].$tinfo['name'].'<BR>';
                $orderInfo[$printer['id']] .= '下单时间：'.date('Y-m-d H:i:s',NOW_TIME).'<BR>';
//                file_put_contents($printer['printer_sn'].'.txt', $orderInfo[$printer['id']]);
                $Feie  = new \Lib\Feie\Feie($printer['printer_sn'], $printer['key']);
                if(false===$order_index=$Feie->doprint($orderInfo[$printer['id']]))
                {

                }                
                unset($Feie);
            }
        }
        
    }
//    public function goodsnames()
//    {
//        $this->options['goodsnames']=true;
//        return $this;
//    }
}
