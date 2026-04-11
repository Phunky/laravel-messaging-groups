<?php

namespace Phunky\LaravelMessagingGroups\Events;

use Phunky\LaravelMessaging\Contracts\Messageable;
use Phunky\LaravelMessaging\Events\BroadcastableMessagingEvent;
use Phunky\LaravelMessagingGroups\Group;

class GroupParticipantRemoved extends BroadcastableMessagingEvent
{
    public function __construct(
        public Group $group,
        public Messageable $removed,
        public Messageable $removedBy,
    ) {
        parent::__construct($group->conversation_id);
    }

    public function broadcastAs(): string
    {
        return 'messaging.group.participant_removed';
    }
}
