<?php

namespace Phunky\LaravelMessagingGroups;

use Illuminate\Contracts\Foundation\Application;
use Phunky\LaravelMessaging\Contracts\MessagingExtension;
use Phunky\LaravelMessaging\Services\MessagingService;

class GroupsExtension implements MessagingExtension
{
    public function register(Application $app): void
    {
        $app->singleton(GroupService::class, fn (Application $app): GroupService => new GroupService(
            $app->make(MessagingService::class)
        ));
    }

    public function boot(Application $app): void
    {
        $migrationDir = dirname(__DIR__).'/database/migrations';

        $app->afterResolving('migrator', function ($migrator) use ($migrationDir): void {
            $migrator->path($migrationDir);
        });
    }
}
