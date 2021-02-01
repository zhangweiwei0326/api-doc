<?php
use think\facade\Route;

Route::get('doc/index', 'Weiwei\ApiDoc\DocController@index');
Route::get('doc/search', 'Weiwei\ApiDoc\DocController@search');
Route::get('doc/list', "Weiwei\ApiDoc\DocController@getList");
Route::get('doc/pass', "Weiwei\ApiDoc\DocController@pass");
Route::post('doc/login', "Weiwei\ApiDoc\DocController@login");
Route::get('doc/info', "Weiwei\ApiDoc\DocController@getInfo");
Route::any('doc/debug', "Weiwei\ApiDoc\DocController@debug");