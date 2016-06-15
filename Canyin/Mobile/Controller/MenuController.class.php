<?php
namespace Mobile\Controller;
class MenuController extends CommonController {   
    public function index(){
        $table_id   =   I('table_id',0,'intval');
        $cid   =   I('cid',0,'intval');
        if(empty($table_id))
        {
            $this->error('参数错误');
        }
        $this->assign('table_id',$table_id);
        $Table  = D('Table');
        if(!$tinfo  =   $Table->find($table_id))
        {
            $this->error('餐桌不存在');
        }
        $store_id   =   $tinfo['sid'];
        $this->assign('tinfo',$tinfo);
        $storeinfo =   M('Store')->find($store_id);
        $shop_id=   $storeinfo['shop_id'];
        $this->assign('storeinfo',$storeinfo);
        $shop_info  = curl_get_contents(C('KANU_API').'getShopinfo.html?shop_id='.$shop_id);
        $shop_info=  json_decode($shop_info,true);
        $this->assign('shopinfo',$shop_info);
        $wminfo = curl_get_contents(C('KANU_API').'getMemberInfoByWmId.html?wm_id='.session('wm_id'));
        $wminfo=  json_decode($wminfo,true);
        $this->assign('wminfo',$wminfo);
        $cardmap['shop_id']=$shop_id;
        $cardmap['type']=2;
        //如果绑定了积分卡
        if($myjifencard=M('Card')->where($cardmap)->find())
        {            
            //判断是否领取该商户积分卡，没有则静默领取
            $Kanu   =   new \Lib\Kanu($shop_id);
            if(false!==$cards  =   $Kanu->getMemberCardLists(session('wm_id'),'card_id'))
            {
                $card_id    = $myjifencard['card_id'];                
                if(empty($cards) || !in_array($card_id, array_keys($cards)))
                {
                    //静默领取
                    $Kanu->receiveCard($card_id, session('wm_id'));
                }
            }
        }
        $Car    = D('Car');
        $nowmap['wm_id']   = session('wm_id');
        $nowmap['table_id']    =   $table_id;
        $now_cars=$Car->where($nowmap)->getField('goods_id,num');
        $this->assign('now_cars',$now_cars);
        if(IS_AJAX)
        {
            $id = I('id',0,'intval');
            if(empty($id))
            {
                $this->error('参数错误');
            }            
            $adddata['goods_id']    =   $id;
            $adddata['wm_id']   = session('wm_id');
            $adddata['table_id']    =   $table_id;
            $act    =   I('act');
            $now_car=$Car->where($adddata)->find();
            if('reduce'==$act)
            {
                if(empty($now_car) || $now_car['num']<1)
                {
                    $this->error('不能小于0');
                }
                if($now_car['num']>1)
                {
                    $Car->where($adddata)->setDec('num');
                }
                else
                {                    
                    $Car->where($adddata)->delete();
                }
                $this->success();
                return;
            }
            elseif('del'==$act)
            {
                $a=$Car->where($adddata)->delete();
                $this->success($a);
                return;                
            }
            else
            {
                if($now_car)
                {     
                    $adddata=$tmpdata;
                    unset($tmpdata);
                    $adddata['num']++;
                    if(false===$Car->save($adddata))
                    {
                        $this->error($Car->getError());
                    }
                }
                else
                {
                    $adddata['num']=1;
                    if(false===$id=$Car->add($adddata))
                    {
                        $this->error($Car->getError());
                    }
                }
            }            
            $this->success();
            return;
        }
        //当前门店所有桌台
        $tlists=$Table->where(array('sid'=>$store_id))->select();
        //该用户已开台餐桌
        $havingtables=array();
        $tlist=array();
        foreach($tlists as $v)
        {
            $tlist[$v['id']]=$v;
            
            if($v['status']!=0&&session('wm_id')==S('table_member_lock_'.$v['id'])&&$v['id']!=$table_id)
            {
                $havingtables[]=$v['id'];
            }
        }
        
        $this->assign('tlist',$tlist);
        if(!empty($havingtables))
        {
            $choose_type=I('choose_type',0,'intval');
            if(empty($choose_type))
            {
                $this->assign('choose_type',1);
                //选择换桌还是继续开台
                $this->display();
                return;
            }
            elseif(1==$choose_type)//换桌
            {
                if(count($havingtables)>1)
                {
                    $choose_which   =   I('choose_which',0,'intval');
                    if(empty($choose_which))
                    {
                        $this->assign('havingtables',$havingtables);
                        //选择换桌 换哪一张桌
                        $this->display();
                        return;
                    }
                    elseif(!in_array($choose_which,$havingtables))
                    {
                        $this->error('请正确选择你要换的餐桌');
                    }
                    else
                    {
                        $change_table_id=$choose_which;
                    }
                }
                else
                {
                    $change_table_id=$havingtables[0];
                }
                S('table_member_lock_'.$change_table_id,null);
                $Table->where(array('id'=>$change_table_id))->setField('status',0);
                //购物车的id替换
                $Car->where(array('wm_id'=>  session('wm_id'),'table_id'=>$change_table_id))->setField('table_id',$table_id);
            }
            elseif(2==$choose_type)//开台
            {
                
            }
        }
        
        //将餐桌与用户绑定
        if(!S('table_member_lock_'.$table_id))
        {
            S('table_member_lock_'.$table_id,session('wm_id'));
        }
        if(!empty($tinfo['status']) && S('table_member_lock_'.$table_id)!= session('wm_id'))
        {
            $this->error('餐桌不是空闲状态');
        }
        
        $Pay  = D('Pay');
        $pmap['table_id']=$table_id;
        $pmap['status']=array('lt',3);//状态 1， 2 未失效的订单
        if($Pay->where($pmap)->find())
        {            
            $this->error('您已经点过餐了',U('Order/index'));
        }
        
        //餐桌设置成已开台
        $Table->where(array('id'=>$table_id))->setField('status',1);
        $cmap['sid']=$gmap['sid']=$tinfo['sid'];
        $cmap['fid']=0;//暂时只支持一级分类
        $GoodsCate   =   D('GoodsCate');
        $goodscates =   $GoodsCate->where($cmap)->order('sort')->select();
        if(!empty($cid))
        {
            $gmap['cid']=$cid;
            $this->assign('cid',$cid);
            $catename   =   $GoodsCate->getFieldById($cid,'name');
            $this->assign('catename',$catename);
        }
        else
        {
            $this->assign('catename','菜品分类');
        }
        $this->assign('goodscates',$goodscates);
        $Goods   =   D('Goods');
        $goods =   $Goods->where($gmap)->order('sort,sellnums desc')->select();
        $this->assign('goods',$goods);
        $Tsc    =   M('Tsc');
        $tscs=$Tsc->where(array('sid'=>$tinfo['sid']))->select();
        foreach($tscs as &$v)
        {
            $v['gname']=$Goods->getFieldById($v['goods_id'],'name');
        }
        $this->assign('tscs',$tscs);
        $this->display();
    }
}