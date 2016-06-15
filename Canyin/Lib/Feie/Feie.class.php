<?php

namespace Lib\Feie;
//==================方法1.打印订单==================
		//***接口返回值有如下几种***
		//{"responseCode":0,"msg":"服务器接收订单成功","orderindex":"xxxxxxxxxxxxxxxxxx"}
		//{"responseCode":1,"msg":"打印机编号错误"};
		//{"responseCode":2,"msg":"服务器处理订单失败"};
		//{"responseCode":3,"msg":"打印内容太长"};
		//{"responseCode":4,"msg":"请求参数错误"};
		
		//第三个参数为打印次（联）数,默认为1
		//wp_print(PRINTER_SN,KEY,1);

		
//===========方法2.查询某订单是否打印成功=============
		//***返回的状态有如下几种***
	 	//{"responseCode":0,"msg":"已打印"};
		//{"responseCode":0,"msg":"未打印"};
		//{"responseCode":1,"msg":"请求参数错误"};
		//{"responseCode":2,"msg":"没有找到该索引的订单"};
		
		//$orderindex = "xxxxxxxxxxxxxxxxxxxxxxxx";//订单索引，从方法1返回值中获取
		//queryOrderState(PRINTER_SN,KEY,$orderindex);

		
	
//===========方法3.查询指定打印机某天的订单详情============
		//***返回的状态有如下几种*** (print:已打印,waiting:未打印)
	 	//{"responseCode":0,"print":"xx","waiting":"xx"};
		//{"responseCode":1,"msg":"请求参数错误"};
		
		//$date = "2014-12-02";//注意时间格式为"yyyy-MM-dd"
		//queryOrderInfoByDate(PRINTER_SN,KEY,$date);



//===========方法4.查询打印机的状态==========================
		//***返回的状态有如下几种***
	    //{"responseCode":0,"msg":"离线"};
	    //{"responseCode":0,"msg":"在线,工作状态正常"}
		//{"responseCode":0,"msg":"在线,工作状态不正常"}
		//{"responseCode":1,"msg":"请求参数错误"};
		
		//queryPrinterStatus(PRINTER_SN,KEY);


/**
 * 飞蛾打印机
 *
 * @author Administrator
 */
class Feie {
    private $printer_sn='';
    private $key='';
    const IP='dzp.feieyun.com';
    const PORT=80;
    const HOSTNAME='/FeieServer';    
    private $error='';
    
    public function __construct($printer_sn,$key) {
        if(empty($printer_sn) || empty($key))
        {
            $this->error='参数为空';
            return false;
        }
        $this->printer_sn=$printer_sn;
        $this->key=$key;
    }
    
    public function doprint($orderInfo)
    {
        $content = array(
            'sn' => $this->printer_sn,
            'printContent' => $orderInfo,
//            'apitype'=>'php',//如果打印出来的订单中文乱码，请把注释打开
            'key' => $this->key,
            'times' => 1,//打印次数
        );
        $client = new HttpClient(self::IP, self::PORT);
        if (!$client->post(self::HOSTNAME . '/printOrderAction', $content)) {
            $this->error=$client->errormsg;
            return false;
        } else {
            $res= $client->getContent();
            $json=  json_decode($res);
            return $json->orderindex;
        }
    }
    /*
     *  方法1
      拼凑订单内容时可参考如下格式
      根据打印纸张的宽度，自行调整内容的格式，可参考下面的样例格式
     */
    public function wp_print($printer_sn, $key, $times) {

        //标签说明："<BR>"为换行符,"<CB></CB>"为居中放大,"<B></B>"为放大,"<C></C>"为居中,"<L></L>"为字体变高
        //"<W></W>"为字体变宽,"<QR></QR>"为二维码,"<CODE>"为条形码,后面接12个数字

        $orderInfo = '<CB>测试打印</CB><BR>';
        $orderInfo .= '名称　　　　　 单价  数量 金额<BR>';
        $orderInfo .= '--------------------------------<BR>';
        $orderInfo .= '饭　　　　　 　10.0   10  10.0<BR>';
        $orderInfo .= '炒饭　　　　　 10.0   10  10.0<BR>';
        $orderInfo .= '蛋炒饭　　　　 10.0   100 100.0<BR>';
        $orderInfo .= '鸡蛋炒饭　　　 100.0  100 100.0<BR>';
        $orderInfo .= '西红柿炒饭　　 1000.0 1   100.0<BR>';
        $orderInfo .= '西红柿蛋炒饭　 100.0  100 100.0<BR>';
        $orderInfo .= '西红柿鸡蛋炒饭 15.0   1   15.0<BR>';
        $orderInfo .= '备注：加辣<BR>';
        $orderInfo .= '--------------------------------<BR>';
        $orderInfo .= '合计：xx.0元<BR>';
        $orderInfo .= '送货地点：广州市南沙区xx路xx号<BR>';
        $orderInfo .= '联系电话：13888888888888<BR>';
        $orderInfo .= '订餐时间：2014-08-08 08:08:08<BR>';
        $orderInfo .= '<QR>http://www.dzist.com</QR>'; //把二维码字符串用标签套上即可自动生成二维码

        $content = array(
            'sn' => $printer_sn,
            'printContent' => $orderInfo,
            //'apitype'=>'php',//如果打印出来的订单中文乱码，请把注释打开
            'key' => $key,
            'times' => $times//打印次数
        );
        $client = new HttpClient(self::IP, self::PORT);
        if (!$client->post(self::HOSTNAME . '/printOrderAction', $content)) {
            echo 'error'.$client->errormsg;
        } else {
            echo $client->getContent();
        }
    }

    /*
     *  方法2
      根据订单索引,去查询订单是否打印成功,订单索引由方法1返回
     */

    public function queryOrderState($printer_sn, $key, $index) {
        $msgInfo = array(
            'sn' => $printer_sn,
            'key' => $key,
            'index' => $index
        );

        $client = new HttpClient(self::IP, self::PORT);
        if (!$client->post(self::HOSTNAME . '/queryOrderStateAction', $msgInfo)) {
            echo 'error';
        } else {
            $result = $client->getContent();
            echo $result;
        }
    }

    /*
     *  方法3
      查询指定打印机某天的订单详情
     */

    public function queryOrderInfoByDate($printer_sn, $key, $date) {
        $msgInfo = array(
            'sn' => $printer_sn,
            'key' => $key,
            'date' => $date
        );

        $client = new HttpClient(self::IP, self::PORT);
        if (!$client->post(self::HOSTNAME . '/queryOrderInfoAction', $msgInfo)) {
            echo 'error';
        } else {
            $result = $client->getContent();
            echo $result;
        }
    }

    /*
     *  方法4
      查询打印机的状态
     */

    public function queryPrinterStatus($printer_sn, $key) {

        $msgInfo = array(
            'sn' => $printer_sn,
            'key' => $key,
        );

        $client = new HttpClient(self::IP, self::PORT);
        if (!$client->post(self::HOSTNAME . '/queryPrinterStatusAction', $msgInfo)) {
            echo 'error';
        } else {
            $result = $client->getContent();
            echo $result;
        }
    }
    public function getError()
    {
        return $this->error;
    }
}
