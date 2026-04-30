<?php

namespace Phunky\LaravelMessagingGroups\Events;

use Phunky\LaravelMessaging\Contracts\Messageable;
use Phunky\LaravelMessaging\Events\BroadcastableMessagingEvent;
use Phunky\LaravelMessagingGroups\Group;

class GroupOwnershipTransferred extends BroadcastableMessagingEvent
{
    public const BROADCAST_NAME = 'messaging.group.ownership_transferred';

    public function __construct(
        public Group $group,
        public Messageable $previousOwner,
        public Messageable $newOwner,
    ) {
        parent::__construct($group->conversation_id);
    }

    public function broadcastAs(): string
    {
        return self::BROADCAST_NAME;
    }

    /**
     * @return array{conversation_id: int|string, group_id: int|string, previous_owner_type: string, previous_owner_id: int|string, new_owner_type: string, new_owner_id: int|string}
     */
    public function broadcastWith(): array
    {
        return [
            'conversation_id' => $this->group->conversation_id,
            'group_id' => $this->group->getKey(),
            'previous_owner_type' => $this->previousOwner->getMorphClass(),
            'previous_owner_id' => $this->previousOwner->getKey(),
            'new_owner_type' => $this->newOwner->getMorphClass(),
            'new_owner_id' => $this->newOwner->getKey(),
        ];
    }
}
