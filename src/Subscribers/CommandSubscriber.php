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

namespace GrahamCampbell\Credentials\Subscribers;

use Illuminate\Console\Command;
use Illuminate\Events\Dispatcher;

/**
 * This is the command subscriber class.
 *
 * @package    Laravel-Credentials
 * @author     Graham Campbell
 * @copyright  Copyright 2013-2014 Graham Campbell
 * @license    https://github.com/GrahamCampbell/Laravel-Credentials/blob/master/LICENSE.md
 * @link       https://github.com/GrahamCampbell/Laravel-Credentials
 */
class CommandSubscriber
{
    /**
     * The forced flag.
     *
     * @var bool
     */
    protected $force;

    /**
     * Create a new instance.
     *
     * @param  bool  $force
     * @return void
     */
    public function __construct($force)
    {
        $this->force = $force;
    }

    /**
     * Register the listeners for the subscriber.
     *
     * @param  \Illuminate\Events\Dispatcher  $events
     * @return array
     */
    public function subscribe(Dispatcher $events)
    {
        $events->listen(
            'command.runmigrations',
            'GrahamCampbell\Credentials\Subscribers\CommandSubscriber@onRunMigrations',
            8
        );
    }

    /**
     * Handle a command.runmigrations event.
     *
     * @param  \Illuminate\Console\Command  $command
     * @return void
     */
    public function onRunMigrations(Command $command)
    {
        if ($this->force) {
            $command->call('migrate', array('--package' => 'cartalyst/sentry', '--force' => true));
            $command->call('migrate', array('--package' => 'graham-campbell/credentials', '--force' => true));
        } else {
            $command->call('migrate', array('--package' => 'cartalyst/sentry'));
            $command->call('migrate', array('--package' => 'graham-campbell/credentials'));
        }
    }
}
