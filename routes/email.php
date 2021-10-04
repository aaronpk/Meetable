<?php

Route::post('/email/sendgrid', 'InboundEmailController@parse_from_sendgrid');
