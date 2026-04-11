# Laravel Messaging Groups

Group conversations extension for [phunky/laravel-messaging](https://github.com/phunky/laravel-messaging). 

Groups have an owner, name, optional avatar, and a backing `Conversation` — so all core messaging features work out of the box.


## Installation

```bash
composer require phunky/laravel-messaging-groups
```

Register the group model and extension in `config/messaging.php` (add to the existing `models` and `extensions` arrays — do not remove the core keys):

```php
'models' => [
    // ... conversation, participant, message, event
    'group' => \Phunky\LaravelMessagingGroups\Group::class,
],

'extensions' => [
    // ... other MessagingExtension classes, if any
    \Phunky\LaravelMessagingGroups\GroupsExtension::class,
],
```

```bash
php artisan migrate
```

## Usage

Resolve `GroupService` via the container (constructor injection, `app(GroupService::class)`, etc.):

```php
use Phunky\LaravelMessagingGroups\GroupService;

$groupService = app(GroupService::class);
```

### Create a group

```php
$group = $groupService->create(
    owner: $user,
    name: 'Project Alpha',
    avatar: 'avatars/project-alpha.png',    // optional
    meta: ['color' => '#4f46e5'],           // optional JSON meta on the group
);
```

The owner is added as the first participant automatically.

### Manage participants

Only the **group owner** may `invite`, `remove`, or `transferOwnership`. `invite` is idempotent for existing participants.

```php
$groupService->invite($group, $owner, $newMember);
$groupService->remove($group, $owner, $memberToRemove);
$groupService->transferOwnership($group, $owner, $newOwner);
$groupService->leave($group, $member);
```

The owner cannot `remove` themselves or `leave` until they `transferOwnership` to another participant.

### Send and read messages

```php
$message = $groupService->send($group, $sender, 'Hello everyone!');
$message = $groupService->send($group, $sender, 'With meta', meta: ['url' => 'https://example.com']);

$messages = $groupService->messages($group); // CursorPaginator or LengthAwarePaginator
```

### Relationships

```php
$group->conversation;
$group->owner;
$group->participants()->get();
```

## Events

All group events extend [`Phunky\LaravelMessaging\Events\BroadcastableMessagingEvent`](https://github.com/phunky/laravel-messaging) and broadcast on the same private conversation channels as core messaging when `config('messaging.broadcasting.enabled')` is `true`. With Laravel Echo, listen using the `broadcastAs()` name with a **leading dot** (e.g. `.listen('.messaging.group.created', …)`).

| Event | Public properties | `broadcastAs()` |
|---|---|---|
| `GroupCreated` | `$group`, `$owner` | `messaging.group.created` |
| `GroupParticipantInvited` | `$group`, `$participant`, `$invitedBy` | `messaging.group.participant_invited` |
| `GroupParticipantRemoved` | `$group`, `$removed`, `$removedBy` | `messaging.group.participant_removed` |
| `GroupParticipantLeft` | `$group`, `$participant` | `messaging.group.participant_left` |
| `GroupOwnershipTransferred` | `$group`, `$previousOwner`, `$newOwner` | `messaging.group.ownership_transferred` |

Messages sent through the group still dispatch the core `Phunky\LaravelMessaging\Events\MessageSending` and `Phunky\LaravelMessaging\Events\MessageSent` events.

```php
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Phunky\LaravelMessagingGroups\Events\GroupParticipantInvited;

Event::listen(GroupParticipantInvited::class, function (GroupParticipantInvited $event) {
    // Your User (or other messageable) must use Illuminate\Notifications\Notifiable for Notification::send.
    Notification::send($event->participant->messageable, new \App\Notifications\AddedToGroup($event->group));
});
```

Create `App\Notifications\AddedToGroup` (or swap in your own notification class).

## License

MIT — see [LICENSE.md](LICENSE.md).
