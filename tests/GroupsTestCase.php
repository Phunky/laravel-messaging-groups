<?php

namespace Phunky\LaravelMessagingGroups\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase as Orchestra;
use Phunky\LaravelMessaging\MessagingServiceProvider;
use Phunky\LaravelMessagingGroups\Group;
use Phunky\LaravelMessagingGroups\GroupsExtension;

abstract class GroupsTestCase extends Orchestra
{
    use RefreshDatabase;

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);

        $app['config']->set('messaging.models.group', Group::class);

        $app['config']->set('messaging.extensions', [
            GroupsExtension::class,
        ]);
    }

    protected function getPackageProviders($app): array
    {
        return [
            MessagingServiceProvider::class,
        ];
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');
    }
}
