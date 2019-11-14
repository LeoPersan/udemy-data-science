<?php

Route::get('/', function () {return view('welcome');});

Auth::routes();
Route::get('/login/{drive}', 'Auth\LoginController@redirectToProvider')->name('loginDrive');
Route::get('/login/{drive}/callback', 'Auth\LoginController@handleProviderCallback');

Route::group(['middleware' => ['auth']], function () {
    Route::get('/home', 'HomeController@index')->name('home');
});
