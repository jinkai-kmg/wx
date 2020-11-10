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
//    Route::get("/","TestController@index");
    Route::match(['get','post'],"/","TestController@wxEvent");
    Route::any("/token","TestController@getAccessToken");   //获取access_token
    Route::any('menu',"TestController@menu");      //按钮
});



Route::prefix('/test')->group(function(){
    Route::get("test2","TestController@test2");
    Route::post("test3","TestController@test3");
});
