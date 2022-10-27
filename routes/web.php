<?php
/* Webhook */
Route::any('/yclients/webhook', 'BotController@webhookYclients');
Route::post('/w/sendMessage', 'BotController@sendMessageFromWebsie');
Route::any('/wa/webhook', 'BotController@waWebhook');
/* Cron */
Route::get('/AutoConfirmation', 'BotController@AutoConfirmation');
Route::get('/AutoNps', 'BotController@AutoNps');
Route::get('/visit', 'BotController@visitReminder');
Route::get('/alt', 'BotController@alternativeYclientsWebhook');
Route::get('/g{id}', 'BotController@redirectLink');
Route::get('/del', 'BotController@del');
Route::get('/clearMessagesQueue', 'BotController@clearMessagesQueue');
Route::get('/thelashes-redirect/{eventId}-{recordId}', 'BotController@theLashesSmsRedirect');
Route::get('/thelashes-redirect/{hash}', 'BotController@theLashesSmsRedirectWithHash');
Route::any('/paid/', 'BotController@webhookPaid');



//Route::get('/', function () {
//    return view('welcome');
//});
Route::get('/no-access', function () {
    return view('noAccess');
})->name('no-access');

Auth::routes();

Route::get('/home', 'HomeController@index')->name('home');
Route::post('/home', 'HomeController@saveConfig');
Route::post('/saveConnectYclients', 'HomeController@saveConnectYclients');

Route::get('/wa', 'WaController@wa');
Route::get('/reload', 'WaController@reload');
Route::get('/s', 'WaController@send');
Route::get('/settings', 'TestController@settings');
Route::get('/showMessagesQueue', 'TestController@showMessagesQueue');
Route::get('/showActionsQueue', 'TestController@showActionsQueue');
Route::get('/clearMessagesAndActionsQueue', 'TestController@clearMessagesAndActionsQueue');
Route::get('/sorry', 'TestController@sorry');

Route::get('/AutoNotification', 'BotController@AutoNotification');



Route::get('/setWebhook', 'BotController@setWebhook');
Route::get('/qrСode', 'BotController@qrСode');
Route::get('/checkWA', 'BotController@checkWA');
Route::get('/cron', 'BotController@cron');
Route::get('/test', 'TestController@test');
Route::get('/phone/{phone}', 'BotController@testPhone');
//Route::get('/null', 'BotController@null');
//Route::get('/location', 'BotController@location');
//Route::get('/an', 'BotController@an');




