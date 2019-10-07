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

Route::get('/{year}/{month}/{key}-{slug}', 'Controller@event')->name('event');

Route::get('/tag/{tag}', 'Controller@tag')->name('tag');

