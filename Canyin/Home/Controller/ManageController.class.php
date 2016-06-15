<?php

namespace Home\Controller;

class ManageController extends CommonController {
 public function _initialize() {

        parent::_initialize();

        $menu = array(

            '桌台管理' => array('href' => U('table'), 'icon' => '&#xe063;'),
            '预约管理' => array('href' => U('reserved'), 'icon' => '&#xe063;'),
            '订单管理' => array('href' => U('orders'), 'icon' => '&#xe048;'),
            '订单日汇总'=> array('href' => U('huizongday'), 'icon' => '&#xe031;'),
            '订单月汇总'=> array('href' => U('huizongmonth'), 'icon' => '&#xe028;'),

        );

        $this->assign('menu', $menu);

    }
    public function index() {
        $this->redirect('table');
    }
    public function reserved()
    {
        $Reserved   =   D('Reserved');
        $id =   I('id',0,'intval');
        $table_id=I('table_id',0,'intval');
        $Table=M('Table');
        if(!empty($id)&&!empty($table_id))
        {
            //分配桌号
            $tinfo=$Table->find($table_id);
            if($tinfo['status'] || S('table_member_lock_'.$table_id))
            {
                $this->error('该桌台不是空闲状态');
            }
            $rinfo=$Reserved->find($id);
            if(!empty($rinfo['table_id']))
            {
                $this->error('已经分配过桌号了');
            }
            //绑定餐桌
            S('table_member_lock_'.$table_id,  session('wm_id'));
            $Table->where($tinfo)->setField('status',1);
            $Reserved->where($rinfo)->save(array('table_id'=>$table_id,'status'=>2));
        }
        $map['sid']=  session('store_id');
        if(false===$lists  =   $Reserved->where($map)->select())
        {
            $this->error($Reserved->getError());
        }
        $this->assign('list',$lists);
        $ttables =  $Table->where($map)->select();
        $TableCate=M('TableCate');
        foreach($ttables as $table)
        {
            $table['cname']=$TableCate->getFieldById($table['cid'],'name');
            $tables[$table['id']]=$table;            
        }
        $this->assign('tables',$tables);
        $this->display();
    }
    public function table() {
        $cid = I('cid', 0, 'intval');
        $name = I('name');
        if(!empty($cid))
        {      
            $map['cid']=$cid;
            $this->assign('cid',$cid);
        }
        if(!empty($name))
        {
            $map['name']=array('like',"%$name%");
            $this->assign('name',$name);
        }
        $cate = D('TableCate')->getField('id,name,min_price', true);
        $this->assign('cate', $cate);
        $Table = D('Table');
        $map['sid'] = session('store_id');
        $tables = $Table->where($map)->order('sort')->getQrcode()->select();
        $this->assign('tables', $tables);
        $this->display();
    }
    //餐桌的订单
    public function order()
    {
        $table_id   =   I('table_id',0,'intval');
        if(empty($table_id))
        {
            $this->error('参数错误');
        }
        $this->assign('table_id',$table_id);
        $Table  = D('Table');
        $tinfo  =   $Table->find($table_id);
        if(empty($tinfo))
        {
            $this->error('餐桌不存在');
        }
        if($tinfo['sid']!=  session('store_id'))
        {
            $this->error('不是您所管理的餐桌');
        }
        $this->assign('tstatus',$tinfo['status']);
        $Pay = D('Pay');
        if(IS_AJAX)
        {
            $act=I('act');
            if(!in_array($act,array('cancel','resetstatus','shoukuan')))
            {
                $this->error('参数错误');
            }
            if($act=='cancel')
            {
                $remoney    =   I('money',0,'doubleval');
                $tmpmap['table_id']   =   $table_id;
                if(false===$tmpinfo=$Pay->where($tmpmap)->order('addtime desc')->find())
                {
                    $this->error($Pay->getError());
                }
                //已失效订单不能取消
                if($tmpinfo['status']>2)
                {
                    $this->error('当前订单状态不允许取消');
                }
                if($tmpinfo['pay_mode']==1 && $remoney>0)
                {
                    $this->error('现金付款不支持退款');
                }                
                $tmpmap['id']=$tmpinfo['id'];
                $Pay->startTrans();
                if(false===$Pay->where($tmpmap)->setField('status',4))
                {
                    $Pay->rollback();
                    $this->error($Pay->getError());
                }
                if($remoney>0)
                {
                    if($tmpinfo['status']!=2)
                    {
                        $this->error('当前订单不是已付款状态');
                    }
                    if($remoney>$tmpinfo['true_pay'])
                    {
                        $this->error('退款金额大于付款金额');
                    }
                    //查询出商户id shop_id
                    $store_id   =   $tmpinfo['oinfo'][0]['ginfo']['sid'];
                    $shop_id    =   M('Store')->getFieldById($store_id,'shop_id');
                    if($tmpinfo['pay_mode']==2)
                    {
                        $wxmap['shop_id']=$shop_id;
                        $wxpayconfig=D('Weixinpay')->where($wxmap)->find();                     
                        unset($wxmap);           
                        //商户退款单号
                        $refund_no = $tmpinfo['order_sn'] . date('His') . rand(100, 999);    
                        //写入退款单号
                        if(false===$Pay->where($tmpmap)->setField('refund_no',$refund_no))
                        {
                            $Pay->rollback();
                            $this->error($Pay->getError());
                        }
                        $Refund = new \Lib\Wechat\Refund($wxpayconfig);
                        $Refund->setParameter("out_trade_no", $tmpinfo['order_sn'].'_'.$tmpinfo['order_time']); //商户订单号
                        $Refund->setParameter("out_refund_no", $refund_no); //商户退款单号
                        $Refund->setParameter("total_fee", $tmpinfo['true_pay'] * 100); //总金额
                        $Refund->setParameter("refund_fee", $remoney * 100); //退款金额
                        $Refund->setParameter("op_user_id", $wxpayconfig['mchid']); //操作员 
                        //调用结果
                        if (false == $refundResult = $Refund->getResult()) {
                            $Pay->rollback();
                            $this->error($Refund->error);
                        }                    
                        //商户根据实际情况设置相应的处理流程,此处仅作举例
                        if ($refundResult["return_code"] == "FAIL") {
                            $Pay->rollback();
                            $this->error("通信出错：" . $refundResult['return_msg']);
                        } elseif ($refundResult['result_code'] == 'FAIL') {
                            $Pay->rollback();
                            $this->error('退款申请出错：' . $refundResult['err_code_des'] . '(' . $refundResult['err_code'] . ')');
                        }
                    }
                    elseif($tmpinfo['pay_mode']==3)
                    {
                        $Kanu   = new \Lib\Kanu($shop_id);
                        $cardmap['shop_id']=$shop_id;
                        $cardmap['type']=1;
                        $card_id    =   M('Card')->where($cardmap)->getField('card_id');
                        $wm_id  =   $tmpinfo['wm_id'];
                        if(false===$Kanu->intoCard($card_id, $wm_id, $remoney))
                        {
                            $this->error($Kanu->getError());
                        }
                    }
                }                
                unset($tmpinfo);
                $Pay->commit();
                $this->success();
            }
            elseif($act=='shoukuan')
            {
                $tmpmap['table_id']   =   $table_id;
                if(false===$tmpinfo=$Pay->where($tmpmap)->order('addtime desc')->find())
                {
                    $this->error($Pay->getError());
                }
                if($tmpinfo['status']!=1)
                {
                    $this->error('当前订单不是待付款状态');
                }
                if($tmpinfo['pay_mode']!=1)
                {
                    $this->error('当前订单不是现金付款');
                }
                $tmpmap['id']=$tmpinfo['id'];
                $Pay->startTrans();
                if(false===$Pay->where($tmpmap)->setField(array('status'=>2,'syy_id'=>session('syy_id'))))
                {
                    $Pay->rollback();
                    $this->error($Pay->getError());
                }                                
                $Pay->commit();                
                $this->success();
            }
            elseif($act=='resetstatus')
            {                
                if($tinfo['status']==0)
                {
                    $this->error('餐桌已是空闲状态');
                }
                $tmpmap['table_id']   =   $table_id;
                if(false===$tmpinfo=$Pay->where($tmpmap)->order('addtime desc')->find())
                {
                    $this->error($Pay->getError());
                }
                if(!empty($tmpinfo) && $tmpinfo['status']<2)
                {
                    $this->error('餐桌存在未处理订单');
                }                
                $tmpmap['id']=$tmpinfo['id'];
                //如果不是取消的订单 设置成已完成
                if(!empty($tmpinfo) && $tmpinfo['status']==2)
                {                    
                    $Pay->startTrans();
                    if(false===$Pay->where($tmpmap)->setField('status',5))
                    {
                        $Pay->rollback();
                        $this->error($Pay->getError());
                    }                                
                    $Pay->commit();         
                }
                //餐桌设置成空闲
                $Table->where(array('id'=>$table_id))->setField('status',0);
                S('table_member_lock_'.$table_id,null);
                //如果是预约 修改预约表
                $Reserved    =   M('Reserved');                
                if($rinfo=$Reserved->where(array('table_id'=>$table_id))->find())
                {
                    $Reserved->where($rinfo)->setField(array('table_id'=>0,'status'=>1));
                }
                $this->success();
            }
            else
            {
                $this->error('未开发的功能:'.$act);
            }
        }
        if($tinfo['status']>1)
        {
            $pmap['table_id']   =   $table_id;
//          $pmap['status']     =   array('lt',3);//小于3 有效的订单
            //加个order 查询最后一条 防止意外情况出现一个餐桌2个订单的bug
            if(false===$info=$Pay->where($pmap)->order('addtime desc')->find())
            {
                $this->error($Pay->getError());
            }
            $this->assign('info',$info);
        }
        
        
        $this->display();        
    }
    public function orders()
    {
        $order_sn   =   I('order_sn');
        $status   =   I('status');
        $syy_id   =   I('syy_id');
        $st=    I('st',0,'strtotime');
        $et=    I('et',0,'strtotime');
        if(!empty($et))
        {
            $et=  daytoend($et);
        }
        if(!empty($st) && empty($et))
        {
            $map['addtime']=array('gt',$st);
            $this->assign('st',$st);
        }
        elseif(empty($st) && !empty($et))
        {
            $map['addtime']=array('lt',$et);
            $this->assign('et',$et);
        }
        elseif(!empty($st) && !empty($et))
        {            
            $map['addtime']=array('between',array($st,$et));
            $this->assign('st',$st);
            $this->assign('et',$et);
        }
       
        if(!empty($order_sn))
        {
            $map['order_sn']=array('like',"%$order_sn%");
            $this->assign('order_sn',$order_sn);                    
        }
        if(!empty($syy_id))
        {
            $map['syy_id']=$syy_id;
            $this->assign('syy_id',$syy_id);   
            //查询收银员 默认已完成状态订单
            $status=5;
        }
        if(!empty($status))
        {
            $map['status']=$status;
            $this->assign('status',$status);                    
        }      
        $Pay    = D('Pay');
        (!$table_ids  =   M('Table')->where(array('sid'=>  session('store_id')))->getField('id',true)) && $table_ids='-1';        
        $map['table_id']=array('in',$table_ids);
        $count  =   $Pay->where($map)->count();
        $page   = new \Think\Page($count);
        if(false===$list=$Pay->where($map)->order('addtime desc')->limit($page->firstRow.','.$page->listRows)->select())
        {
            $this->error($Pay->getError());
        }
        $syys   =   D('User')->where(array('store_id'=>  session('store_id')))->getField('id,nickname');
        $syys['-1']='商户管理员';
        $this->assign('syys',$syys);
        $this->assign('list',$list);
        $this->assign('page',$page->show());
        $this->display();
    }
    public function order_detail()
    {
        $order_sn   =   I('id');
        if(empty($order_sn))
        {
            $this->error('参数错误');
        }        
        $tables =M('Table')->where(array('sid'=>  session('store_id')))->getField('id,name',true);
        (!$table_ids  = array_keys($tables)) && $table_ids='-1';  
        $this->assign('tables',$tables);
        $map['table_id']=array('in',$table_ids);
        $map['order_sn']=$order_sn;
        $Pay    = D('Pay');
        if(false===$info=$Pay->where($map)->order('addtime desc')->find())
        {
            $this->error($Pay->getError());
        }
        if(empty($info))
        {
            $this->error('未查找到订单');
        }
        $this->assign('info',$info);
        $this->display();
    }
    /*
     * 日汇总表
     */
    public function huizongday()
    {
        $st=    I('st',0,'strtotime');
        $et=    I('et',0,'strtotime');
        $syy_id   =   I('syy_id');
        if(!empty($et))
        {
            $et=  daytoend($et);
        }
        if(!empty($st) && empty($et))
        {
            $map['addtime']=array('gt',$st);
            $this->assign('st',$st);
        }
        elseif(empty($st) && !empty($et))
        {
            $map['addtime']=array('lt',$et);
            $this->assign('et',$et);
        }
        elseif(!empty($st) && !empty($et))
        {            
            $map['addtime']=array('between',array($st,$et));
            $this->assign('st',$st);
            $this->assign('et',$et);
        }
        if(!empty($syy_id))
        {
            $map['syy_id']=$syy_id;
            $this->assign('syy_id',$syy_id);   
        }
        $tables =M('Table')->where(array('sid'=>  session('store_id')))->getField('id,name',true);
        (!$table_ids  = array_keys($tables)) && $table_ids='-1';  
        $map['table_id']=array('in',$table_ids);
        //已经完成的付款
        $map['status']=5;
        $Pay    = D('Pay');
        if(false===$infos=$Pay->where($map)->order('addtime desc')->group('days')->field('sum(true_pay) as true_pays,sum(if(pay_mode=1,true_pay,0)) as true_pays1,sum(if(pay_mode=2,true_pay,0)) as true_pays2,count(id) as orders,sum(if(pay_mode=1,1,0)) as orders1,sum(if(pay_mode=2,1,0)) as orders2,FROM_UNIXTIME(`pay_time`,"%Y-%m-%d") as days')->select())
        {
            $this->error($Pay->getError());
        }
        
        $syys   =   D('User')->where(array('store_id'=>  session('store_id')))->getField('id,nickname');
        $syys['-1']='商户管理员';
        $this->assign('syys',$syys);
        $this->assign('infos',$infos);
        $this->display();
    }
    /**
     * 月汇总表
     */
    public function huizongmonth()
    {
        $st=    I('st',0,'strtotime');
        $et=    I('et',0,'strtotime');
        $syy_id   =   I('syy_id');
        if(!empty($et))
        {
            $et=strtotime(date('Y-m-01',$et).'+1 month -1 day');
            $et=  daytoend($et);
        }
        if(!empty($st) && empty($et))
        {
            $st=strtotime(date('Y-m-01',$st));
            $map['addtime']=array('gt',$st);
            $this->assign('st',$st);
        }
        elseif(empty($st) && !empty($et))
        {
            $map['addtime']=array('lt',$et);
            $this->assign('et',$et);
        }
        elseif(!empty($st) && !empty($et))
        {            
            $map['addtime']=array('between',array($st,$et));
            $this->assign('st',$st);
            $this->assign('et',$et);
        }
        if(!empty($syy_id))
        {
            $map['syy_id']=$syy_id;
            $this->assign('syy_id',$syy_id);   
        }
        $tables =M('Table')->where(array('sid'=>  session('store_id')))->getField('id,name',true);
        (!$table_ids  = array_keys($tables)) && $table_ids='-1';  
        $map['table_id']=array('in',$table_ids);
        //已经完成的付款
        $map['status']=5;
        $Pay    = D('Pay');
        if(false===$infos=$Pay->where($map)->order('addtime desc')->group('months')->field('sum(true_pay) as true_pays,sum(if(pay_mode=1,true_pay,0)) as true_pays1,sum(if(pay_mode=2,true_pay,0)) as true_pays2,count(id) as orders,sum(if(pay_mode=1,1,0)) as orders1,sum(if(pay_mode=2,1,0)) as orders2,FROM_UNIXTIME(`pay_time`,"%Y-%m") as months')->select())
        {
            $this->error($Pay->getError());
        }
        $syys   =   D('User')->where(array('store_id'=>  session('store_id')))->getField('id,nickname');
        $syys['-1']='商户管理员';
        $this->assign('syys',$syys);
        $this->assign('infos',$infos);
        $this->display();
    }
}
