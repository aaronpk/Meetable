<?php
use Laragear\WebAuthn\WebAuthn;

WebAuthn::routes();

// Check whether setup has been completed and define installer routes if not
if(defined('MEETABLE_SETUP')):

Route::get('/', 'Setup\Controller@setup')->name('setup');
Route::get('/setup/db', 'Setup\Controller@database')->name('setup.database');
Route::post('/setup/db-test', 'Setup\Controller@test_database')->name('setup.test-database');
Route::get('/setup/app-settings', 'Setup\Controller@app_settings')->name('setup.app-settings');
Route::post('/setup/app-settings', 'Setup\Controller@save_app_settings')->name('setup.save-app-settings');
Route::get('/setup/auth-method', 'Setup\Controller@auth_method')->name('setup.auth-method');
Route::post('/setup/auth-method', 'Setup\Controller@save_auth_method')->name('setup.save-auth-method');
Route::post('/setup/register-heroku-app', 'Setup\Controller@register_heroku_app')->name('setup.register-heroku-app');
Route::get('/setup/auth-settings', 'Setup\Controller@auth_settings')->name('setup.auth-settings');
Route::post('/setup/auth-settings', 'Setup\Controller@save_auth_settings')->name('setup.save-auth-settings');
Route::get('/setup/save', 'Setup\Controller@save_config')->name('setup.save-config');
Route::get('/setup/push-heroku-config', 'Setup\Controller@push_heroku_config')->name('setup.push-heroku-config');
Route::get('/setup/database', 'Setup\Controller@create_database_error')->name('setup.create-database');
Route::get('/setup/heroku-complete', 'Setup\Controller@heroku_config_complete')->name('setup.heroku-config-complete');
Route::get('/setup/heroku-in-progress', 'Setup\Controller@heroku_in_progress')->name('setup.heroku-in-progres');

else:

Route::get('/', 'Controller@index')->name('index');
Route::get('/custom-css', 'Controller@custom_css');

######
## SETUP ROUTES
## If they hit reload after setup, send them back to the home page
Route::get('/setup/save', 'Setup\Controller@redirect_after_complete');
## Create the database here
Route::get('/setup/database', 'Setup\Controller@create_database')->name('setup.create-database');
Route::get('/setup/heroku-complete', 'Setup\Controller@heroku_config_complete')->name('setup.heroku-config-complete');
Route::get('/setup/heroku-in-progress', 'Setup\Controller@heroku_finished')->name('setup.heroku-in-progres');
######


Route::middleware('slashes:remove')->group(function(){
    Route::get('/manifest.json', 'Controller@manifest_json');

    Route::get('/archive', 'Controller@archive')->name('archive');

    Route::get('/{year}/{month}/{slug}-{key}', 'Controller@event')->name('event');
    Route::get('/{year}/{month}/{key}', 'Controller@event')->name('event-short');

    Route::get('/{year}/{month}/{day}', 'Controller@index')->name('day');
    Route::get('/{year}/{month}', 'Controller@index')->name('month');
    Route::get('/{year}', 'Controller@index')->name('year');

    Route::get('/{year}/{tag}', 'Controller@year_tag');

    Route::get('/{year}/{month}/{partial_slug}', 'Controller@find_matching_events');

    Route::get('/event/{key}.json', 'Controller@event_json')->name('event-json');

    Route::get('/tag/{tag}', 'Controller@tag')->name('tag');
    Route::get('/tag/{tag}/archive', 'Controller@tag_archive')->name('tag-archive');
    Route::redirect('/tag', '/tags', 301);
    Route::get('/tags', 'Controller@tags')->name('tags');

    Route::get('/local-time', 'Controller@local_time')->name('local_time');

    Route::get('/webmention', 'WebmentionController@get');
    Route::post('/webmention', 'WebmentionController@webmention')->name('webmention');

    Route::get('/add-to-google/{event}', 'Controller@add_to_google')->name('add-to-google');

    Route::get('/login', 'Auth\AuthController@login')->name('login');
    Route::get('/logout', 'Auth\AuthController@logout')->name('logout');
    Route::post('/auth/create-user', 'Auth\AuthController@create_user')->name('create-user');
    Route::get('/auth/github', 'Auth\GitHubController@callback')->name('github-oauth-redirect');
    Route::get('/auth/heroku', 'Auth\HerokuController@callback')->name('heroku-oauth-redirect');
    Route::get('/auth/oidc', 'Auth\OIDCController@callback')->name('oidc-redirect');
    Route::get('/auth/oidc/initiate', 'Auth\OIDCController@initiate')->name('oidc-initiate');
    Route::get('/auth/oidc/logout', 'Auth\OIDCController@logout')->name('oidc-logout');

    Route::get('/{key}', 'Controller@event_shorturl');
});

Route::middleware('auth')->middleware('slashes:remove')->group(function(){

    Route::get('/new', 'EventController@new_event')->name('new-event');
    Route::get('/import', 'EventController@import_event')->name('import-event');
    Route::get('/events/unlisted', 'EventController@unlisted_events')->name('unlisted');

    Route::post('/create', 'EventController@create_event')->name('create-event');
    Route::get('/event/{event}', 'EventController@edit_event')->name('edit-event');
    Route::post('/event/{event}/save', 'EventController@save_event')->name('save-event');
    Route::get('/event/{event}/history', 'EventController@revision_history')->name('revision-history');
    Route::get('/event/{event}/history/{revision}', 'EventController@view_revision')->name('view-revision');
    Route::get('/event/{event}/history/{revision}/diff', 'EventController@view_revision_diff')->name('view-revision-diff');
    Route::get('/event/{event}/clone', 'EventController@clone_event')->name('clone-event');
    Route::post('/event/{event}/delete', 'EventController@delete_event')->name('delete-event');

    Route::post('/event/timezone', 'EventController@get_timezone')->name('get-timezone');

    Route::get('/event/{event}/photo', 'EventController@add_event_photo')->name('add-event-photo');
    Route::post('/event/{event}/photo', 'EventController@upload_event_photo')->name('upload-event-photo');
    Route::post('/event/{event}/photo_order', 'EventController@set_photo_order')->name('set-photo-order');
    Route::post('/event/cover_image', 'EventController@upload_event_cover_image')->name('upload-event-cover-image');
    Route::get('/event/{event}/registration', 'EventController@edit_registration')->name('edit-registration');

    Route::get('/event/{event}/responses', 'ResponseController@edit_responses')->name('edit-responses');
    Route::post('/event/{event}/responses/{response}/delete', 'ResponseController@delete_response')->name('delete-response');
    Route::get('/event/{event}/responses/{response}.json', 'ResponseController@get_response_details')->name('get-response-details');
    Route::post('/event/{event}/responses/save_alt_text', 'ResponseController@save_alt_text')->name('save-alt-text');

    Route::get('/moderate', 'ResponseController@moderate_all_responses')->name('moderate-all-responses');
    Route::get('/event/{event}/moderate', 'ResponseController@moderate_responses')->name('moderate-responses');
    Route::post('/event/{event}/moderate/{response}/approve', 'ResponseController@approve_response')->name('approve-response');

    Route::post('/event/{event}/rsvp', 'EventResponseController@save_rsvp')->name('event-rsvp');
    Route::post('/event/{event}/rsvp_delete', 'EventResponseController@delete_rsvp')->name('event-rsvp-delete');

    Route::middleware('can:manage-site')->group(function(){
        Route::get('/settings', 'SettingsController@get')->name('settings');
        Route::post('/settings/save', 'SettingsController@post')->name('settings-save');
    });

});

endif; // setup
