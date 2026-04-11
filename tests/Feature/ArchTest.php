<?php

use Phunky\LaravelMessaging\Events\BroadcastableMessagingEvent;

arch('group extension events extend BroadcastableMessagingEvent')
    ->expect('Phunky\LaravelMessagingGroups\Events')
    ->classes()
    ->toExtend(BroadcastableMessagingEvent::class);
