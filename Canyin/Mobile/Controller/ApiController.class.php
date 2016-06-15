<?php
/**
 * 点餐系统API
 * 
 */
namespace Mobile\Controller;
use Think\Controller;
class ApiController extends Controller {
    public function _empty($name){
        $this->error('不存在接口:'.$name);
    }
    public function _initialize()
    {
        if(!IS_POST)
        {
            $this->error('请使用post请求');
        }
        $this->checkSign();
    }
    /**
     * 服务员登录
     * @param string $username 用户名
     * @param string $pwd 密码
     * @return json  uid store_id token
     */
    public function ulogin()
    {
        $username   = I('username');
        $pwd        = I('pwd');
        if(empty($username))
        {
            $this->error('缺少参数 username');
        }
        if(empty($pwd))
        {
            $this->error('缺少参数 pwd');
        }
        $map['username']    = $username;
        $map['pwd']         = $pwd;
        if(!$data=M('Waiter')->where($map)->find())
        {
            $this->error('用户名或密码错误');
        }
        if(empty($data['token']))
        {
            $data['token']=md5($username.$pwd.NOW_TIME.rand(0,99));
            M('Waiter')->where($map)->setField('token',$data['token']);
        }
        $return['uid']=$data['id'];
        $return['store_id']=$data['store_id'];
        $return['token']=$data['token'];
        $this->success($return);
    }
    
    /**
     * 获取门店餐桌信息
     * @param string $token 登录信息
     */
    public function getTables()
    {
        $uinfo = $this->uinfo();
        $sid   = $uinfo['store_id'];
        if(empty($sid))
        {
            $this->error('接口错误 缺少store_id');
        }
        $Table  = D('Table');
        $map['sid']=$sid;
        if(false===$tables=$Table->where($map)->select())
        {
            $this->error($Table->getError());
        }
        $unsettables=array();
        foreach($tables as $table)
        {            
            if(!isset($unsettables['sinfo']))
            {
                unset($table['sinfo']['shop_id']);
                unset($table['sinfo']['area_id']);
                unset($table['sinfo']['addtime']);
                unset($table['sinfo']['token']);
                $unsettables['sinfo']=$table['sinfo'];
            }
            unset($table['sid']);
            unset($table['addtime']);
            unset($table['sinfo']);
            $unsettables['tinfo'][$table['id']]=$table;
        }
        $this->success($unsettables);
    }
    /**
     * 获取菜单
     * @param int $table_id 餐桌id
     * @param string $token 登录信息
     * @param int $cid 分类id
     */
    public function menu()
    {
        $table_id   =   I('table_id',0,'intval');
        $uinfo = $this->uinfo();
        $uid   = $uinfo['id'];
        $cid   =   I('cid',0,'intval');
        if(empty($uid))
        {            
            $this->error('接口错误 缺少uid');
        }
        if(empty($table_id))
        {
            $this->error('缺少参数table_id');
        }
        $Table  = D('Table');
        if(!$tinfo  =   $Table->find($table_id))
        {
            $this->error('餐桌不存在');
        }
        $store_id   =   $tinfo['sid'];
        $storeinfo =   M('Store')->find($store_id);
        $shop_id=   $storeinfo['shop_id'];
        $shop_info  = curl_get_contents(C('KANU_API').'getShopinfo.html?shop_id='.$shop_id);
        $shop_info=  json_decode($shop_info,true);
        
        //将餐桌与服务员绑定
        if(!S('table_water_lock_'.$table_id))
        {
            S('table_water_lock_'.$table_id,$uid);
        }
        if(!empty($tinfo['status']) && (S('table_member_lock_'.$table_id)))
        {            
            S('table_water_lock_'.$table_id,null);
            $this->error('餐桌已经被客人开台');
        }                
        if(S('table_water_lock_'.$table_id)!=$uid)
        {
            $this->error('餐桌不是空闲状态');
        }
        
        $Pay  = D('Pay');
        $pmap['table_id']=$table_id;
        $pmap['status']=array('lt',3);//状态 1， 2 未失效的订单
        if($Pay->where($pmap)->find())
        {            
            $this->error('该餐桌已下单');
        }        
        //餐桌设置成已开台
        $Table->where(array('id'=>$table_id))->setField('status',1);
        $cmap['sid']=$gmap['sid']=$tinfo['sid'];
        $cmap['fid']=0;//暂时只支持一级分类
        $GoodsCate   =   D('GoodsCate');
        $goodscates =   $GoodsCate->where($cmap)->order('sort')->select();
        $Goods   =   D('Goods');        
        if(!empty($cid))
        {
            $gmap['cid']=$cid;
        }
        $goods =   $Goods->where($gmap)->order('sort,sellnums desc')->select();
        $Tsc    =   M('Tsc');
        $tscs=$Tsc->where(array('sid'=>$tinfo['sid']))->select();
        foreach($tscs as &$v)
        {
            $v['gname']=$Goods->getFieldById($v['goods_id'],'name');
        }
        $return['cate']=$goodscates;
        $return['goods']=$goods;
        $return['tscs']=$tscs;
        $return['tinfo']=$tinfo;
        $return['sinfo']=$shop_info;
        $this->success($return);        
    }
    /**
     * 菜单分类
     * @param string $token 登录信息
     */
    public function menucate()
    {
        $uinfo  = $this->uinfo();
        $cmap['sid']=$gmap['sid']=$uinfo['shop_id'];
        $cmap['fid']=0;//暂时只支持一级分类
        $GoodsCate   =   D('GoodsCate');
        $goodscates =   $GoodsCate->where($cmap)->order('sort')->select();
        $this->success($goodscates);
    }
    /**
     * 点菜
     * table_id
     */
    public function order()
    {
        $id = I('id',0,'intval');
        if(empty($id))
        {
            $this->error('缺少参数id');
        }
        $uinfo = $this->uinfo();
        $uid   = $uinfo['id'];
        if(empty($uid))
        {            
            $this->error('接口错误 缺少uid');
        }
        $table_id   =   I('table_id',0,'intval');
        if(empty($table_id))
        {
            $this->error('缺少参数table_id');
        }
        $Table  = D('Table');
        if(!$tinfo  =   $Table->find($table_id))
        {
            $this->error('餐桌不存在');
        }
        $Car    = D('Car');
        $adddata['goods_id']    =   $id;
        $adddata['waiter_id']   = $uid;
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
    }
    /**
     * 查询购物车
     */
    public function cart()
    {
        
    }
    
    
    
    
    
    
    /**
     * 参数按键值排序连接md5
     * 验证签名
     */
    private function checkSign()
    {
        $data=I('');
        if(!isset($data['sign']))
        {
            //缺少签名
            $this->error('缺少参数sign');
        }      
        $localsign=  $this->getSign();
        if($localsign!=$data['sign'])
        {
            $this->error('签名错误');
        }
    }
    private function getSign()
    {
        $data=I('');
        unset($data['sign']);
        ksort($data);
        foreach ($data as $k => $v) {
            $v = urlencode($v);
            $buff .= $k . "=" . $v . "&";
        }
        return md5($buff);
    }
    /**
     * 通过token获取服务员信息
     * @param string $token
     */
    private function uinfo($token='')
    {
        if(empty($token) && !$token=I('token'))
        {
            $this->error('缺少参数token');
        }
        if(!$uinfo=M('Waiter')->getByToken($token))
        {
            $this->error('登录过期');
        }
        return $uinfo;
    }
    public function success($msg = '') {
        $data['msg']=$msg;
        $data['status']=1;
        $this->ajaxReturn($data);
        exit;
    }
    public function error($msg = '') {
        $data['msg']=$msg;
        $data['status']=0;
        $this->ajaxReturn($data);
        exit;
    }
}