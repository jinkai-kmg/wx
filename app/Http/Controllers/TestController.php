<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use GuzzleHttp\Client;
use App\Models\WxuserModel;

class TestController extends Controller
{
    public $str_obj;
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

    /*
     * 接受微信推送事件
    */
    public function wxEvent(Request $request){
        $echostr = $request->echostr;
        if(!empty($echostr)){
            echo $echostr;
            die;
        }
        //接受数据
        $xml_str = file_get_contents("php://input");
        //记录日志
        file_put_contents("wx_event.log",$xml_str);

        $data = simplexml_load_string($xml_str);
//            print_r($data);die;
        $this->str_obj = $data;

        //关注取消关注
        if (strtolower($data->MsgType) == "event") {
            if (strtolower($data->Event) == 'subscribe') {
                $openid = $this->str_obj->FromUserName;
                $user = WxuserModel::where(['openid'=>$openid])->first();
                if($user){
                    $content = "欢迎再次关注";
                }else {
                    $this->subscribe();
                    $content = "欢迎关注";
                }

                echo $this->response($data,$content);die;
            }else{
                //取消关注
            }
            if(strtolower($data->Event) == 'click'){
                if(strtolower($data->EventKey) == 'wx_key_weather'){
                    echo    $this->weather();die;
                }
            }
        }

        //文本回复
        if(strtolower($data->MsgType) == "text") {
            if (strtolower($data->Content) == "你好") {
                $content = "你好ya";
                echo $this->response($data, $content);
                die;
            }
        }

        //图片消息
        if(strtolower($data->MsgType) == "image"){

        }
    }

    //处理文本消息
    public function response($content){
        $fromUserName = $this->str_obj->ToUserName;
        $toUserName = $this->str_obj->FromUserName;
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

    /*
     * 自定义菜单
     */
    public function menu(){
        $arr = [
            'button' => [
                [
                    'type' => 'click',
                    'name' => '签到',
                    'key' => 'wx_key_card'
                ],
                [
                    'type' => 'click',
                    'name' => '获取天气',
                    'key' => 'wx_key_weather'
                ],
                [
                    'name' => '发图',
                    'sub_button' => [
                        [
                            'type' => 'pic_sysphoto',
                            'name' => '系统拍照发图',
                            'key' => 'rselfmenu_1_0',
                            "sub_button" => [ ]
                        ],
                        [
                            "type" => "pic_photo_or_album",
                            "name" => "拍照或者相册发图",
                            "key" => "rselfmenu_1_1",
                            "sub_button" => [ ]
                        ],
                        [
                            "type" => "pic_weixin",
                            "name" => "微信相册发图",
                            "key" => "rselfmenu_1_2",
                            "sub_button" => [ ]
                        ]
                    ]
                ]
            ]
        ];
        $arr = json_encode($arr,JSON_UNESCAPED_UNICODE);
        $access_token = $this->getAccessToken();
//        echo    $access_token;die;
        $url = "https://api.weixin.qq.com/cgi-bin/menu/create?access_token=".$access_token;
        $client = new Client();
        $res_menu = $client->request('POST',$url,[
            'verify'    => false,    //忽略 HTTPS证书 验证
            'body' => $arr
        ]);
        $data = $res_menu->getBody();
        echo    $data;
    }
    public function guzzle(){

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
            $client = new Client();
            $res = $client->request('GET',$url,['verify' => false]);
            $response = $res->getBody();
//            $response = file_get_contents($url);
            $data = json_decode($response,true);
            $token = $data['access_token'];
            Redis::set($key,$token);
            Redis::expire($key,3600);
        }
        return $token;
    }

    //获取天气
    public function weather(){
        $url = "https://devapi.qweather.com/v7/weather/now?location=101010100&key=3b20b6ae1ba348c4afdc9545926f1694&gzip=n";
        $red = $this->curl($url);
        $red = json_decode($red,true);
        $rea = $red['now'];
        $rea = implode(',',$rea);
        return $rea;
    }

    //获取用户信息
    public function getUserInfo($openid){
        $access = $this->getAccessToken();
        $url = "https://api.weixin.qq.com/cgi-bin/user/info?access_token=".$access."&openid=".$openid."&lang=zh_CN";
        $client = new Client();
        $res = $client->request('GET',$url,[
            'verify'    => false,    //忽略 HTTPS证书 验证
        ]);
        return json_decode($res->getBody(),true);
    }

    /*
     * 新增临时素材
     */
    public function media(){
        $type = 'image';
        $access = $this->getAccessToken();
        $url = 'https://api.weixin.qq.com/cgi-bin/media/upload?access_token='.$access.'&type='.$type;
        $client = new   Client();

    }

    /*
     * 用户信息入库
     * */
    public function subscribe(){
        $openid = $this->str_obj->FromUserName;
            $userinfo = $this->getUserInfo($openid);
            unset($userinfo['remark']);
            unset($userinfo['groupid']);
            unset($userinfo['tagid_list']);
            unset($userinfo['subscribe_scene']);
            unset($userinfo['qr_scene']);
            unset($userinfo['qr_scene_str']);
            WxuserModel::insertGetId($userinfo);
    }

    //调用接口方法
    public function curl($url,$header="",$content=[]){
        $ch = curl_init(); //初始化CURL句柄
        if(substr($url,0,5)=="https"){
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,2);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,true); //字符串类型打印
        curl_setopt($ch, CURLOPT_URL, $url); //设置请求的URL
        if(!empty($header)){
            curl_setopt ($ch, CURLOPT_HTTPHEADER,$header);
        }
        if($content){
            curl_setopt ($ch, CURLOPT_POST,true);
            curl_setopt ($ch, CURLOPT_POSTFIELDS,$content);
        }
        //执行
        $output = curl_exec($ch);
        if($error=curl_error($ch)){
            die($error);
        }
        //关闭
        curl_close($ch);
        return $output;
    }
}
