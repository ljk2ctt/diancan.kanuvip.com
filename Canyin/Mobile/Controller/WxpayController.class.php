<?php

namespace Mobile\Controller;

class WxpayController extends CommonController {
    public function _initialize(){
//       redirect(U('Wechatpaytest/index',I()));
       layout(false);
   }
 
	/**
     * 微信支付
     * 主方法
     * @param [get] [order_id] [订单编号]
    */
    public function index() {
    	//获取用户订单号（非必须）
        $order_sn = I('get.order_sn');	
      
        /*-------------------用户实现自己的业务逻辑----------------------------*/
        /*本业务逻辑仅供参考 */
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
        if(2!=$info['pay_mode'])
        {
            $this->error('该订单不是该支付方式');
        }
        $table_id=$info['table_id'];
        $store_id   = M('Table')->getFieldById($table_id,'sid');
        $shop_id    = M('Store')->getFieldById($store_id,'shop_id');
        //获取微信支付配置
        $wxmap['shop_id']=$shop_id;
        $wxpayconfig=D('Weixinpay')->where($wxmap)->find(); 
        
        //H5网页端调起支付接口
        $jsApi = new \Lib\Wechat\Wxjspay($wxpayconfig);
	//统一支付接口类
	$unifiedOrder = new \Lib\Wechat\UnifiedOrder($wxpayconfig);
	//获取用户openid
        if (!isset($_GET['code'])){
            $url = $jsApi->createOauthUrlForCode(C('WEB_DOMAIN').substr(U(''),1)."?order_sn=".$order_sn);
            Header("Location: $url");
        }else{
            $code = $_GET['code'];
            $jsApi->setCode($code);
            $openid = $jsApi->getOpenId();
        }
        $amount =$info['true_pay']*100; //查询支付金额
       /*-----------------------------必填--------------------------*/ 
        $unifiedOrder->setParameter("body","订单支付");//商品描述支付平台
        $unifiedOrder->setParameter("out_trade_no",$info['order_sn'].'_'.NOW_TIME);//商户订单号
        $unifiedOrder->setParameter("total_fee",$amount);//总金额（微信支付以人民币“分”为单位）
       /*-------------------------------------------------------*/  
        $unifiedOrder->setParameter("openid","$openid");//获取到的OPENID
        $unifiedOrder->setParameter("notify_url",C('WEB_DOMAIN').  substr(U('notify'), 1));//通知地址
        $unifiedOrder->setParameter("trade_type","JSAPI");//交易类型        
        
        $prepay_id = $unifiedOrder->getPrepayId(); 
        //=========步骤3：使用jsapi调起支付============
        $jsApi->setPrepayId($prepay_id);
        $jsApiParameters = $jsApi->getParameters();
        $this->jsApiParameters = $jsApiParameters;
        $this->info = $info;
        $this->display('index'); //渲染支付页面
//        echo $jsApiParameters;
    }
    /**
     * 微信支付回调
    */
    public function notify(){
        $postStr = $GLOBALS["HTTP_RAW_POST_DATA"];
        M('PayWeixinXmlLog')->add(array('xml'=>$postStr));
	$wechatpay = new \Lib\Wechat\Wechatpay();        
        $array = $wechatpay->xmlToArray($postStr);//获取回调参数，order_id        
        $order_sn = substr($array['out_trade_no'],0,strpos($array['out_trade_no'], '_'));
        $map['order_sn']=$order_sn;
        $info = M('Pay')->lock(true)->where($map)->find(); //订单详情       
        $table_id=$info['table_id'];
        $store_id   = M('Table')->getFieldById($table_id,'sid');
        $shop_id    = M('Store')->getFieldById($store_id,'shop_id');
        //获取微信支付配置
        $wxmap['shop_id']=$shop_id;
        $wxpayconfig=D('Weixinpay')->where($wxmap)->find();  
        $unifiedOrder   =   new \Lib\Wechat\UnifiedOrder($wxpayconfig);
        unset($wechatpay);
        if(false==$res=$unifiedOrder->checkSign($array))
        {            
            $unifiedOrder->setReturnParameter("return_code","FAIL");//返回状态码
            $unifiedOrder->setReturnParameter("return_msg","签名失败");//返回信息
        }
        else
        {
            $unifiedOrder->setReturnParameter("return_code","SUCCESS");//设置返回码
	}
        /*-----------业务逻辑-------------*/
        if(true===$res)
        {          
            if($array['return_code']== "SUCCESS"&&$array['result_code']== "SUCCESS")
            {                
                
                D('Pay')->where($info)->setField('status',2);
                $returnXml = $unifiedOrder->returnXml();
                echo $returnXml;
            }
            
        }          
	/*-----------业务逻辑-------------*/
		
    }

    
}
