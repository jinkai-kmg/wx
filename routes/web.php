<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});



Route::prefix('/wx')->group(function(){
    Route::get("/index","WxController@index");
    Route::match(['get','post'],"/","WxController@wxEvent");
    Route::any("/token","WxController@getAccessToken");   //获取access_token
    Route::any('/menu',"WxController@menu");      //按钮
    Route::get('/weater',"WxController@weather");      //天气
});


Route::prefix('/api')->group(function(){
    Route::match(['get','post'],'/test',"Api\TestController@test");
    Route::any('/onLogin',"Api\TestController@homeLogin");
    Route::any('/goods',"Api\TestController@goods");
    Route::any('/goodsinfo',"Api\TestController@goodsinfo");
    Route::any('/add_cart',"Api\TestController@add_cart")->middleware('check.token');
    Route::any('/cartinfo',"Api\TestController@cartinfo")->middleware('check.token');
    Route::any('/userLogin',"Api\TestController@userLogin");
});



Route::prefix('/test')->group(function(){
    Route::get("test2","WxController@test2");
    Route::post("test3","WxController@test3");
});
