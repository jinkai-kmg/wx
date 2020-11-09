<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

class TestController extends Controller
{
    public function index(){
        $echoStr =request()->get("echostr","");
        if($this->checkSignature() && !empty($echoStr)){
            //至少微信公众平台第一次接入调用走这个
            echo $echoStr;

        }
    }
    private function checkSignature()
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
    public function wxEvent(){
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];
        $token = "shanyi";
        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode( $tmpArr );
        $tmpStr = sha1( $tmpStr );
        if($tmpArr == $signature){
            //接受数据
            $xml_str = file_get_contents("php://input");
            //记录日志
            file_put_contents("wx_event.log",$xml_str);
            //把xml文本转换为php的对象或数组
            $data = simplexml_load_string($xml_str,'SimpleXMLElement',LIBXML_NOCDATA);
            $xml = "<xml>
                        <ToUserName><![CDATA[toUser]]></ToUserName>
                        <FromUserName><![CDATA[FromUser]]></FromUserName>
                        <CreateTime>123456789</CreateTime>
                        <MsgType><![CDATA[event]]></MsgType>
                        <Event><![CDATA[subscribe]]></Event>
                    </xml>";
            echo    $xml;
        }else{

        }
    }
    public function test2(){
        print_r($_GET);
    }
    public function test3(){
        print_r($_POST);
    }
}
