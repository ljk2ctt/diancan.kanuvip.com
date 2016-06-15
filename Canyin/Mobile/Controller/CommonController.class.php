<?php
namespace Mobile\Controller;
use Think\Controller;
class CommonController extends Controller {
    public function _initialize()
    {
        if(!session('wm_id') && !checkwxbrowser())
        {
            session('wm_id',41);
        }
        //卡包weixinmember表id
        if(!session('wm_id'))
        {
            $wm_id=I('get.wm_id',0,'intval');
            if(empty($wm_id))
            {
                redirect(C('KANU_DOMAIN').substr(U('Home/Public/welogin'),1).'?url='.C('WEB_DOMAIN').substr(U('',I('')),1));
                return;
            }
            session('wm_id',$wm_id);
        }
    }
}