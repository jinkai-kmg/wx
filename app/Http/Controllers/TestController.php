<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use GuzzleHttp\Client;

class TestController extends Controller
{
    //接入微信
    private function index()
    {
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];
        $token = "shanyi";
        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode( $tmpArr );
        $tmpStr = sha1( $tmpStr );

        if( $tmpStr == $signature ){
            return true;
        }else{
            return false;
        }
    }
    //获取access_token
    public function getAccessToken(){
        $key = 'wx:access_token';
        $token = Redis::get($key);
        if($token){
            echo    "有缓存";
        }else{
            echo    "无";
            $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".env('WX_APPID')."&secret=".env('WX_APPSECRET');
//        dd($url);
            $response = file_get_contents($url);
            $data = json_decode($response,true);
            $token = $data['access_token'];
            Redis::set($key,$token);
            Redis::expire($key,3600);
        }
        echo $token;
    }
    /*
     * 接受微信推送事件
    */
    public function wxEvent(){
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];
        $token = "shanyi";
        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode( $tmpArr );
        $tmpStr = sha1( $tmpStr );
        if($tmpStr == $signature){
            //接受数据
            $xml_str = file_get_contents("php://input");
            //记录日志
            //file_put_contents("wx_event.log",$xml_str);

            $data = simplexml_load_string($xml_str);
            if (strtolower($data->MsgType) == "event") {
                if (strtolower($data->Event == 'subscribe')) {
                    $content = "欢迎关注";
                    //%s代表字符串(发送信息)
                    $info = $this->responseText($data,$content);
                    echo $info;die;
                }
            }
            echo    "";
            die;
        }else{
            echo    "";
        }
    }
    public function guzzle(){

    }
    //处理消息
    public function responseText($xml,$content){
        $fromUserName=$xml->ToUserName;
        $toUserName=$xml->FromUserName;
        $time=time();
        $msgType="text";
        $xml="<xml>
                       <ToUserName><![CDATA[%s]]></ToUserName>
                       <FromUserName><![CDATA[%s]]></FromUserName>
                       <CreateTime>%s</CreateTime>
                       <MsgType><![CDATA[%s]]></MsgType>
                       <Content><![CDATA[%s]]></Content>
                       </xml>";//发送//来自//时间//类型//内容
        return sprintf($xml,$toUserName,$fromUserName,$time,$msgType,$content);
    }
}
