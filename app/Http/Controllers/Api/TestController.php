<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\XcxUserModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use GuzzleHttp\Client;
use App\Models\GoodsModel;
use App\Models\CartModel;
use App\Models\XcxwwxModel;
use App\Models\CityModel;

class TestController extends Controller
{
    public $str_obj;

    /**
     * 小程序首页登录
     * @param Request $request
     */
    public function homeLogin(Request $request)
    {
        //接收code
        $code = $request->get('code');
//        return $code;
        //使用code
        $url = 'https://api.weixin.qq.com/sns/jscode2session?appid=' . env('WX_XCX_APPID') . '&secret=' . env('WX_XCX_SECRET') . '&js_code=' . $code . '&grant_type=authorization_code';

        $data = json_decode(file_get_contents($url), true);
//        return $data;
        //自定义登录状态
        if (isset($data['errcode']))     //有错误
        {
            $response = [
                'errno' => 50001,
                'msg' => '登录失败',
            ];

        } else {              //成功
            $openid = $data['openid'];          //用户OpenID
            //判断新用户 老用户
            $u = XcxUserModel::where(['openid' => $openid])->first();
            if ($u) {
                // TODO 老用户
                $u_id = $u->u_id;
                //更新用户信息

            } else {
                // TODO 新用户
                $u_info = [
                    'openid' => $openid,
                    'add_time' => time(),
                    'type' => 3        //小程序
                ];

                $u_id = XcxUserModel::insertGetId($u_info);
            }

            //生成token
            $token = sha1($data['openid'] . $data['session_key'] . mt_rand(0, 999999));
            //保存token
            $redis_login_hash = 'h:xcx:login:' . $token;

            $login_info = [
                'u_id' => $u_id,
                'user_name' => "",
                'login_time' => date('Y-m-d H:i:s'),
                'login_ip' => $request->getClientIp(),
                'token' => $token,
                'openid' => $openid
            ];

            //保存登录信息
            Redis::hMset($redis_login_hash, $login_info);
            // 设置过期时间
            Redis::expire($redis_login_hash, 7200);

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

    /**
     * 小程序 个人中心登录
     * @param Request $request
     * @return array
     */
    public function userLogin(Request $request)
    {
        //接收code
        //$code = $request->get('code');
        $token = $request->get('token');

        //获取用户信息
        $userinfo = json_decode(file_get_contents("php://input"), true);

        $redis_login_hash = 'h:xcx:login:' . $token;
        $openid = Redis::hget($redis_login_hash, 'openid');          //用户OpenID
//        dd($openid);
        $u0 = XcxwwxModel::where(['openid' => $openid])->first();
        if(empty($u0)){
            $u_info = [
                'openid' => $openid,
                'nickname' => $userinfo['u']['nickName'],
                'sex' => $userinfo['u']['gender'],
                'language' => $userinfo['u']['language'],
                'city' => $userinfo['u']['city'],
                'province' => $userinfo['u']['province'],
                'country' => $userinfo['u']['country'],
                'headimgurl' => $userinfo['u']['avatarUrl'],
                'update_time'   => 0
            ];
            XcxwwxModel::insert($u_info);
        }elseif($u0->update_time == 0){     // 未更新过资料
            //因为用户已经在首页登录过 所以只需更新用户信息表
            $u_info = [
                'nickname' => $userinfo['u']['nickName'],
                'sex' => $userinfo['u']['gender'],
                'language' => $userinfo['u']['language'],
                'city' => $userinfo['u']['city'],
                'province' => $userinfo['u']['province'],
                'country' => $userinfo['u']['country'],
                'headimgurl' => $userinfo['u']['avatarUrl'],
                'update_time'   => time()
            ];
            XcxwwxModel::where(['openid' => $openid])->update($u_info);
        }



        $response = [
            'errno' => 0,
            'msg' => 'ok',
        ];

        return $response;

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
        $goods_id = request()->post('goodsid');
//        dd($goods_id);
        $u_id = $_SERVER['u_id'];
//        dd($u_id);
        $price = GoodsModel::find($goods_id)->shop_price;

//        dd($price);

        //将商品存储购物车表 或 Redis
        $info = [
            'u_id'       => $u_id,
            'goods_id'  => $goods_id,
            'goods_num' => 1,
            'cart_price' => $price,
            'add_time'  => time()
        ];

        $id = CartModel::insertGetId($info);
        if($id)
        {
            $response = [
                'errno' => 0,
                'msg'   => 'ok'
            ];
        }else{
            $response = [
                'errno' => 50002,
                'msg'   => '加入购物车失败'
            ];
        }

        return $response;
    }

    /**
     * 小程序购物车列表
     */
    public function cartinfo()
    {
//        echo    __LINE__;die;
        $u_id = $_SERVER['u_id'];
//        dd($u_id);
        $goods = CartModel::where(['u_id'=>$u_id])->get();
        if($goods)      //购物车有商品
        {
            $goods = $goods->toArray();
            foreach($goods as $k=>&$v)
            {
                $g = GoodsModel::find($v['goods_id']);
                $v['goods_name'] = $g->goods_name;
            }
        }else{          //购物车无商品
            $goods = [];
        }

        //echo '<pre>';print_r($goods);echo '</pre>';die;
        $response = [
            'errno' => 0,
            'msg'   => 'ok',
            'data'  => [
                'list'  => $goods
            ]
        ];

        return $response;
    }


    //获取天气
    public function weather(){
        $cityInfo = request()->post('cityInfo');
        if(preg_match('/^[\x7f-\xff]+$/', $cityInfo)){
            $data = CityModel::where(['cityZh'=>$cityInfo])->first();

        }else{
            $data = CityModel::where(['cityEn'=>$cityInfo])->first();
        }
        $url = 'https://tianqiapi.com/api?version=v6&appid=55551934&appsecret=m6ciJOi7&cityid='.$data['id'];
        $client = new Client();
        $res = $client->request('GET',$url,[
            'verify'    => false   //忽略 HTTPS证书 验证
        ]);
        return json_decode($res->getBody(),true);
    }

}
