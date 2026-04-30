<?php

namespace Phunky\LaravelMessagingGroups\Events;

use Phunky\LaravelMessaging\Contracts\Messageable;
use Phunky\LaravelMessaging\Events\BroadcastableMessagingEvent;
use Phunky\LaravelMessaging\Models\Participant;
use Phunky\LaravelMessagingGroups\Group;

class GroupParticipantInvited extends BroadcastableMessagingEvent
{
    public const BROADCAST_NAME = 'messaging.group.participant_invited';

    public function __construct(
        public Group $group,
        public Participant $participant,
        public Messageable $invitedBy,
    ) {
        parent::__construct($group->conversation_id);
    }

    public function broadcastAs(): string
    {
        return self::BROADCAST_NAME;
    }

    /**
     * @return array{conversation_id: int|string, group_id: int|string, participant_id: int|string, invited_by_type: string, invited_by_id: int|string}
     */
    public function broadcastWith(): array
    {
        return [
            'conversation_id' => $this->group->conversation_id,
            'group_id' => $this->group->getKey(),
            'participant_id' => $this->participant->getKey(),
            'invited_by_type' => $this->invitedBy->getMorphClass(),
            'invited_by_id' => $this->invitedBy->getKey(),
        ];
    }
}
