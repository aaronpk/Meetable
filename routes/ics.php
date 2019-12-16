<?php

Route::get('/ics/events.ics', 'ICSController@index')->name('ics_index');

Route::get('/ics/tag/{tag}.ics', 'ICSController@tag')->name('ics_tag');
