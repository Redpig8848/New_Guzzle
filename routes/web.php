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

Route::get("href","GuzzleController@index");


Route::get("getarticle","ArticleController@index");
Route::get("etlink","ZuQiuBa\linkController@index");
Route::get("etwen","ZuQiuBa\WenController@index");