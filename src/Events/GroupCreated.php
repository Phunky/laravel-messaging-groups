<?php

namespace Phunky\LaravelMessagingGroups\Events;

use Phunky\LaravelMessaging\Contracts\Messageable;
use Phunky\LaravelMessaging\Events\BroadcastableMessagingEvent;
use Phunky\LaravelMessagingGroups\Group;

class GroupCreated extends BroadcastableMessagingEvent
{
    public const BROADCAST_NAME = 'messaging.group.created';

    public function __construct(
        public Group $group,
        public Messageable $owner,
    ) {
        parent::__construct($group->conversation_id);
    }

    public function broadcastAs(): string
    {
        return self::BROADCAST_NAME;
    }

    /**
     * @return array{conversation_id: int|string, group_id: int|string, owner_type: string, owner_id: int|string}
     */
    public function broadcastWith(): array
    {
        return [
            'conversation_id' => $this->group->conversation_id,
            'group_id' => $this->group->getKey(),
            'owner_type' => $this->owner->getMorphClass(),
            'owner_id' => $this->owner->getKey(),
        ];
    }
}
