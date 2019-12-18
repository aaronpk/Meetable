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

Route::get('/archive', 'Controller@archive')->name('archive');

Route::get('/{year}/{month}/{slug}-{key}', 'Controller@event')->name('event');
Route::get('/{year}/{month}/{key}', 'Controller@event')->name('event-short');

Route::get('/{year}/{month}', 'Controller@index')->name('month');
Route::get('/{year}', 'Controller@index')->name('year');

Route::get('/tag/{tag}', 'Controller@tag')->name('tag');

Route::get('/webmention', 'WebmentionController@get');
Route::post('/webmention', 'WebmentionController@webmention')->name('webmention');

Route::get('/add-to-google/{event}', 'Controller@add_to_google')->name('add-to-google');

Route::middleware('auth')->group(function(){

    Route::get('/new', 'EventController@new_event')->name('new-event');

    Route::post('/create', 'EventController@create_event')->name('create-event');
    Route::get('/event/{event}', 'EventController@edit_event')->name('edit-event');
    Route::post('/event/{event}/save', 'EventController@save_event')->name('save-event');
    Route::get('/event/{event}/history', 'EventController@event_history')->name('event-history');
    Route::get('/event/{event}/clone', 'EventController@clone_event')->name('clone-event');
    Route::post('/event/{event}/delete', 'EventController@delete_event')->name('delete-event');

    Route::get('/event/{event}/photo', 'EventController@add_event_photo')->name('add-event-photo');
    Route::post('/event/{event}/photo', 'EventController@upload_event_photo')->name('upload-event-photo');
    Route::post('/event/{event}/photo_order', 'EventController@set_photo_order')->name('set-photo-order');

    Route::get('/event/{event}/responses', 'EventController@edit_responses')->name('edit-responses');
    Route::post('/event/{event}/responses', 'EventController@save_responses')->name('save-responses');

    Route::post('/event/{event}/rsvp', 'EventResponseController@save_rsvp')->name('event-rsvp');
    Route::post('/event/{event}/rsvp_delete', 'EventResponseController@delete_rsvp')->name('event-rsvp-delete');

});
