<?php

namespace Phunky\LaravelMessagingGroups;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Phunky\LaravelMessaging\Contracts\Messageable;
use Phunky\LaravelMessaging\Models\Conversation;
use Phunky\LaravelMessaging\Models\Participant;

/**
 * @property int $id
 * @property int $conversation_id
 * @property string $name
 * @property string|null $avatar
 * @property array|null $meta
 * @property string $owner_type
 * @property int|string $owner_id
 * @property-read Conversation $conversation
 * @property-read Model&Messageable $owner
 */
class Group extends Model
{
    protected $guarded = [];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->table = messaging_table('groups');
    }

    protected function casts(): array
    {
        return [
            'meta' => 'array',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(config('messaging.models.conversation'), 'conversation_id');
    }

    public function owner(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Participants in the group's underlying conversation (same as {@see Conversation::participants()}).
     */
    public function participants(): HasMany
    {
        /** @var class-string<Participant> $participantClass */
        $participantClass = config('messaging.models.participant');

        return $this->hasMany($participantClass, 'conversation_id', 'conversation_id');
    }
}
