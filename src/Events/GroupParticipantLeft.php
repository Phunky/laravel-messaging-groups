<?php

namespace Phunky\LaravelMessagingGroups\Events;

use Phunky\LaravelMessaging\Contracts\Messageable;
use Phunky\LaravelMessaging\Events\BroadcastableMessagingEvent;
use Phunky\LaravelMessagingGroups\Group;

class GroupParticipantLeft extends BroadcastableMessagingEvent
{
    public const BROADCAST_NAME = 'messaging.group.participant_left';

    public function __construct(
        public Group $group,
        public Messageable $participant,
    ) {
        parent::__construct($group->conversation_id);
    }

    public function broadcastAs(): string
    {
        return self::BROADCAST_NAME;
    }

    /**
     * @return array{conversation_id: int|string, group_id: int|string, participant_type: string, participant_id: int|string}
     */
    public function broadcastWith(): array
    {
        return [
            'conversation_id' => $this->group->conversation_id,
            'group_id' => $this->group->getKey(),
            'participant_type' => $this->participant->getMorphClass(),
            'participant_id' => $this->participant->getKey(),
        ];
    }
}
