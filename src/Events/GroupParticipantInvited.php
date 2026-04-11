<?php

namespace Phunky\LaravelMessagingGroups\Events;

use Phunky\LaravelMessaging\Contracts\Messageable;
use Phunky\LaravelMessaging\Events\BroadcastableMessagingEvent;
use Phunky\LaravelMessaging\Models\Participant;
use Phunky\LaravelMessagingGroups\Group;

class GroupParticipantInvited extends BroadcastableMessagingEvent
{
    public function __construct(
        public Group $group,
        public Participant $participant,
        public Messageable $invitedBy,
    ) {
        parent::__construct($group->conversation_id);
    }

    public function broadcastAs(): string
    {
        return 'messaging.group.participant_invited';
    }
}
