<?php
namespace Mobile\Controller;
/**
 * 预约
 */
class ReservedController extends CommonController {
    public function index(){
        $sid    =   I('sid',0,'intval');
        if(empty($sid))
        {
            $this->error('参数错误');
        }
        $Store  =   M('Store');
        if(!$Store->find($sid))
        {
            $this->error('不存在门店');
        }
        if(IS_AJAX && IS_POST)
        {
            $Reserved   =   D('reserved');
            if(false===$Reserved->create() || false===$Reserved->add())
            {
                $this->error($Reserved->getError());
            }
            $this->success();
            return;
        }
        $json   = curl_get_contents(C('KANU_API').'getMemberInfoByWmId.html?wm_id='.session('wm_id'));
        $info=  json_decode($json,true);
        $this->assign('phone',$info['phone']);
        $tables_count   =   M('Table')->where(array('status'=>0,'sid'=>$sid))->count();
        $this->assign('tables_count',$tables_count);
        $this->assign('sid',$sid);
        $this->display();
    }
}