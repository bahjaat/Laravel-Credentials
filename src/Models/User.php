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

namespace GrahamCampbell\Credentials\Models;

use Carbon\Carbon;
use McCool\LaravelAutoPresenter\PresenterInterface;
use Illuminate\Database\Eloquent\SoftDeletingTrait;
use Cartalyst\Sentry\Users\Eloquent\User as SentryUser;
use GrahamCampbell\Database\Models\Interfaces\BaseModelInterface;
use GrahamCampbell\Database\Models\Common\BaseModelTrait;
use GrahamCampbell\Credentials\Models\Relations\Interfaces\RevisionableInterface;
use GrahamCampbell\Credentials\Models\Relations\Common\RevisionableTrait;

/**
 * This is the user model class.
 *
 * @package    Laravel-Credentials
 * @author     Graham Campbell
 * @copyright  Copyright 2013-2014 Graham Campbell
 * @license    https://github.com/GrahamCampbell/Laravel-Credentials/blob/master/LICENSE.md
 * @link       https://github.com/GrahamCampbell/Laravel-Credentials
 */
class User extends SentryUser implements BaseModelInterface, RevisionableInterface, PresenterInterface
{
    use BaseModelTrait, RevisionableTrait, SoftDeletingTrait;

    /**
     * The table the users are stored in.
     *
     * @var string
     */
    protected $table = 'users';

    /**
     * The model name.
     *
     * @var string
     */
    public static $name = 'user';

    /**
     * The properties on the model that are dates.
     *
     * @var array
     */
    protected $dates = array('deleted_at');

    /**
     * The revisionable columns.
     *
     * @var array
     */
    protected $keepRevisionOf = array('email', 'password', 'activated', 'last_login', 'first_name', 'last_name');

    /**
     * The columns to select when displaying an index.
     *
     * @var array
     */
    public static $index = array('id', 'email', 'first_name', 'last_name');

    /**
     * The max users per page when displaying a paginated index.
     *
     * @var int
     */
    public static $paginate = 20;

    /**
     * The columns to order by when displaying an index.
     *
     * @var string
     */
    public static $order = 'email';

    /**
     * The direction to order by when displaying an index.
     *
     * @var string
     */
    public static $sort = 'asc';

    /**
     * The user validation rules.
     *
     * @var array
     */
    public static $rules = array(
        'first_name'            => 'required|min:2|max:32',
        'last_name'             => 'required|min:2|max:32',
        'email'                 => 'required|min:4|max:32|email',
        'password'              => 'required|min:6|confirmed',
        'password_confirmation' => 'required',
        'activated'             => 'required',
        'activated_at'          => 'required'
    );

    /**
     * Access caches.
     *
     * @var array
     */
    protected $access = array();

    /**
     * Get the recent action history for the user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function revisions()
    {
        return $this->hasMany('GrahamCampbell\Credentials\Models\Revision');
    }

    /**
     * Get the presenter class.
     *
     * @var string
     */
    public function getPresenter()
    {
        return 'GrahamCampbell\Credentials\Presenters\UserPresenter';
    }

    /**
     * Activated at accessor.
     *
     * @param  mixed  $value
     * @return \Carbon\Carbon
     */
    public function getActivatedAtAccessor($value)
    {
        if ($value) {
            return new Carbon($value);
        }

        if ($this->getAttribute('activated')) {
            return $this->getAttribute('created_at');
        }

        return false;
    }

    /**
     * Check a user's access.
     *
     * @param  string|array  $permissions
     * @param  bool  $all
     * @param  bool  $cache
     * @return bool
     */
    public function hasAccess($permissions, $all = true, $cache = true)
    {
        $key = md5(json_encode($permissions).json_encode($all));

        if (!array_key_exists($key, $this->access) || $cache === false) {
            $this->access[$key] = parent::hasAccess($permissions, $all);
        }

        return $this->access[$key];
    }
}
