<?php

Route::get('/ics/events.ics', 'ICSController@index')->name('ics-index');
Route::get('/ics/events', 'ICSController@index')->name('ics-index');
Route::get('/ics/events/preview', 'ICSController@preview')->name('ics-index-preview');

Route::get('/ics/tag/{tag}.ics', 'ICSController@tag')->name('ics-tag');
Route::get('/ics/tag/{tag}', 'ICSController@tag')->name('ics-tag');
Route::get('/ics/tag/{tag}/preview', 'ICSController@preview')->name('ics-tag-preview');

Route::get('/ics/{year}/{month}/{slug}-{key}.ics', 'ICSController@event')->name('ics-event');
Route::get('/ics/{year}/{month}/{slug}-{key}', 'ICSController@event')->name('ics-event');
Route::get('/ics/{year}/{month}/{key}.ics', 'ICSController@event')->name('ics-event-short');
Route::get('/ics/{year}/{month}/{key}', 'ICSController@event')->name('ics-event-short');
