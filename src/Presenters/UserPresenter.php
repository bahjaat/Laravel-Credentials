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

namespace GrahamCampbell\Credentials\Presenters;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use GrahamCampbell\Credentials\Models\User;
use McCool\LaravelAutoPresenter\BasePresenter;

/**
 * This is the user presenter class.
 *
 * @package    Laravel-Credentials
 * @author     Graham Campbell
 * @copyright  Copyright 2013-2014 Graham Campbell
 * @license    https://github.com/GrahamCampbell/Laravel-Credentials/blob/master/LICENSE.md
 * @link       https://github.com/GrahamCampbell/Laravel-Credentials
 */
class UserPresenter extends BasePresenter
{
    /**
     * Create a new instance.
     *
     * @param  \GrahamCampbell\Credentials\Models\Users  $user
     * @return void
     */
    public function __construct(User $user)
    {
        $this->resource = $user;
    }

    /**
     * Get the user's name.
     *
     * @return string
     */
    public function name()
    {
        return $this->resource->first_name.' '.$this->resource->last_name;
    }

    public function securityHistory()
    {
        $presenter = App::make('McCool\LaravelAutoPresenter\PresenterDecorator');
        $history = $this->resource->revisionHistory()->orderBy('id', 'desc')->take(20)->get();

        $history->each(function ($item) {
            $item->security = true;
        });

        return $presenter->decorate($history);
    }

    public function actionHistory()
    {
        $presenter = App::make('McCool\LaravelAutoPresenter\PresenterDecorator');
        $history = $this->resource->revisions()
            ->where(function ($q) {
                $q->where('revisionable_type', '<>', Config::get('cartalyst/sentry::users.model'))
                    ->where('user_id', '=', $this->id);
            })
            ->orWhere(function ($q) {
                $q->where('revisionable_type', '=', Config::get('cartalyst/sentry::users.model'))
                    ->where('revisionable_id', '<>', $this->id)
                    ->where('user_id', '=', $this->id);
            })
            ->orderBy('id', 'desc')->take(20)->get();

        return $presenter->decorate($history);
    }
}
