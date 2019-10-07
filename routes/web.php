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

Route::get('/', 'Controller@index')->name('index');

Route::get('/{year}/{month}/{slug}-{key}', 'Controller@event')->name('event');
Route::get('/{year}/{month}/{key}', 'Controller@event')->name('event');

Route::get('/tag/{tag}', 'Controller@tag')->name('tag');

Route::middleware('auth')->group(function(){

    Route::get('/new', 'EventController@new_event');
    Route::post('/create', 'EventController@create_event')->name('create_event');

});
