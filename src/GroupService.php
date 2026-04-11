<?php

namespace Phunky\LaravelMessagingGroups;

use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Phunky\LaravelMessaging\Contracts\Messageable;
use Phunky\LaravelMessaging\Models\Conversation;
use Phunky\LaravelMessaging\Models\Message;
use Phunky\LaravelMessaging\Models\Participant;
use Phunky\LaravelMessaging\Services\MessagingService;
use Phunky\LaravelMessagingGroups\Events\GroupCreated;
use Phunky\LaravelMessagingGroups\Events\GroupOwnershipTransferred;
use Phunky\LaravelMessagingGroups\Events\GroupParticipantInvited;
use Phunky\LaravelMessagingGroups\Events\GroupParticipantLeft;
use Phunky\LaravelMessagingGroups\Events\GroupParticipantRemoved;
use Phunky\LaravelMessagingGroups\Exceptions\GroupException;

class GroupService
{
    public function __construct(
        protected MessagingService $messaging,
    ) {}

    public function create(Messageable $owner, string $name, ?string $avatar = null, ?array $meta = null): Group
    {
        /** @var class-string<Conversation> $conversationClass */
        $conversationClass = config('messaging.models.conversation');
        /** @var class-string<Participant> $participantClass */
        $participantClass = config('messaging.models.participant');
        /** @var class-string<Group> $groupClass */
        $groupClass = config('messaging.models.group');

        return DB::transaction(function () use ($owner, $name, $avatar, $meta, $conversationClass, $participantClass, $groupClass): Group {
            $hash = hash('sha256', 'group:'.Str::uuid()->toString());

            /** @var Conversation $conversation */
            $conversation = $conversationClass::query()->create([
                'participant_hash' => $hash,
            ]);

            $participantClass::query()->create([
                'conversation_id' => $conversation->getKey(),
                'messageable_type' => $owner->getMorphClass(),
                'messageable_id' => $owner->getKey(),
            ]);

            /** @var Group $group */
            $group = $groupClass::query()->create([
                'conversation_id' => $conversation->getKey(),
                'name' => $name,
                'avatar' => $avatar,
                'meta' => $meta,
                'owner_type' => $owner->getMorphClass(),
                'owner_id' => $owner->getKey(),
            ]);

            $group->load('conversation');

            event(new GroupCreated($group, $owner));

            return $group;
        });
    }

    public function invite(Group $group, Messageable $actor, Messageable $invitee): Participant
    {
        $this->assertOwner($group, $actor);

        $conversation = $group->conversation;

        /** @var class-string<Participant> $participantClass */
        $participantClass = config('messaging.models.participant');

        /** @var Participant $participant */
        $participant = $participantClass::query()->firstOrCreate(
            [
                'conversation_id' => $conversation->getKey(),
                'messageable_type' => $invitee->getMorphClass(),
                'messageable_id' => $invitee->getKey(),
            ],
            []
        );

        if ($participant->wasRecentlyCreated) {
            event(new GroupParticipantInvited($group, $participant, $actor));
        }

        return $participant;
    }

    public function remove(Group $group, Messageable $actor, Messageable $target): void
    {
        $this->assertOwner($group, $actor);

        if ($this->isOwner($group, $target)) {
            throw new GroupException('The group owner cannot be removed from the group.');
        }

        $participant = $this->messaging->findParticipantOrFail($group->conversation, $target);
        $participant->delete();

        event(new GroupParticipantRemoved($group, $target, $actor));
    }

    public function transferOwnership(Group $group, Messageable $actor, Messageable $newOwner): Group
    {
        $this->assertOwner($group, $actor);

        $this->messaging->findParticipantOrFail($group->conversation, $newOwner);

        $previousOwner = $group->owner;

        $group->update([
            'owner_type' => $newOwner->getMorphClass(),
            'owner_id' => $newOwner->getKey(),
        ]);

        $group->refresh();

        event(new GroupOwnershipTransferred($group, $previousOwner, $newOwner));

        return $group;
    }

    public function leave(Group $group, Messageable $actor): void
    {
        if ($this->isOwner($group, $actor)) {
            throw new GroupException('The owner cannot leave the group without transferring ownership first.');
        }

        $participant = $this->messaging->findParticipantOrFail($group->conversation, $actor);

        event(new GroupParticipantLeft($group, $actor));

        $participant->delete();
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    public function send(Group $group, Messageable $sender, string $body, array $meta = []): Message
    {
        return $this->messaging->sendMessage($group->conversation, $sender, $body, $meta);
    }

    public function messages(Group $group): CursorPaginator|LengthAwarePaginator
    {
        return $this->messaging->paginateMessages($group->conversation);
    }

    protected function assertOwner(Group $group, Messageable $actor): void
    {
        if (! $this->isOwner($group, $actor)) {
            throw new GroupException('Only the group owner can perform this action.');
        }
    }

    protected function isOwner(Group $group, Messageable $messageable): bool
    {
        return $group->owner_type === $messageable->getMorphClass()
            && (string) $group->owner_id === (string) $messageable->getKey();
    }
}
