<?php

use Illuminate\Http\Request;

Route::middleware('auth:api')->group(function(){

    Route::get('/user', function(Request $request) {
        return $request->user();
    });

    Route::post('/add-response', 'APIController@add_response');

});

