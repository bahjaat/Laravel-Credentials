<?php

/**
 * This file is part of Laravel Credentials by Graham Campbell.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

// send users to the profile page
Route::get('account', array('as' => 'account', function () {
    Session::flash('', ''); // work around laravel bug if there is no session yet
    Session::reflash();
    return Redirect::route('account.profile');
}));


// account routes
Route::get('account/history', array(
    'as' => 'account.history',
    'uses' => 'GrahamCampbell\Credentials\Controllers\AccountController@getHistory'
));
Route::get('account/profile', array(
    'as' => 'account.profile',
    'uses' => 'GrahamCampbell\Credentials\Controllers\AccountController@getProfile'
));
Route::delete('account/profile', array(
    'as' => 'account.profile.delete',
    'uses' => 'GrahamCampbell\Credentials\Controllers\AccountController@deleteProfile'
));
Route::patch('account/details', array(
    'as' => 'account.details.patch',
    'uses' => 'GrahamCampbell\Credentials\Controllers\AccountController@patchDetails'
));
Route::patch('account/password', array(
    'as' => 'account.password.patch',
    'uses' => 'GrahamCampbell\Credentials\Controllers\AccountController@patchPassword'
));


// registration routes
if (Config::get('graham-campbell/credentials::regallowed')) {
    Route::get('account/register', array(
        'as' => 'account.register',
        'uses' => 'GrahamCampbell\Credentials\Controllers\RegistrationController@getRegister'
    ));
    Route::post('account/register', array(
        'as' => 'account.register.post',
        'uses' => 'GrahamCampbell\Credentials\Controllers\RegistrationController@postRegister'
    ));
}


// activation routes
if (Config::get('graham-campbell/credentials::activation')) {
    Route::get('account/activate/{id}/{code}', array(
        'as' => 'account.activate',
        'uses' => 'GrahamCampbell\Credentials\Controllers\ActivationController@getActivate'
    ));
    Route::get('account/resend', array(
        'as' => 'account.resend',
        'uses' => 'GrahamCampbell\Credentials\Controllers\ActivationController@getResend'
    ));
    Route::post('account/resend', array(
        'as' => 'account.resend.post',
        'uses' => 'GrahamCampbell\Credentials\Controllers\ActivationController@postResend'
    ));
}


// reset routes
Route::get('account/reset', array(
    'as' => 'account.reset',
    'uses' => 'GrahamCampbell\Credentials\Controllers\ResetController@getReset'
));
Route::post('account/reset', array(
    'as' => 'account.reset.post',
    'uses' => 'GrahamCampbell\Credentials\Controllers\ResetController@postReset'
));
Route::get('account/password/{id}/{code}', array(
    'as' => 'account.password',
    'uses' => 'GrahamCampbell\Credentials\Controllers\ResetController@getPassword'
));


// login routes
Route::get('account/login', array(
    'as' => 'account.login',
    'uses' => 'GrahamCampbell\Credentials\Controllers\LoginController@getLogin'
));
Route::post('account/login', array(
    'as' => 'account.login.post',
    'uses' => 'GrahamCampbell\Credentials\Controllers\LoginController@postLogin'
));
Route::get('account/logout', array(
    'as' => 'account.logout',
    'uses' => 'GrahamCampbell\Credentials\Controllers\LoginController@getLogout'
));


// user routes
Route::resource('users', 'GrahamCampbell\Credentials\Controllers\UserController');
Route::post('users/{users}/suspend', array(
    'as' => 'users.suspend',
    'uses' => 'GrahamCampbell\Credentials\Controllers\UserController@suspend'
));
Route::post('users/{users}/reset', array(
    'as' => 'users.reset',
    'uses' => 'GrahamCampbell\Credentials\Controllers\UserController@reset'
));
if (Config::get('graham-campbell/credentials::activation')) {
    Route::post('users/{users}/resend', array(
        'as' => 'users.resend',
        'uses' => 'GrahamCampbell\Credentials\Controllers\UserController@resend'
    ));
}
