<?php

use Illuminate\Http\Request;

Route::middleware('auth:api')->group(function(){

    Route::get('/user', 'APIController@user');

    Route::post('/add-response', 'APIController@add_response');
    Route::post('/add-event', 'APIController@add_event');

});

Route::post('/zoom/webhook', 'ZoomController@webhook');
