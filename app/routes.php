<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the Closure to execute when that URI is requested.
|
*/
Route::get('/a', function () {

	return View::make('web.indexo', array('nav_active'=> 'index'));
});

Route::get('/', array('as'=> '/', 'uses'=> 'IndexController@index'));
Route::get('news', array('as'=> 'news', 'uses'=> 'NewsController@news'));
Route::get('news/detail', array('as'=> 'news.detail', 'uses'=> 'NewsController@newsDetail'));


Route::group(array('prefix'=>'admin/'), function () {
	Route::get('news', array('as'=> 'admin.news', 'uses' => 'AdminNewsController@news'));
	Route::get('news/add', array('as'=> 'admin.news.add', 'uses'=> 'AdminNewsController@newsAdd'));
	Route::get('news/detail', array('as'=> 'admin.news.detail', 'uses'=> 'AdminNewsController@newsDetail'));
	Route::post('news/add/data', array('as'=> 'admin.news.add.data', 'uses'=> 'AdminNewsController@newsAddData'));
	// 图片上传
	Route::post('image/upload', array('as'=>'image.upload', 'uses'=> 'ImageUploadController@imageUpload'));
});

