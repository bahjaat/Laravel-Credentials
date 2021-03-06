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

namespace GrahamCampbell\Credentials\Controllers;

use DateTime;
use Illuminate\Support\Str;
use Illuminate\View\Factory;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\HTML;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\URL;
use GrahamCampbell\Binput\Binput;
use GrahamCampbell\Credentials\Credentials;
use GrahamCampbell\Credentials\Providers\UserProvider;
use GrahamCampbell\Credentials\Facades\GroupProvider;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * This is the user controller class.
 *
 * @package    Laravel-Credentials
 * @author     Graham Campbell
 * @copyright  Copyright 2013-2014 Graham Campbell
 * @license    https://github.com/GrahamCampbell/Laravel-Credentials/blob/master/LICENSE.md
 * @link       https://github.com/GrahamCampbell/Laravel-Credentials
 */
class UserController extends BaseController
{
    /**
     * Create a new instance.
     *
     * @param  \GrahamCampbell\Credentials\Credentials  $credentials
     * @param  \GrahamCampbell\Binput\Binput  $binput
     * @param  \GrahamCampbell\Credentials\Providers\UserProvider  $userprovider
     * @param  \Illuminate\View\Factory  $view
     * @return void
     */
    public function __construct(Credentials $credentials, Binput $binput, UserProvider $userprovider, Factory $view)
    {
        $this->binput = $binput;
        $this->userprovider = $userprovider;
        $this->view = $view;

        $this->setPermissions(array(
            'index'   => 'mod',
            'create'  => 'admin',
            'store'   => 'admin',
            'show'    => 'mod',
            'edit'    => 'admin',
            'update'  => 'admin',
            'suspend' => 'mod',
            'reset'   => 'admin',
            'resend'  => 'admin',
            'destroy' => 'admin',
        ));

        parent::__construct($credentials, $binput, $userprovider, $view);
    }

    /**
     * Display a listing of the users.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $users = $this->userprovider->paginate();
        $links = $this->userprovider->links();

        return $this->view->make('graham-campbell/credentials::users.index', array('users' => $users, 'links' => $links));
    }

    /**
     * Show the form for creating a new user.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $groups = GroupProvider::index();

        return $this->view->make('graham-campbell/credentials::users.create', array('groups' => $groups));
    }

    /**
     * Store a new user.
     *
     * @return \Illuminate\Http\Response
     */
    public function store()
    {
        $password = Str::random();

        $input = array_merge($this->binput->only(array('first_name', 'last_name', 'email')), array(
            'password'     => $password,
            'activated'    => true,
            'activated_at' => new DateTime
        ));

        $rules = $this->userprovider->rules(array_keys($input));
        $rules['password'] = 'required|min:6';

        $val = $this->userprovider->validate($input, $rules, true);
        if ($val->fails()) {
            return Redirect::route('users.create')->withInput()->withErrors($val->errors());
        }

        try {
            $user = $this->userprovider->create($input);

            $groups = GroupProvider::index();
            foreach ($groups as $group) {
                if ($this->binput->get('group_'.$group->id) === 'on') {
                    $user->addGroup($group);
                }
            }

            $mail = array(
                'url'      => URL::to(Config::get('graham-campbell/core::home', '/')),
                'password' => $password,
                'email'    => $user->getLogin(),
                'subject'  => Config::get('platform.name').' - New Account Information'
            );

            Mail::queue('graham-campbell/credentials::emails.newuser', $mail, function($message) use ($mail) {
                $message->to($mail['email'])->subject($mail['subject']);
            });

            return Redirect::route('users.show', array('users' => $user->id))
                ->with('success', 'The user has been created successfully. Their password has been emailed to them.');
        } catch (\Cartalyst\Sentry\Users\UserExistsException $e) {
            return Redirect::route('users.create')->withInput()->withErrors($val->errors())
                ->with('error', 'That email address is taken.');
        }
    }

    /**
     * Show the specified user.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $user = $this->userprovider->find($id);
        $this->checkUser($user);

        if ($user->activated_at) {
            $activated = HTML::ago($user->activated_at);
        } else {
            if ($this->credentials->hasAccess('admin') && Config::get('graham-campbell/credentials::activation')) {
                $activated = 'No - <a href="#resend_user" data-toggle="modal" data-target="#resend_user">Resend Email</a>';
            } else {
                $activated = 'Not Activated';
            }
        }

        if ($this->credentials->getThrottleProvider()->findByUserId($id)->isSuspended()) {
            $suspended = 'Currently Suspended';
        } else {
            $suspended = 'Not Suspended';
        }

        $groups = $user->getGroups();
        if (count($groups) >= 1) {
            $data = array();
            foreach ($groups as $group) {
                $data[] = $group->name;
            }
            $groups = implode(', ', $data);
        } else {
            $groups = 'No Group Memberships';
        }

        return $this->view->make('graham-campbell/credentials::users.show', array('user' => $user, 'groups' => $groups, 'activated' => $activated, 'suspended' => $suspended));
    }

    /**
     * Show the form for editing the specified user.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $user = $this->userprovider->find($id);
        $this->checkUser($user);

        $groups = GroupProvider::index();

        return $this->view->make('graham-campbell/credentials::users.edit', array('user' => $user, 'groups' => $groups));
    }

    /**
     * Update an existing user.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update($id)
    {
        $input = $this->binput->only(array('first_name', 'last_name', 'email'));

        $val = $this->userprovider->validate($input, array_keys($input));
        if ($val->fails()) {
            return Redirect::route('users.edit', array('users' => $id))->withInput()->withErrors($val->errors());
        }

        $user = $this->userprovider->find($id);
        $this->checkUser($user);

        $user->update($input);

        $groups = GroupProvider::index();

        foreach ($groups as $group) {
            if ($user->inGroup($group)) {
                if ($this->binput->get('group_'.$group->id) !== 'on') {
                    $user->removeGroup($group);
                }
            } else {
                if ($this->binput->get('group_'.$group->id) === 'on') {
                    $user->addGroup($group);
                }
            }
        }

        return Redirect::route('users.show', array('users' => $user->id))
            ->with('success', 'The user has been updated successfully.');
    }

    /**
     * Suspend an existing user.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function suspend($id)
    {
        try {
            $throttle = $this->credentials->getThrottleProvider()->findByUserId($id);
            $throttle->suspend();
        } catch (\Cartalyst\Sentry\Users\UserNotFoundException $e) {
            throw new NotFoundHttpException('User Not Found', $e);
        } catch (\Cartalyst\Sentry\Throttling\UserSuspendedException $e) {
            $time = $throttle->getSuspensionTime();
            return Redirect::route('users.suspend', array('users' => $user->id))->withInput()->withErrors($val->errors())
                ->with('error', "This user is already suspended for $time minutes.");
        } catch (\Cartalyst\Sentry\Throttling\UserBannedException $e) {
            return Redirect::route('users.suspend', array('users' => $user->id))->withInput()->withErrors($val->errors())
                ->with('error', 'This user has already been banned.');
        }

        return Redirect::route('users.show', array('users' => $id))
            ->with('success', 'The user has been suspended successfully.');
    }

    /**
     * Reset the password of an existing user.
     *
     * @return \Illuminate\Http\Response
     */
    public function reset($id)
    {
        $password = Str::random();

        $input = array(
            'password' => $password,
        );

        $rules = array(
            'password' => 'required|min:6',
        );

        $val = $this->userprovider->validate($input, $rules, true);
        if ($val->fails()) {
            return Redirect::route('users.show', array('users' => $id))->withErrors($val->errors());
        }

        $user = $this->userprovider->find($id);
        $this->checkUser($user);

        $user->update($input);

        $mail = array(
            'password' => $password,
            'email' => $user->getLogin(),
            'subject' => Config::get('platform.name').' - New Password Information'
        );

        Mail::queue('graham-campbell/credentials::emails.password', $mail, function($message) use ($mail) {
            $message->to($mail['email'])->subject($mail['subject']);
        });

        return Redirect::route('users.show', array('users' => $id))
            ->with('success', 'The user\'s password has been successfully reset. Their new password has been emailed to them.');
    }

    /**
     * Resend the activation email of an existing user.
     *
     * @return \Illuminate\Http\Response
     */
    public function resend($id)
    {
        $user = $this->userprovider->find($id);
        $this->checkUser($user);

        if ($user->activated) {
            return Redirect::route('account.resend')->withInput()
                ->with('error', 'That user is already activated.');
        }

        $mail = array(
            'url'     => URL::to(Config::get('graham-campbell/core::home', '/')),
            'link'    => URL::route('account.activate', array('id' => $user->id, 'code' => $user->getActivationCode())),
            'email'   => $user->getLogin(),
            'subject' => Config::get('platform.name').' - Activation'
        );

        Mail::queue('graham-campbell/credentials::emails.resend', $mail, function($message) use ($mail) {
            $message->to($mail['email'])->subject($mail['subject']);
        });

        return Redirect::route('users.show', array('users' => $id))
            ->with('success', 'The user\'s activation email has been successfully sent.');
    }

    /**
     * Delete an existing user.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $user = $this->userprovider->find($id);
        $this->checkUser($user);

        try {
            $user->delete();
        } catch (\Exception $e) {
            return Redirect::route('users.show', array('users' => $id))
                ->with('error', 'We were unable to delete the account.');
        }

        return Redirect::route('users.index')
            ->with('success', 'The user has been deleted successfully.');
    }

    /**
     * Check the user model.
     *
     * @param  mixed  $user
     * @return void
     */
    protected function checkUser($user)
    {
        if (!$user) {
            throw new NotFoundHttpException('User Not Found');
        }
    }
}
