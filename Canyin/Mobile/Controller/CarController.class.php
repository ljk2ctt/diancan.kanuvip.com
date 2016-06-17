<?php

namespace Mobile\Controller;

class CarController extends CommonController {
    public function index() {
        $table_id = I('table_id', 0, 'intval');
        $Car = D('Car');
        $map['wm_id'] = session('wm_id');
        if (empty($table_id)) {
            $table_ids=$Car->where($map)->group('table_id')->getField('table_id',true);  
            if(count($table_ids) > 1)
            {
                $tables =   M('Table')->where(array('id'=>array('in',$table_ids)))->getField('id,name',true);
                //请选择餐桌
                $this->assign('tables',$tables);
                $this->display();
                return;
            }
            elseif(count($table_ids)==1)
            {
                $table_id=$table_ids[0];
            }
            else
            {
                $rurl=I('server.HTTP_REFERER');
                $purl=parse_url($rurl);
                parse_str($purl['query']);
                if(empty($table_id))
                {
                    if($tpos=  strpos($rurl, 'table_id/'))
                    {
                        $tableendstr=  substr($rurl, $tpos);
                        $xpos=  strpos($tableendstr, '/');
                        $dpos=  strpos($tableendstr, '.');
                        if($xpos && $dpos)
                        {
                            $pos=min($xpos,$dpos);
                        }else
                        {
                            ($pos=$xpos) || ($pos=$dpos);
                        }
                        $table_id=substr($tableendstr, 9,9-$pos);
                    }
                }
//                dump($table_id);
//                $this->error('您未点菜',U('Order/index'));
            }
        }     
        $this->assign('table_id', $table_id);        
        $map['table_id'] = $table_id;
        $list = $Car->where($map)->select();        
        
        $this->assign('list', $list);
        $pay_mode[1]=C('PAY_MODE.1');
        $store_id   =   M('Table')->getFieldById($table_id,'sid');
        $shop_id    =   M('Store')->getFieldById($store_id,'shop_id');
        //如果配置的微信支付
        if(M('Weixinpay')->where(array('shop_id'=>$shop_id))->find())
        {
            $pay_mode[2]=C('PAY_MODE.2');
        }
        //如果配置的储值卡
        if(M('Weixinpay')->where(array('shop_id'=>$shop_id,'type'=>1))->find())
        {
            $pay_mode[3]=C('PAY_MODE.3');
        }        
        $this->assign('pay_mode',$pay_mode);
        $this->display();
    }

    /**
     * 结算 生成订单
     */
    public function jiesuan() {
        if (IS_AJAX) {
            $table_id = I('table_id', 0, 'intval');
            if (empty($table_id)) {
                $this->error('参数错误');
            }
            $this->assign('table_id', $table_id);

            $pay_mode = I('pay_mode', 0, 'intval');
            if (empty($pay_mode)) {
                $this->error('请选择支付方式');
            }
            if(!in_array($pay_mode,array_keys(C('PAY_MODE'))))
            {                
                $this->error('未开发的支付方式');
            }
            $Car = D('Car');
            $map['wm_id'] = session('wm_id');
            $map['table_id'] = $table_id;
            
            $lists = $Car->where($map)->select();
            if (empty($lists)) {
                $this->error('购物车空空如也');
            }
            $Order = D('Order');
            $omap['addtime'] = array('gt', strtotime(date('Y-m-d')));
            $order_sn = $Order->where($omap)->order('order_sn desc')->getField('order_sn');
            if (empty($order_sn)) {
                $order_sn = date('Ymd') . '00001';
            } else {
                //订单号自加
                ++$order_sn;
            }
            $nlists = array();
            $total=0;
            $store_id   =   M('Table')->getFieldById($table_id,'sid');
            //查询出当前门店正在促销的特色菜id数组 在时间范围内
            $Tsc    =   M('Tsc');
            $tmap['sid']=$store_id;
            $tmap['promote_price']=array('gt',0);
            $tmap['_string']="promote_time_start+promote_date_start <".NOW_TIME.' and promote_time_end+promote_date_end >'.NOW_TIME;
            $promotes=$Tsc->where($tmap)->getField('goods_id,promote_price',true);
            foreach ($lists as $list) {
                $list['order_sn'] = $order_sn;
                $list['addtime'] = NOW_TIME;
                if(!empty($promotes) && in_array($list['goods_id'],  array_keys($promotes)))
                {
                    $list['price'] = $promotes[$list['goods_id']];
                }
                else
                {
                    $list['price'] = $list['ginfo']['price'];
                }
                unset($list['id']);
                $nlists[] = $list;                
                $total+=$list['price']*$list['num'];
            }
            unset($lists);
            $Order->startTrans();
            if (false === $Order->addAll($nlists)) {
                $Order->rollback();
                $this->error($Order->getError());
            }
            if (false === $Car->where($map)->delete()) {
                $Order->rollback();
                $this->error($Car->getError());
            }
            $Table = D('Table');
            //餐桌设置已下单
            if (false === $Table->where(array('id' => $table_id))->setField('status', 2)) {
                $Order->rollback();
                $this->error($Table->getError());
            }
            $Pay    = D('Pay') ;
            //写入数据到支付表
            $paydata['order_sn']=$order_sn;
            $paydata['table_id']=$table_id;
            $paydata['wm_id']=  session('wm_id');
            //暂时实际支付金额同总金额
            $paydata['total']=$paydata['true_pay']=$total;
            $paydata['pay_mode']=$pay_mode;
            $paydata['status']=1;
            $paydata['addtime']=NOW_TIME;
            if(false===$Pay->add($paydata))
            {
                $Order->rollback();
                $this->error($Pay->getError());                
            }            
            $Order->commit();
            $this->success(array('paymode'=>$pay_mode,'order_sn'=>$order_sn));
            return;
        }
        else
        {
            $this->error('非法访问');
        }
    }
    
    public function getcarcount()
    {
     
        $table_id=I('table_id');
        $this->success(M('Car')->where(array('table_id'=>$table_id,'wm_id'=>  session('wm_id')))->sum('num'));
    }
}
