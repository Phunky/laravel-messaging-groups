<?php

namespace Phunky\LaravelMessagingGroups\Events;

use Phunky\LaravelMessaging\Contracts\Messageable;
use Phunky\LaravelMessaging\Events\BroadcastableMessagingEvent;
use Phunky\LaravelMessagingGroups\Group;

class GroupParticipantRemoved extends BroadcastableMessagingEvent
{
    public const BROADCAST_NAME = 'messaging.group.participant_removed';

    public function __construct(
        public Group $group,
        public Messageable $removed,
        public Messageable $removedBy,
    ) {
        parent::__construct($group->conversation_id);
    }

    public function broadcastAs(): string
    {
        return self::BROADCAST_NAME;
    }

    /**
     * @return array{conversation_id: int|string, group_id: int|string, removed_type: string, removed_id: int|string, removed_by_type: string, removed_by_id: int|string}
     */
    public function broadcastWith(): array
    {
        return [
            'conversation_id' => $this->group->conversation_id,
            'group_id' => $this->group->getKey(),
            'removed_type' => $this->removed->getMorphClass(),
            'removed_id' => $this->removed->getKey(),
            'removed_by_type' => $this->removedBy->getMorphClass(),
            'removed_by_id' => $this->removedBy->getKey(),
        ];
    }
}
