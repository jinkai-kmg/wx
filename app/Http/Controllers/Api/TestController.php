<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\XcxUserModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use GuzzleHttp\Client;
use App\Models\GoodsModel;

class TestController extends Controller
{
    public $str_obj;

    public function test(){
        print_r($_GET);
        print_r($_POST);
    }

    //小程序登录
    public function onLogin(){
        //接受code
        $code = request()->get('code');
//        echo    $code;
        //使用code
        $url = 'https://api.weixin.qq.com/sns/jscode2session?appid='.env('WX_XCX_APPID').'&secret='.env('WX_XCX_SECRET').'&js_code='.$code.'&grant_type=authorization_code';

        $data = json_decode(file_get_contents($url),true);
//        dd($data);

        //自定义登录状态
        if(isset($data['errcode'])){
            $response = [
                'errno' => 50001,
                'msg' => '登录失败'
            ];
        }else{
            $token = sha1($data['openid'].$data['session_key'].mt_rand(0,999999));
            $redis_key = 'wx_xcx_token'.$token;
            Redis::set($redis_key,time());
            Redis::expire($redis_key,7200);

            XcxUserModel::insert($data);
            $response = [
                'errno' => 0,
                'msg' => 'ok',
                'data' => [
                    'token' => $token
                ]
            ];
        }
        return $response;

    }

    //用户信息
    public function xcxlogin(){
        $code = request()->get('code');
        // echo $code;
        //使用code
        $userinfo =json_decode(file_get_contents("php://input"),true);
        $url = 'https://api.weixin.qq.com/sns/jscode2session?appid='.env('WX_XCX_APPID').'&secret='.env('WX_XCX_SECRET').'&js_code='.$code.'&grant_type=authorization_code';
        $data = json_decode(file_get_contents($url),true);
        if(isset($data['errcode'])){
            $response = [
                'error' =>50001,
                'msg' =>'登入失败',
            ];
        }else{
            $openid = $data['openid'];
            $u = DB::table('wxuser')->where(['openid'=>$openid])->first();
            if($u){

            }else{
                $u_info = [
                    'openid' => $openid,
                    'nickname' => $userinfo['u']['nickName'],
                    'sex' => $userinfo['u']['gender'],
                    'language' => $userinfo['u']['language'],
                    'city' => $userinfo['u']['city'],
                    'province' => $userinfo['u']['province'],
                    'country' => $userinfo['u']['country'],
                    'headimgurl' => $userinfo['u']['avatarUrl'],
                    'subscribe_time' => time(),
                    'type' =>3
                ];
            }
            DB::table('wxuser')->insertGetId($u_info);
        }

    }

    //首页商品信息
    public function goods(){
        $pagesize = request()->get('ps');
        $data = GoodsModel::select('goods_id','goods_name','shop_price','goods_img')->limit(10)->paginate($pagesize);
//        dd($data);
        return  $data;
    }

    //商品详情
    public function goodsinfo(){
        $goods_id = request()->goods_id;
//        return $goods_id;
        $info = GoodsModel::where(['goods_id'=>$goods_id])->get()->toArray();
        $info['goods_imgs'] = [
            '//m.360buyimg.com/mobilecms/s750x750_jfs/t1/138694/17/10615/68848/5f861345E105290e8/27a4a550d6b41eee.jpg!q80.dpg',
            '//m.360buyimg.com/mobilecms/s1125x1125_jfs/t1/126256/19/14768/67348/5f861348Eede929c4/2aa8ce70add5f3b6.jpg!q70.dpg.webp',
            '//m.360buyimg.com/mobilecms/s1125x1125_jfs/t1/135503/9/12217/99639/5f86134bE9144ce5f/66534f8695095186.jpg!q70.dpg.webp',
            '//m.360buyimg.com/mobilecms/s1125x1125_jfs/t1/130775/39/12262/72360/5f86134eEec143f90/eb79c28e8465c119.jpg!q70.dpg.webp',
            '//m.360buyimg.com/mobilecms/s1125x1125_jfs/t1/127784/36/14807/106073/5f86135fE25bce65d/6b297bf6a95f39bb.jpg!q70.dpg.webp',
            '//m.360buyimg.com/mobilecms/s1125x1125_jfs/t1/119931/37/14878/118824/5f861352Ed3142c2a/9a4eacf9e69bc484.jpg!q70.dpg.webp',
            '//m.360buyimg.com/mobilecms/s1125x1125_jfs/t1/126920/40/14946/108743/5f861356E9cf46b82/006ec7d53581e8ba.jpg!q70.dpg.webp',
            '//m.360buyimg.com/mobilecms/s1125x1125_jfs/t1/152153/6/2111/49964/5f861359E95c13e85/edf6143efe3ccfe9.jpg!q70.dpg.webp'
        ];
        $info['goods_desc_imgs'] = [
            '//img13.360buyimg.com/cms/jfs/t1/120836/20/14832/819799/5f8604f8Eb381a921/5be9108f28a06b69.jpg'
        ];
        return $info;
    }

    //添加购物车
    public function add_cart(){
        $goods_id = request()->goods_id;
//        return $goods_id;
    }

}
