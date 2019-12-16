<?php

Route::get('/ics/events.ics', 'ICSController@index')->name('ics-index');

Route::get('/ics/tag/{tag}.ics', 'ICSController@tag')->name('ics-tag');

Route::get('/ics/{year}/{month}/{slug}-{key}.ics', 'ICSController@event')->name('ics-event');
Route::get('/ics/{year}/{month}/{key}.ics', 'ICSController@event')->name('ics-event-short');
