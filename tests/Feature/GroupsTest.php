<?php

use Illuminate\Support\Facades\Event;
use Phunky\LaravelMessaging\Models\Conversation;
use Phunky\LaravelMessaging\Models\Participant;
use Phunky\LaravelMessagingGroups\Events\GroupCreated;
use Phunky\LaravelMessagingGroups\Events\GroupOwnershipTransferred;
use Phunky\LaravelMessagingGroups\Events\GroupParticipantInvited;
use Phunky\LaravelMessagingGroups\Events\GroupParticipantLeft;
use Phunky\LaravelMessagingGroups\Events\GroupParticipantRemoved;
use Phunky\LaravelMessagingGroups\Exceptions\GroupException;
use Phunky\LaravelMessagingGroups\Group;
use Phunky\LaravelMessagingGroups\GroupService;
use Phunky\LaravelMessagingGroups\Tests\Fixtures\User;

function groupUsers(): array
{
    return [
        User::create(['name' => 'Alice']),
        User::create(['name' => 'Bob']),
        User::create(['name' => 'Carol']),
    ];
}

describe('create', function () {
    it('creates a group, conversation, and owner participant', function () {
        [$owner] = groupUsers();
        $groups = app(GroupService::class);

        $group = $groups->create($owner, 'Team chat', 'https://example.com/a.png', ['topic' => 'work']);

        expect($group)->toBeInstanceOf(Group::class)
            ->and($group->name)->toBe('Team chat')
            ->and($group->avatar)->toBe('https://example.com/a.png')
            ->and($group->meta)->toBe(['topic' => 'work'])
            ->and($group->conversation)->toBeInstanceOf(Conversation::class)
            ->and($group->conversation->participant_hash)->not->toBeEmpty()
            ->and($group->participants)->toHaveCount(1)
            ->and($groups->messages($group))->not->toBeNull();

        $p = $group->participants->first();
        expect($p)->toBeInstanceOf(Participant::class)
            ->and($p->messageable_type)->toBe($owner->getMorphClass())
            ->and((string) $p->messageable_id)->toBe((string) $owner->getKey());
    });
});

describe('invite', function () {
    it('adds a participant and is idempotent', function () {
        [$owner, $invitee] = groupUsers();
        $groups = app(GroupService::class);
        $group = $groups->create($owner, 'G1');

        expect($group->fresh()->participants)->toHaveCount(1);

        $p1 = $groups->invite($group, $owner, $invitee);
        expect($group->fresh()->participants)->toHaveCount(2)
            ->and($p1)->toBeInstanceOf(Participant::class);

        $p2 = $groups->invite($group, $owner, $invitee);
        expect($p1->getKey())->toBe($p2->getKey())
            ->and($group->fresh()->participants)->toHaveCount(2);
    });
});

describe('send / messages', function () {
    it('sends and lists messages on the group conversation', function () {
        [$owner, $other] = groupUsers();
        $groups = app(GroupService::class);
        $group = $groups->create($owner, 'G2');
        $groups->invite($group, $owner, $other);

        $m1 = $groups->send($group, $owner, 'hello');
        $m2 = $groups->send($group, $other, 'hi');

        $page = $groups->messages($group);
        $idsOnPage = collect($page->getCollection())->pluck('id')->all();

        expect($m1->body)->toBe('hello')
            ->and($m2->body)->toBe('hi')
            ->and($idsOnPage)->toContain($m1->getKey(), $m2->getKey());
    });
});

describe('remove', function () {
    it('removes a member when owner acts and cannot remove the owner', function () {
        [$owner, $member] = groupUsers();
        $groups = app(GroupService::class);
        $group = $groups->create($owner, 'G3');
        $groups->invite($group, $owner, $member);

        expect($group->fresh()->participants)->toHaveCount(2);

        $groups->remove($group, $owner, $member);
        expect($group->fresh()->participants)->toHaveCount(1);

        expect(fn () => $groups->remove($group, $owner, $owner))
            ->toThrow(GroupException::class, 'owner cannot be removed');
    });
});

describe('leave', function () {
    it('allows non-owner to leave and blocks owner', function () {
        [$owner, $member] = groupUsers();
        $groups = app(GroupService::class);
        $group = $groups->create($owner, 'G4');
        $groups->invite($group, $owner, $member);

        $groups->leave($group, $member);
        expect($group->fresh()->participants)->toHaveCount(1);

        expect(fn () => $groups->leave($group, $owner))
            ->toThrow(GroupException::class, 'cannot leave');
    });
});

describe('transfer ownership', function () {
    it('transfers ownership so the previous owner can leave', function () {
        [$owner, $next] = groupUsers();
        $groups = app(GroupService::class);
        $group = $groups->create($owner, 'G5');
        $groups->invite($group, $owner, $next);

        $group = $groups->transferOwnership($group, $owner, $next);
        $group->refresh();

        expect($group->owner_type)->toBe($next->getMorphClass())
            ->and((string) $group->owner_id)->toBe((string) $next->getKey());

        $groups->leave($group, $owner);
        expect($group->fresh()->participants)->toHaveCount(1);
    });
});

describe('authorization', function () {
    it('prevents non-owners from inviting or removing', function () {
        [$owner, $a, $b] = groupUsers();
        $groups = app(GroupService::class);
        $group = $groups->create($owner, 'G6');
        $groups->invite($group, $owner, $a);
        $groups->invite($group, $owner, $b);

        expect(fn () => $groups->invite($group, $a, $b))
            ->toThrow(GroupException::class, 'Only the group owner');

        expect(fn () => $groups->remove($group, $a, $b))
            ->toThrow(GroupException::class, 'Only the group owner');
    });
});

describe('group events', function () {
    it('dispatches GroupCreated when a group is created', function () {
        Event::fake([GroupCreated::class]);
        [$owner] = groupUsers();

        app(GroupService::class)->create($owner, 'Evt1');

        Event::assertDispatched(GroupCreated::class);
    });

    it('dispatches GroupParticipantInvited only when a participant is new', function () {
        Event::fake([GroupParticipantInvited::class]);
        [$owner, $invitee] = groupUsers();
        $groups = app(GroupService::class);
        $group = $groups->create($owner, 'Evt2');

        $groups->invite($group, $owner, $invitee);
        $groups->invite($group, $owner, $invitee);

        Event::assertDispatchedTimes(GroupParticipantInvited::class, 1);
    });

    it('dispatches GroupParticipantRemoved when a member is removed', function () {
        Event::fake([GroupParticipantRemoved::class]);
        [$owner, $member] = groupUsers();
        $groups = app(GroupService::class);
        $group = $groups->create($owner, 'Evt3');
        $groups->invite($group, $owner, $member);

        $groups->remove($group, $owner, $member);

        Event::assertDispatched(GroupParticipantRemoved::class);
    });

    it('dispatches GroupOwnershipTransferred when ownership changes', function () {
        Event::fake([GroupOwnershipTransferred::class]);
        [$owner, $next] = groupUsers();
        $groups = app(GroupService::class);
        $group = $groups->create($owner, 'Evt4');
        $groups->invite($group, $owner, $next);

        $groups->transferOwnership($group, $owner, $next);

        Event::assertDispatched(GroupOwnershipTransferred::class);
    });

    it('dispatches GroupParticipantLeft when a member leaves', function () {
        Event::fake([GroupParticipantLeft::class]);
        [$owner, $member] = groupUsers();
        $groups = app(GroupService::class);
        $group = $groups->create($owner, 'Evt5');
        $groups->invite($group, $owner, $member);

        $groups->leave($group, $member);

        Event::assertDispatched(GroupParticipantLeft::class);
    });
});
