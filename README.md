# Laravel Messaging Groups

Add named group conversations to [phunky/laravel-messaging](https://github.com/phunky/laravel-messaging). Each group has an owner, a name, an optional avatar, and a backing `Conversation` — so all core messaging features (sending, receipts, events, extensions) work in groups without any special handling.

## Installation

```bash
composer require phunky/laravel-messaging-groups
```

Register the extension and the group model in `config/messaging.php`:

```php
'models' => [
    // ...
    'group' => \Phunky\LaravelMessagingGroups\Group::class,
],

'extensions' => [
    // ...
    \Phunky\LaravelMessagingGroups\GroupsExtension::class,
],
```

Run migrations:

```bash
php artisan migrate
```

## Usage

Inject `GroupService` — or resolve it from the container — to manage groups.

### Creating a group

```php
use Phunky\LaravelMessagingGroups\GroupService;

$group = $groupService->create(
    owner: $user,
    name: 'Project Alpha',
    avatar: 'avatars/project-alpha.png', // optional
    meta: ['color' => '#4f46e5'],         // optional JSON
);
```

The group is backed by a `Conversation` created atomically in the same transaction. The owner is added as the first participant automatically.

### Managing participants

```php
// Only the owner can invite or remove members
$groupService->invite($group, $owner, $newMember);
$groupService->remove($group, $owner, $memberToRemove);

// Any participant can leave; the owner must transfer ownership first
$groupService->transferOwnership($group, $owner, $newOwner);
$groupService->leave($group, $member);
```

`invite()` is idempotent — calling it again for an existing participant returns the existing record without firing a second event. `remove()` and `leave()` throw `GroupException` if the owner tries to remove themselves; call `transferOwnership()` first.

### Sending and reading messages

```php
// Send a message (delegates to the core MessagingService on the group's conversation)
$message = $groupService->send($group, $sender, 'Hello everyone!');
$message = $groupService->send($group, $sender, 'Check this out', meta: ['url' => '...']);

// Paginated messages (returns CursorPaginator or LengthAwarePaginator)
$messages = $groupService->messages($group);
```

Because group messages go through the core `MessagingService`, `MessageSending` and `MessageSent` fire normally — any extension that listens to those events (attachments, reactions, etc.) works in groups out of the box.

### Relationships

```php
$group->conversation;          // the backing Conversation model
$group->owner;                 // polymorphic — works with any Messageable type
$group->participants()->get(); // all Participant records in the conversation
```

## Events

Domain events extend [`BroadcastableMessagingEvent`](https://github.com/phunky/laravel-messaging) from `phunky/laravel-messaging`: they inherit `Dispatchable`, `SerializesModels`, and `ShouldDispatchAfterCommit`, and broadcast on the same **private conversation channels** as core messaging. Broadcasting is gated by `config('messaging.broadcasting.enabled')` (via `broadcastWhen()`); when disabled, events still dispatch locally but are not broadcast. See the core package README for channel naming and `routes/channels.php` authorization.

| Event | Properties |
|-------|------------|
| `GroupCreated` | `Group $group`, `Messageable $owner` |
| `GroupParticipantInvited` | `Group $group`, `Participant $participant`, `Messageable $invitedBy` |
| `GroupParticipantRemoved` | `Group $group`, `Messageable $removed`, `Messageable $removedBy` |
| `GroupParticipantLeft` | `Group $group`, `Messageable $participant` |
| `GroupOwnershipTransferred` | `Group $group`, `Messageable $previousOwner`, `Messageable $newOwner` |

When broadcasting is enabled, each event’s `broadcastAs()` string is shown below. On the client, Laravel Echo uses the same name with a **leading dot** in `.listen('.messaging.group.created', …)` so it is not prefixed with the application namespace.

| Event | `broadcastAs()` |
|-------|-----------------|
| `GroupCreated` | `messaging.group.created` |
| `GroupParticipantInvited` | `messaging.group.participant_invited` |
| `GroupParticipantRemoved` | `messaging.group.participant_removed` |
| `GroupParticipantLeft` | `messaging.group.participant_left` |
| `GroupOwnershipTransferred` | `messaging.group.ownership_transferred` |

Group messages dispatch the standard `MessageSending` and `MessageSent` events from the core package — not package-specific events.

```php
use Phunky\LaravelMessagingGroups\Events\GroupParticipantInvited;

Event::listen(GroupParticipantInvited::class, function (GroupParticipantInvited $event) {
    Notification::send($event->participant->messageable, new AddedToGroup($event->group));
});
```

## License

MIT
