<?php

namespace Phunky\LaravelMessagingGroups\Events;

use Phunky\LaravelMessaging\Contracts\Messageable;
use Phunky\LaravelMessaging\Events\BroadcastableMessagingEvent;
use Phunky\LaravelMessagingGroups\Group;

class GroupCreated extends BroadcastableMessagingEvent
{
    public function __construct(
        public Group $group,
        public Messageable $owner,
    ) {
        parent::__construct($group->conversation_id);
    }

    public function broadcastAs(): string
    {
        return 'messaging.group.created';
    }
}
